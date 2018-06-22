<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\User;

class TopupHoldingAccountRequestService extends HoldingAccountRequestService{

    protected $_topupTrx = NULL;

    public function request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        if( $result = parent::request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode) )
        {
            //$result['payable_amount'] = $this->_topupTrx->getItems()->getTotalAmount();
            $result['transaction_info'] = $this->_topupTrx->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
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
            $result['transaction_info'] = $this->_topupTrx->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));
            return $result;
        }

        return false;
    }

    protected function _findHoldingAccount($user_profile_id, $country_currency_code)
    {
        if( !$holding_account = parent::_findHoldingAccount($user_profile_id, $country_currency_code) )
        {
            return false;
        }

        return $holding_account;
    }

    protected function _requestAction(HoldingAccountRequest $request)
    {//create trx on request

        $paymentModeService = PaymentServiceFactory::build();
        if( $mode = $paymentModeService->getPaymentInfo($request->getPaymentCode()) )
            $request->setDescription('Payment Mode: ' . $mode->getName());

        if( $this->_topupTrx = $this->_createTransaction($request) ) {

            $paymentInfo = $this->getPaymentInfo();
            if( $paymentInfo['amount'] > 0.0 )
            {//check if amount tally
                if ($this->_paymentRequest($request, $this->getPaymentInfo(), $this->_topupTrx))
                    return true;
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
        if( $this->_topupTrx = $tran_serv->findByTransactionID($request->getTransactionID()) )
        {
            if( $this->_cancelTransaction($this->_topupTrx) )
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
        if( $this->_topupTrx = $tran_serv->findByTransactionID($request->getTransactionID()) )
        {//complete transaction
            if ($this->_completeTransaction($this->_topupTrx)) {

                //do payment only if transaction amount > 0
                if( $this->_topupTrx->getItems()->getTotalAmount() > 0 )
                {
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

                        if (!$this->_paymentRequest($request, $this->getPaymentInfo(), $this->_topupTrx))
                            return false;
                    }

                    if ($this->_paymentComplete($request)) {
                        return true;
                    }
                }
                else
                    return true;
            }

        }

        return false;
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

    protected function _constructMainItemDescription(HoldingAccountRequest $request)
    {
        $request->getItemDescription()->add('Top Up Amount', CurrencyFormatter::format($request->getAmount(), $request->getHoldingAccount()->getCountryCurrencyCode()) );
        return true;
    }
}