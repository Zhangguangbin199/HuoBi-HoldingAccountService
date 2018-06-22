<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequestValidator;

use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;
use Iapps\Common\Validator\IappsValidator;

class HoldingAccountRequestValidator extends IappsValidator{

    protected $request;
    public static function make(HoldingAccountRequest $request)
    {
        $v = new HoldingAccountRequestValidator();
        $v->request = $request;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = false;

        if( !$this->_validateTransactionID() )
            $this->isFailed = true;

        if( !$this->_validateAmount() )
            $this->isFailed = true;
    }

    protected function _validateAmount(){
        if($this->request->getAmount() > 0.0)
        {
            return true;
        }

        $this->setErrorCode(MessageCode::CODE_INVALID_REQUEST_AMOUNT);
        return false;
    }

    protected function _validateTransactionID(){
        if($this->request->getModuleCode() != NULL AND
           $this->request->getTransactionID() != NULL )
        {
            return true;
        }

        $this->setErrorCode(MessageCode::CODE_INVALID_TRANSACTIONID);
        return false;
    }

}