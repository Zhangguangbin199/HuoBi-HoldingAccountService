<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\SystemCode\SystemCodeObject;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;

class FeeTypeValidator {

    public static function validate($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new FeeType());
    }
}
