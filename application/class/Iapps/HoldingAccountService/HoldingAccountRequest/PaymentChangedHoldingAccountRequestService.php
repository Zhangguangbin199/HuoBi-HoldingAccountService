<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Aws\Sns\MessageValidator\Message;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\HoldingAccountService\Common\CoreConfigType;
use Iapps\HoldingAccountService\Common\IncrementIDAttribute;
use Iapps\HoldingAccountService\Common\IncrementIDServiceFactory;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\HoldingAccountRequestValidator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountFeeCalculator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;
use Iapps\HoldingAccountService\Common\CoreConfigDataServiceFactory;
use Iapps\HoldingAccountService\Common\HoldingAccountCorporateService;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;
use Iapps\HoldingAccountService\HoldingAccountRequest\RequestType;


class PaymentChangedHoldingAccountRequestService  extends IappsBaseService{

    protected $_trx = NULL;
    protected $_trx_serv = NULL;

    public function getHoldingAccountTypeCode()
    {
        $holding_account_type_code = NULL;
        if ($this->_trx != NULL) {
            if ($this->_trx->getTransactionType() != NULL) {
                switch ($this->_trx->getTransactionType()->getCode()) {
                    case TransactionType::CODE_TOP_UP:
                    case TransactionType::CODE_WITHDRAW:
                        $holding_account_type_code = HoldingAccountType::PERSONAL_ACCOUNT;
                        break;
                    default:
                        $holding_account_type_code = NULL;
                }
            }
        }

        return $holding_account_type_code;
    }

    public function getRequestType()
    {
        $request_type_code = NULL;
        if($this->_trx != NULL) {
            if ($this->_trx->getTransactionType() != NULL) {
                switch ($this->_trx->getTransactionType()->getCode()) {
                    case TransactionType::CODE_TOP_UP:
                        $request_type_code = RequestType::TOPUP;
                        break;
                    case TransactionType::CODE_WITHDRAW:
                        $request_type_code = RequestType::WITHDRAWAL;
                        break;
                    default:
                        $request_type_code = NULL;
                }
            }
        }

        return $request_type_code;
    }

    public function complete($module_code, $transactionID, $reference_no = NULL)
    {
        if( $request = $this->getRepository()->findByTransactionID($module_code, $transactionID) )
        {
            $tran_serv = HoldingAccountTransactionServiceFactory::build();
            $tran_serv->setUpdatedBy($this->getUpdatedBy());
            $tran_serv->setIpAddress($this->getIpAddress());
            if( $this->_trx = $tran_serv->findByTransactionID($request->getTransactionID()) ) {
                if( !$request->isRequestType($this->_getRequestType()) )
                {
                    $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                    return false;
                }

                HoldingAccountTransactionServiceFactory::reset();
                $this->_trx_serv = HoldingAccountTransactionServiceFactory::build($this->_trx->getTransactionType()->getCode());
                $this->_trx_serv->setUpdatedBy($this->getUpdatedBy());
                $this->_trx_serv->setIpAddress($this->getIpAddress());

                $ori_request = clone($request);

                $holdingAccount_serv = $this->_getHoldingAccountService();
                $holdingAccount_serv->setUpdatedBy($this->getUpdatedBy());
                $holdingAccount_serv->setIpAddress($this->getIpAddress());
                if ($holdingAccount = $holdingAccount_serv->findById($request->getHoldingAccount()->getId())) {
                    $request->setHoldingAccount($holdingAccount);
                    $request->setReferenceNo($reference_no);

                    if ($this->getUpdatedBy() == NULL)
                        $this->setUpdatedBy($request->getHoldingAccount()->getUserProfileId());

                    $request->setUpdatedBy($this->getUpdatedBy());
                    if ($request->complete()) {
                        $this->getRepository()->startDBTransaction();

                        Logger::debug('HoldingAccount.request: completing action - ' . $request->getId());
                        if ($this->_completeAction($request)) {

                            //complete request
                            if ($this->getRepository()->updateRequestStatus($request)) {
                                $this->getRepository()->completeDBTransaction();
                                $this->fireLogEvent('iafb_holding_account.holding_account_request', AuditLogAction::UPDATE, $request->getId(), $ori_request);

                                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_COMPLETE_SUCCESS);
                                return true;
                            }
                        }

                        $this->getRepository()->rollbackDBTransaction();
                    }
                }
            }
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
            return false;
        }

        if( $this->getResponseCode() == NULL )
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_COMPLETE_FAILED);
        return false;
    }

    public function cancel($module_code, $transactionID)
    {
        if( $request = $this->getRepository()->findByTransactionID($module_code, $transactionID) )
        {
            $tran_serv = HoldingAccountTransactionServiceFactory::build();
            $tran_serv->setUpdatedBy($this->getUpdatedBy());
            $tran_serv->setIpAddress($this->getIpAddress());
            if( $this->_trx = $tran_serv->findByTransactionID($request->getTransactionID()) ) {
                if (!$request->isRequestType($this->_getRequestType())) {
                    $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                    return false;
                }

                HoldingAccountTransactionServiceFactory::reset();
                $this->_trx_serv = HoldingAccountTransactionServiceFactory::build($this->_trx->getTransactionType()->getCode());
                $this->_trx_serv->setUpdatedBy($this->getUpdatedBy());
                $this->_trx_serv->setIpAddress($this->getIpAddress());

                $ori_request = clone($request);

                $holdingAccount_serv = $this->_getHoldingAccountService();
                $holdingAccount_serv->setUpdatedBy($this->getUpdatedBy());
                $holdingAccount_serv->setIpAddress($this->getIpAddress());
                if ($holdingAccount = $holdingAccount_serv->findById($request->getHoldingAccount()->getId())) {
                    $request->setHoldingAccount($holdingAccount);
                    if ($request->cancel()) {
                        if ($this->getUpdatedBy() == NULL)
                            $this->setUpdatedBy($this->_request->getHoldingAccount()->getUserProfileId());
                        $request->setUpdatedBy($this->getUpdatedBy());

                        $this->getRepository()->startDBTransaction();

                        Logger::debug('HoldingAccount.request: cancelling action - ' . $request->getId());
                        if ($this->_cancelAction($request)) {
                            //complete request
                            if ($this->getRepository()->updateRequestStatus($request)) {
                                $this->getRepository()->completeDBTransaction();
                                $this->fireLogEvent('iafb_holding_account.holding_account_request', AuditLogAction::UPDATE, $request->getId(), $ori_request);

                                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_CANCEL_SUCCESS);
                                return true;
                            }
                        }

                        $this->getRepository()->rollbackDBTransaction();
                    }
                }
            }
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
            return false;
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_CANCEL_FAILED);
        return false;
    }


    protected function _cancelAction(HoldingAccountRequest $request)
    {
        $this->_trx_serv->setHoldingAccountRequest($request);
        if ($this->_cancelTransaction($this->_trx)) {

            return true;
        }

        return false;
    }

    protected function _completeAction(HoldingAccountRequest $request)
    {
        $this->_trx_serv->setHoldingAccountRequest($request);
        if ($this->_completeTransaction($this->_trx)) {
            return true;
        }

        return false;
    }

    protected function _cancelTransaction(HoldingAccountTransaction $transaction)
    {
        if( !$result = $this->_trx_serv->cancelTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _completeTransaction(HoldingAccountTransaction $transaction)
    {
        if( !$result = $this->_trx_serv->completeTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }


    protected function _getHoldingAccountService()
    {
        $holdingAccount_service =  HoldingAccountServiceFactory::build($this->getHoldingAccountTypeCode());
        $holdingAccount_service->setUpdatedBy($this->getUpdatedBy());
        $holdingAccount_service->setIpAddress($this->getIpAddress());
        return $holdingAccount_service;
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        return $sc_serv->getByCode($this->getRequestType(), RequestType::getSystemGroupCode());
    }

}