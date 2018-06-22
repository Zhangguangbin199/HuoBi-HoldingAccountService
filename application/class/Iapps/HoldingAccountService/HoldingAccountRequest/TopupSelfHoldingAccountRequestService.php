<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;

class TopupSelfHoldingAccountRequestService extends HoldingAccountRequestService{

    protected $_trx = NULL;

    function __construct(HoldingAccountRequestRepository $rp, $ipAddress, $updatedBy, HoldingAccountPaymentInterface $paymentInterface)
    {
        parent::__construct($rp, $ipAddress, $updatedBy, $paymentInterface);
    }

    public function request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        if( $result = parent::request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode) )
        {
            $result['transaction_info'] = $this->_trx->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));

            //transaction_no returned now based on BT2 which is the combination of user's mobile number and first letter of display name
            $mobile_no = "";
            $name = "";
            $acc_serv = AccountServiceFactory::build();
            if($userProfile = $acc_serv->getUserProfile($user_profile_id)) {
                if($userProfile instanceof User) {
                    if ($userProfile->getMobileNumberObj() != NULL) {
                        $mobile_no = $userProfile->getMobileNumberObj()->getMobileNumber();
                    }
                    if($userProfile->getName() != NULL) {
                        if(strlen($userProfile->getName()) > 3) {
                            $name = strtoupper(substr($userProfile->getName(), 0, 3));
                        }
                    }
                }
            }

            $result['transaction_info']['transaction_no'] = $name . " ". $mobile_no;

            return $result;
        }

        return false;
    }

    public function complete($token, $reference_no = NULL)
    {
        if( parent::complete($token, $reference_no) )
        {
            $result['transaction_info'] = $this->_trx->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));
            return $result;
        }

        return false;
    }

    protected function _requestAction(HoldingAccountRequest $request)
    {//create trx on request

        $request->setDescription('Payment Mode: ' . 'NONE');
        if( $request->getPaymentCode() )
        {
            $paymentModeService = PaymentServiceFactory::build();
            if( $mode = $paymentModeService->getPaymentInfo($request->getPaymentCode()) )
                $request->setDescription('Payment Mode: ' . $mode->getName());
        }

        if( $this->_trx = $this->_createTransaction($request) ) {

            $paymentInfo = $this->getPaymentInfo();
            if( $paymentInfo['amount'] > 0.0 )
            {//check if amount tally
                if ($this->_paymentRequest($request, $this->getPaymentInfo(), $this->_trx)) {
                    return true;
                }
            }
            else
                return true;
        }

        return false;
    }

    protected function _cancelAction(HoldingAccountRequest $request)
    {//cancel transaction & payment

        $this->setHoldingAccountRequest($request);
        //find transaction
        $tran_serv = HoldingAccountTransactionServiceFactory::build();
        $tran_serv->setUpdatedBy($this->getUpdatedBy());
        $tran_serv->setIpAddress($this->getIpAddress());
        if( $this->_trx = $tran_serv->findByTransactionID($request->getTransactionID()) )
        {
            if( $this->_cancelTransaction($this->_trx) )
            {
                if( $payment_request_id = $request->getPaymentRequestId() )
                {
                    $this->_paymentCancel($request);
                }

                return true;
            }
        }

        return false;
    }

    protected function _completeAction(HoldingAccountRequest $request)
    {
        $this->setHoldingAccountRequest($request);
        //find transaction
        $tran_serv = HoldingAccountTransactionServiceFactory::build();
        $tran_serv->setUpdatedBy($this->getUpdatedBy());
        $tran_serv->setIpAddress($this->getIpAddress());
        if( $this->_trx = $tran_serv->findByTransactionID($request->getTransactionID()) )
        {//complete transaction
            if ($this->_completeTransaction($this->_trx)) {
                if( $this->_trx->getItems()->getTotalAmount() > 0 )
                {//only need payment if payable amount > 0
                    if( $request->getPaymentRequestId() == NULL )
                    {//do payment request here if it was not done in request

                        if( !$paymentInfo = $this->getPaymentInfo() )
                        {
                            $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                            return false;
                        }
                        if( $request->getPaymentCode() !== $paymentInfo['payment_code'] )
                        {
                            $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                            return false;
                        }

                        if (!$this->_paymentRequest($request, $this->getPaymentInfo(), $this->_trx))
                        {
                            return false;
                        }
                    }

                    if( $this->_paymentComplete($request) )
                        return true;
                }
                else
                    return true;
            }
        }

        return false;
    }

    protected function _findHoldingAccount(HoldingAccount $holdingAccount)
    {
        $holdingAccount->setUserProfileId($holdingAccount->getUserProfileId());

        if( !$holdingAcct = parent::_findHoldingAccount($holdingAccount) )
        {
            if($holdingAccount->getHoldingAccountType()->getCode() == HoldingAccountType::PERSONAL_ACCOUNT) {
                $holdingAccountConfig = new HoldingAccountConfig();
                $holdingAccount->setReferenceId($holdingAccount->getUserProfileId());
                $config_info = $holdingAccountConfig->generateDefaultConfig();

                if ($holdingAccount = $this->_getHoldingAccountService()->createHoldingAccount($holdingAccount, $config_info)) {
                    if ($holdingAccount instanceof HoldingAccount)
                        return $holdingAccount;
                }
            }
            return false;
        }

        return $holdingAcct;
    }

    protected function _getCorporateService($country_currency_code)
    {
        $corp_serv = CorporateServServiceFactory::build();

        return $corp_serv->findByTransactionTypeAndCountryCurrencyCode(TransactionType::CODE_TOP_UP, $country_currency_code);
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        return $sc_serv->getByCode(RequestType::TOPUP, RequestType::getSystemGroupCode());
    }

    protected function _getHoldingAccountService()
    {
        $holding_account_service =  HoldingAccountServiceFactory::build(HoldingAccountType::PERSONAL_ACCOUNT);
        $holding_account_service->setUpdatedBy($this->getUpdatedBy());
        $holding_account_service->setIpAddress($this->getIpAddress());
        return $holding_account_service;
    }

    protected function _checkActiveRequest(HoldingAccountRequest $currentRequest)
    {
        return true;
    }

    protected function _constructMainItemDescription(HoldingAccountRequest $request)
    {
        $request->getItemDescription()->add('Top Up Amount', CurrencyFormatter::format($request->getAmount(), $request->getHoldingAccount()->getCountryCurrencyCode()) );
        return true;
    }
}