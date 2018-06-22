<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\BidirectionalHoldingAccountRequestValidator;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\HoldingAccountRequestValidator;

class VoidHoldingAccountRequestService extends HoldingAccountRequestService{

    public function request($user_profile_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        //void take as positive amount to deduct, negative amount to credit
        $amount = $amount * -1;

        return parent::request($user_profile_id, $country_currency_code, $amount, $payment_mode, $module_code, $transactionID);
    }

    public function complete($token, $reference_no = NULL)
    {
        if( parent::complete($token, $reference_no))
        {
            $result = array();

            $holdingAccount = $this->_request->getHoldingAccount();
            if( $holdingAccount instanceof HoldingAccount )
            {
                $result['balance']['initial'] = $holdingAccount->getBalance()->getValue() - $this->_request->getAmount();
                $result['balance']['new'] = $holdingAccount->getBalance()->getValue();
            }

            return $result;
        }

        return false;
    }

    protected function _requestAction(HoldingAccountRequest $request)
    {
        //todo check if the transactionID to be voided exists, and not voided before!
        if( $request->getAmount() < 0 )
        {//
            $holdingAccount_serv = $this->_getHoldingAccountService();

            //void
            if( $holdingAccount_serv->void($request->getHoldingAccount(),
                                    $request->getAmount(),
                                    $request->getModuleCode(),
                                    $request->getTransactionID()) )
            {
                return true;
            }

            $this->setResponseCode($holdingAccount_serv->getResponseCode());
            return false;
        }
        else //nothing to be done
            return true;
    }

    protected function _cancelAction(HoldingAccountRequest $request)
    {//nothing to be done
        if( $request->getAmount() < 0 )
        {
            $holdingAccount_serv = $this->_getHoldingAccountService();

            //utilise
            return $holdingAccount_serv->revert($request->getHoldingAccount(),
                                         $request->getAmount(),
                                         $request->getModuleCode(),
                                         $request->getTransactionID());
        }
        else //nothing to be done
            return true;
    }

    protected function _completeAction(HoldingAccountRequest $request)
    {//to credit to HoldingAccount

        if( $request->getAmount() < 0 )
            return true;
        else //positive means collection
        {
            $holdingAccount_serv = $this->_getHoldingAccountService();

            //void
            if( $holdingAccount_serv->void($request->getHoldingAccount(),
                                    $request->getAmount(),
                                    $request->getModuleCode(),
                                    $request->getTransactionID()) )
            {
                return true;
            }

            $this->setResponseCode($holdingAccount_serv->getResponseCode());
            return false;
        }
    }

    //no transaction to be created
    protected function _getCorporateService($country_currency_code)
    {
        return false;
    }

    protected function _checkActiveRequest(HoldingAccountRequest $currentRequest)
    {
        if( $currentRequest->getAmount() > 0 )  //if to credit money
            return parent::_checkActiveRequest($currentRequest);

        //otherwise, it's ok to have multiple request
        return true;
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        return $sc_serv->getByCode(RequestType::VOID, RequestType::getSystemGroupCode());
    }

    protected function _validateRequest(HoldingAccountRequest $request)
    {
        $v = BidirectionalHoldingAccountRequestValidator::make($request);
        $this->setResponseCode($v->getErrorCode());
        return !$v->fails();
    }
}