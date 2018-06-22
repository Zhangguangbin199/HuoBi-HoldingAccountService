<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\HoldingAccountRequestValidator;

class CollectionHoldingAccountRequestService extends HoldingAccountRequestService{

    protected $is_collection = false;   //default is refund

    public function setIsCollection($is_collection)
    {
        $this->is_collection = $is_collection;
        return $this;
    }

    public function getIsCollection()
    {
        return $this->is_collection;
    }

    public function request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        //refund take as positive amount
        $amount = abs($amount);
        return parent::request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode, $module_code, $transactionID);
    }

    public function complete($token, $reference_no = NULL)
    {
        if( parent::complete($token, $reference_no))
        {
            $result = array();

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
    {//nothing to be done
        return true;
    }

    protected function _cancelAction(HoldingAccountRequest $request)
    {//nothing to be done
        return true;
    }

    protected function _completeAction(HoldingAccountRequest $request)
    {//to credit to HoldingAccount
        $holdingAccount_serv = $this->_getHoldingAccountService();

        //collect money
        if( $this->getIsCollection() )
        {
            if( $holdingAccount_serv->collect($request->getHoldingAccount(),
                                       abs($request->getAmount()),
                                       $request->getModuleCode(),
                                       $request->getTransactionID()) )
                return true;
        }
        else
        {
            if( $holdingAccount_serv->refund($request->getHoldingAccount(),
                abs($request->getAmount()),
                $request->getModuleCode(),
                $request->getTransactionID()) )
                return true;
        }

        $this->setResponseCode($holdingAccount_serv->getResponseCode());
        return false;
    }

    //no transaction to be created
    protected function _getCorporateService($country_currency_code)
    {
        return false;
    }

    protected function _findHoldingAccount(HoldingAccount $holdingAccount)
    {
        if( !$holdingAcct = parent::_findHoldingAccount($holdingAccount) )
        {
            return false;
        }

        return $holdingAcct;
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        if( $this->getIsCollection() )
            return $sc_serv->getByCode(RequestType::COLLECTION, RequestType::getSystemGroupCode());
        else
            return $sc_serv->getByCode(RequestType::REFUND, RequestType::getSystemGroupCode());
    }

    protected function _validateRequest(HoldingAccountRequest $request)
    {
        $v = HoldingAccountRequestValidator::make($request);
        $this->setResponseCode($v->getErrorCode());
        return !$v->fails();
    }
}