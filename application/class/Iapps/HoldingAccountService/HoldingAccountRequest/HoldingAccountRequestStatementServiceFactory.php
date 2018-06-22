<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;


class HoldingAccountRequestStatementServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('holdingaccountrequest/holding_account_request_model');
            $repo = new HoldingAccountRequestRepository($_ci->holding_account_request_model);

            self::$_instance = new HoldingAccountRequestStatementService($repo);
        }

        return self::$_instance;
    }
}