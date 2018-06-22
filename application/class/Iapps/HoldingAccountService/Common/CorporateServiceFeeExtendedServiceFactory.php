<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\HoldingAccountService\Common\CorporateServiceFeeExtendedService;

require_once __DIR__ . '/../../../../modules/common/models/Corporate_service_fee_model.php';

class CorporateServiceFeeExtendedServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( CorporateServiceFeeExtendedServiceFactory::$_instance == NULL )
        {
            $dm = new \Corporate_service_fee_model();
            $repo = new CorporateServiceFeeRepository($dm);
            CorporateServiceFeeExtendedServiceFactory::$_instance = new CorporateServiceFeeExtendedService($repo, "iafb_holding_account.corporate_service_fee");
        }

        return CorporateServiceFeeExtendedServiceFactory::$_instance;
    }
}