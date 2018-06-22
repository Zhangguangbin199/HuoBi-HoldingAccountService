<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\OutHoldingAccountRequestValidator;

class UtilizeHoldingAccountRequestService extends HoldingAccountRequestService{

    public function request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        //utilize take as negative amount
        $amount = abs($amount) * -1;

        if( $result = parent::request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode, $module_code, $transactionID) )
        {
            $holdingAccount = $this->_request->getHoldingAccount();
            if( $holdingAccount instanceof HoldingAccount )
            {
                $result['balance']['initial'] = $holdingAccount->getBalance()->getOriginalValue();
                $result['balance']['new'] = $holdingAccount->getBalance()->getValue();
            }

            return $result;
        }

        return false;
    }

    protected function _requestAction(HoldingAccountRequest $request)
    {//to deduct from HoldingAccount
        $holdingAccount_serv = $this->_getHoldingAccountService();

        //utilise
        if( $holdingAccount_serv->utilise($request->getHoldingAccount(),
            abs($request->getAmount()),
            $request->getModuleCode(),
            $request->getTransactionID()) )
        {
            return true;
        }

        $this->setResponseCode($holdingAccount_serv->getResponseCode());
        return false;
    }

    protected function _cancelAction(HoldingAccountRequest $request)
    {//to revert the deduction
        $holdingAccount_serv = $this->_getHoldingAccountService();

        //utilise
        return $holdingAccount_serv->revert($request->getHoldingAccount(),
                                     $request->getAmount(),
                                     $request->getModuleCode(),
                                     $request->getTransactionID());
    }

    protected function _completeAction(HoldingAccountRequest $request)
    {//nothing to be done on completion
        return true;
    }

    //no transaction to be created
    protected function _getCorporateService($country_currency_code)
    {
        return false;
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        return $sc_serv->getByCode(RequestType::UTILISE, RequestType::getSystemGroupCode());
    }

    protected function _checkActiveRequest(HoldingAccountRequest $currentRequest)
    {//it's ok to have multiple request
        return true;
    }

    protected function _validateRequest(HoldingAccountRequest $request)
    {
        $v = OutHoldingAccountRequestValidator::make($request);
        $this->setResponseCode($v->getErrorCode());
        return !$v->fails();
    }
}