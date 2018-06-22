<?php

namespace Iapps\HoldingAccountService\HoldingAccountConfig;

class HoldingAccountConfigServiceFactory{

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('holdingaccountconfig/Holding_account_config_model');
            $repo = new HoldingAccountConfigRepository($_ci->Holding_account_config_model);

            self::$_instance = new HoldingAccountConfigService($repo);
        }

        return self::$_instance;
    }
}