<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

class HoldingAccountServiceFactory {

    protected static $_instance = array();

    public static function build($type = HoldingAccountType::PERSONAL_ACCOUNT)
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('holdingaccount/Holding_account_model');
            $repo = new HoldingAccountRepository($_ci->Holding_account_model);

            self::$_instance = new HoldingAccountService($repo);
            self::$_instance->setHoldingAccountType($type);
        }

        return self::$_instance;
    }
}