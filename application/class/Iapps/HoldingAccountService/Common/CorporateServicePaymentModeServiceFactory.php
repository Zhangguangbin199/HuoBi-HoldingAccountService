<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CorporateService\CorporateServicePaymentMode;
use Iapps\Common\CorporateService\CorporateServicePaymentModeRepository;
use Iapps\Common\CorporateService\CorporateServicePaymentModeService;

require_once __DIR__ . '/../../../../modules/common/models/Corporate_service_payment_mode_model.php';

class CorporateServicePaymentModeServiceFactory{

    protected static $_instance = NULL;

    public static function build()
    {
        if( CorporateServicePaymentModeServiceFactory::$_instance == NULL )
        {
            $dm = new \Corporate_service_payment_mode_model();
            $repo = new CorporateServicePaymentModeRepository($dm);
            CorporateServicePaymentModeServiceFactory::$_instance = new CorporateServicePaymentModeService($repo, "iafb_holding_account.corporate_service_payment_mode");
        }

        return CorporateServicePaymentModeServiceFactory::$_instance;
    }
}