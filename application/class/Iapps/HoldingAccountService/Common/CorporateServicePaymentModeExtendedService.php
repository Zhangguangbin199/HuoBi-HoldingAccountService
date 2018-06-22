<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\Common\Microservice\AccountService\AccountService;

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\HoldingAccountService\Common\CorporateServiceFeeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionTypeValidator;
use Iapps\Common\CorporateService\CorporateServService;
use Iapps\HoldingAccountService\Common\TransactionType;
use Iapps\HoldingAccountService\Common\CurrencyCodeValidator;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServiceFeeRepository;
use Iapps\Common\CorporateService\CorporateServiceFeeService;
use Iapps\Common\CorporateService\CorporateServicePaymentModeService;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\HoldingAccountService\Common\CorporateServicePaymentModeServiceFactory;
use Iapps\HoldingAccountService\Common\CorporateServicePaymentModeFeeServiceFactory;
use Iapps\HoldingAccountService\Common\FeeTypeValidator;
use Iapps\HoldingAccountService\Common\FeeType;
use Iapps\Common\CorporateService\CorporateServiceFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\CorporateService\CorporateServicePaymentModeCollection;


class CorporateServicePaymentModeExtendedService extends CorporateServicePaymentModeService {

    public function getCorpServicePaymentModeWithFeeByCorpServId($corporate_service_id)
    {
        if ($object = $this->getSupportedPaymentMode($corporate_service_id)) {
            $result_array = $object->result->getSelectedField(array('id', 'direction', 'corporate_service_id', 'role_id', 'is_default', 'payment_code'));
            $count_result = count($result_array);

            //fee service factory
            $corp_serv_payment_mode_fee_serv = CorporateServicePaymentModeFeeServiceFactory::build();

            for ($i = 0; $i < $count_result; $i++) {
                if ($fee_object = $corp_serv_payment_mode_fee_serv->getPaymentModeFeeByCorporateServicePaymentModeId($result_array[$i]['id'])) {
                    $result_array[$i]['fee'] = $fee_object->result->toArray();
                }
            }

            return $result_array;
        }

        return false;
    }

}