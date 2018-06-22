<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\Validator\IappsValidator;

class PasscodeValidator extends IappsValidator{

    protected $passcode;
    public static function make($passcode)
    {
        $v = new PasscodeValidator();
        $v->passcode = $passcode;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        if( $this->passcode != NULL )
        {
            if( is_numeric($this->passcode) )
            {
                if( strlen($this->passcode) == 6 )
                    $this->isFailed = false;
            }
        }
    }
}
