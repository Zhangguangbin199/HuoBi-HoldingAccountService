<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequestValidator;

use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;

class OutHoldingAccountRequestValidator extends HoldingAccountRequestValidator{

    public static function make(HoldingAccountRequest $request)
    {
        $v = new OutHoldingAccountRequestValidator();
        $v->request = $request;
        $v->validate();

        return $v;
    }

    protected function _validateAmount(){
        if($this->request->getAmount() < 0.0)
        {
            return true;
        }

        $this->setErrorCode(MessageCode::CODE_INVALID_REQUEST_AMOUNT);
        return false;
    }
}