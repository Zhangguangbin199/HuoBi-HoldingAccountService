<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Microservice\EwalletService\EwalletServiceFactory;
use Iapps\Common\Microservice\ModuleCode;
use Iapps\Common\Microservice\PaymentService\PaymentModeGroup;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\HoldingAccountService\Common\GeneralDescription;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\Common\PaymentModeType;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigService;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\OutHoldingAccountRequestValidator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountTransaction\PasscodeValidator;

class WithdrawalHoldingAccountRequestService extends HoldingAccountRequestService{

    protected $_withdrawal_trx = NULL;

    public function request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        //withdrawal take as negative amount
        $amount *= -1;
        if( isset($this->paymentInfo['amount']) )
            $this->paymentInfo['amount'] *= -1;


        if( isset($this->paymentInfo['collection_amount']) )
            $this->paymentInfo['collection_amount'] *= -1;


        if( $result = parent::request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode) )
        {
            $result['transaction_info'] = $this->_withdrawal_trx->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));
            return $result;
        }

        return false;
    }

    public function complete($token, $reference_no = NULL)
    {
        //withdrawal take as negative amount
        if( isset($this->paymentInfo['amount']) )
            $this->paymentInfo['amount'] *= -1;


        if( parent::complete($token, $reference_no) )
        {
            $result['transaction_info'] = $this->_withdrawal_trx->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));
            return $result;
        }

        return false;
    }

    protected function _requestAction(HoldingAccountRequest $request)
    {//to deduct from HoldingAccount
        $holdingAccoutnConfigServ = HoldingAccountConfigServiceFactory::build();

        if($request->getPaymentCode() != PaymentModeType::EWALLET){
            $this->setResponseCode(MessageCode::CODE_WITHDRAWAL_NOT_ALLOWED);
            return false;
        }
        $holdingConfigFilter = new HoldingAccountConfig();
        $holdingConfigFilter->setModuleCode(ModuleCode::EWALLET_SERVICE);
        $holdingConfigFilter->setIsSupported(1);
        $holdingConfigFilter->setHoldingAccountId($request->getHoldingAccount()->getId());
        if(!$holdingAccoutnConfigServ->getHoldingAccountConfigByFilter($holdingConfigFilter)){
            $this->setResponseCode(MessageCode::CODE_WITHDRAWAL_NOT_ALLOWED);
            return false;
        }


        $paymentModeService = PaymentServiceFactory::build();
        if( $mode = $paymentModeService->getPaymentInfo($request->getPaymentCode()) )
            $request->setDescription('Collection Mode: ' . $mode->getName());


        $paymentInfo = $this->getPaymentInfo();

        $paymentInfo['option']['is_collection'] = '1';
        if(!isset($paymentInfo['display_rate']) || !isset($paymentInfo['collection_amount'])|| !isset($paymentInfo['country_currency_code'])){
            $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
            return false;
        }

        $request->setToAmount($paymentInfo['collection_amount']);
        $request->setDisplayRate($paymentInfo['display_rate']);
        $request->setToCountryCurrencyCode($paymentInfo['country_currency_code']);


        $ewalletServ =  EwalletServiceFactory::build();
        if(!$limit = $ewalletServ->getEwalletLimit($request->getUserProfileId(), $request->getToCountryCurrencyCode())){
            $this->setResponseCode(MessageCode::CODE_EXCEEDED_TOPUP_LIMIT);
            $this->setResponseMessage(CurrencyFormatter::format(abs($request->getToAmount()), $request->getToCountryCurrencyCode()));
            return false;
        }else{
            if(abs($request->getToAmount()) > $limit->limit){
                $this->setResponseCode(MessageCode::CODE_EXCEEDED_TOPUP_LIMIT);
                $this->setResponseMessage(CurrencyFormatter::format($limit->limit, $request->getToCountryCurrencyCode()));

                return false;
            }
        }

        //check if password required
        if( !$this->_validatePasscode() )
        {
            $this->setResponseCode(MessageCode::CODE_INVALID_PASSCODE);
            return false;
        }

        if( $this->_withdrawal_trx = $this->_createTransaction($request) ) {

            if( abs($paymentInfo['amount']) > 0.0 )
            {//check if amount tally
                if($request->getAmount() * $request->getDisplayRate() != $request->getToAmount()){
                    $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                    return false;
                }
                if(!$this->_paymentRequest($request, $paymentInfo, $this->_withdrawal_trx)) {
                    return false;
                }
            }

            $holdingAccount_serv = $this->_getHoldingAccountService();

            //withdraw
            return $holdingAccount_serv->withdrawal($request->getHoldingAccount(),
                                            $request->getAmount(),
                                            $request->getModuleCode(),
                                            $request->getTransactionID());
        }

        return false;
    }

    protected function _cancelAction(HoldingAccountRequest $request)
    {//to revert the deduction
        $this->setHoldingAccountRequest($request);
        //find transaction
        $tran_serv = HoldingAccountTransactionServiceFactory::build();
        $tran_serv->setUpdatedBy($this->getUpdatedBy());
        $tran_serv->setIpAddress($this->getIpAddress());
        if( $this->_withdrawal_trx = $tran_serv->findByTransactionID($request->getTransactionID()) )
        {
            if( $this->_cancelTransaction($this->_withdrawal_trx) )
            {
                if( $payment_request_id = $request->getPaymentRequestId() )
                {
                    $this->_paymentCancel($request);
                }

                return true;
            }
        }
    }

    protected function _completeAction(HoldingAccountRequest $request)
    {
        $this->setHoldingAccountRequest($request);
        //find transaction
        $tran_serv = HoldingAccountTransactionServiceFactory::build();
        $tran_serv->setUpdatedBy($this->getUpdatedBy());
        $tran_serv->setIpAddress($this->getIpAddress());
        if( $this->_withdrawal_trx = $tran_serv->findByTransactionID($request->getTransactionID()) )
        {//complete transaction

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
                
                if (!$this->_paymentRequest($request, $this->getPaymentInfo(), $this->_withdrawal_trx))
                {
                    return false;
                }
            }

            if ($this->_completeTransaction($this->_withdrawal_trx)) {
                if ($this->_paymentComplete($request)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function _findHoldingAccount(HoldingAccount $holdingAccount)
    {
        $holdingAccount->setUserProfileId($holdingAccount->getUserProfileId());
        if( !$holdingAcct = parent::_findHoldingAccount($holdingAccount) )
        {
            return false;
        }

        return $holdingAcct;
    }

    //no transaction to be created
    protected function _getCorporateService($country_currency_code)
    {
        $corp_serv = CorporateServServiceFactory::build();

        return $corp_serv->findByTransactionTypeAndCountryCurrencyCode(TransactionType::CODE_WITHDRAW, $country_currency_code);
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        return $sc_serv->getByCode(RequestType::WITHDRAWAL, RequestType::getSystemGroupCode());
    }

    protected function _validatePasscode()
    {
        $payment_code = $this->getPaymentInfo()['payment_code'];

        $payment_serv = PaymentServiceFactory::build();
        if($mode = $payment_serv->getPaymentInfo($payment_code) )
        {
            switch($mode->getGroup())
            {//only cash payment type required passcode
                case PaymentModeGroup::CASH:
                    $v = PasscodeValidator::make($this->passcode);
                    return !$v->fails();
                    break;
                default:
                    return true;
                    break;
            }
        }

        return false;
    }

    protected function _validateRequest(HoldingAccountRequest $request)
    {
        $v = OutHoldingAccountRequestValidator::make($request);
        $this->setResponseCode($v->getErrorCode());
        return !$v->fails();
    }

    /*
     * no expired date required
     */
    protected function _setExpiryData(HoldingAccountRequest $request)
    {
        return true;
    }

    protected function _checkActiveRequest(HoldingAccountRequest $currentRequest)
    {
        return true;
    }

    protected function _constructMainItemDescription(HoldingAccountRequest $request)
    {
        $request->getItemDescription()->add('Withdrawal Amount', CurrencyFormatter::format($request->getAmount(), $request->getHoldingAccount()->getCountryCurrencyCode()) );
        return true;
    }
}