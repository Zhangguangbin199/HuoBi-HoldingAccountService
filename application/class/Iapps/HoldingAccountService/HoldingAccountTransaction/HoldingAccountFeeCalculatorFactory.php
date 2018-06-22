<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\PaymentModeType;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;

class HoldingAccountFeeCalculatorFactory{

    protected static $_instance;

    public static function build($payment_mode = NULL)
    {
        if( self::$_instance == NULL )
        {
            switch($payment_mode)
            {
                default:
                    self::$_instance = new HoldingAccountFeeCalculator();
            }
        }

        return self::$_instance;
    }

    public static function reset()
    {
        self::$_instance = NULL;
    }
}