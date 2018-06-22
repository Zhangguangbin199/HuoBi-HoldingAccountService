<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\SystemCode\SystemCodeRepository;
use Iapps\Common\SystemCode\SystemCodeService;

class SystemCodeServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('common/Systemcode_model');
            $repo = new SystemCodeRepository($_ci->Systemcode_model);
            self::$_instance = new SystemCodeService($repo);
        }

        return self::$_instance;
    }
}