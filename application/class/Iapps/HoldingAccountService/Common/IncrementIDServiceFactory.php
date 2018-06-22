<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\IncrementID\IncrementIDRepository;
use Iapps\Common\IncrementID\IncrementIDService;

require_once __DIR__ . '/../../../../modules/common/models/Increment_id_model.php';

class IncrementIDServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( IncrementIDServiceFactory::$_instance == NULL )
        {
            $dm = new \Increment_id_model();
            $repo = new IncrementIDRepository($dm);
            IncrementIDServiceFactory::$_instance = new IncrementIDService($repo);
        }

        return IncrementIDServiceFactory::$_instance;
    }
}