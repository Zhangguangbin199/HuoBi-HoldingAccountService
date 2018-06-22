<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CoreConfigData\CoreConfigDataRepository;
use Iapps\Common\CoreConfigData\CoreConfigDataService;

class CoreConfigDataServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('common/Core_config_data_model');
            $repo = new CoreConfigDataRepository($_ci->Core_config_data_model);
            self::$_instance = new CoreConfigDataService($repo);
        }

        return self::$_instance;
    }
}