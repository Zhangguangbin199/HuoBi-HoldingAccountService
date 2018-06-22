<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Validator\IappsValidator;
use Iapps\HoldingAccountService\Common\MessageCode;

class NilPaymentInfoValidator extends PaymentInfoValidator{

    public function validate()
    {
        $this->isFailed = true;

        if( isset($this->data['payment_code']) AND
            isset($this->data['amount']) )
        {
            if( $this->data['amount'] == 0.0 )
                $this->isFailed = false;
        }

        $this->setErrorCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
    }
}