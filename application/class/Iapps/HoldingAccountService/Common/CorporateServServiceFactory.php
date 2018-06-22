<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServService;

class CorporateServServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('common/Corporate_service_model');
            $repo = new CorporateServiceExtendedRepository($_ci->Corporate_service_model);
            self::$_instance = new CorporateServiceExtendedService($repo, "iafb_holding_account.corporate_service");
        }

        return self::$_instance;
    }
}