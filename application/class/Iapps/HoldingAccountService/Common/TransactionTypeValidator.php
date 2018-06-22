<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\SystemCode\SystemCodeObject;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;

class TransactionTypeValidator {

    public static function validate($code)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->validateSystemCode($code, new TransactionType());
    }

    public static function getById($id)
    {
        $systemcode = SystemCodeServiceFactory::build();
        return $systemcode->getById($id);
    }
}
