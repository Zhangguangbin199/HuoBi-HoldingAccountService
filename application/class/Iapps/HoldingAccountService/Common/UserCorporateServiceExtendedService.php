<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\HoldingAccountService\Common\TransactionType;
use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\CorporateService\CorporateServiceFeeService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;

class UserCorporateServiceExtendedService extends IappsBasicBaseService{

    public function getTopUpChannel($country_currency_code, $self_service = false)
    {
        return $this->getChannel(TransactionType::CODE_TOP_UP, $country_currency_code, PaymentDirection::IN, $self_service);
    }

    public function getWithdrawalChannel($country_currency_code, $self_service = false)
    {
        return $this->getChannel(TransactionType::CODE_WITHDRAW, $country_currency_code, PaymentDirection::OUT, $self_service);
    }

    protected function getChannel($transaction_type, $country_currency_code, $direction, $self_service = false)
    {
        $corp_serv = CorporateServServiceFactory::build();
        if( $corp = $corp_serv->findByTransactionTypeAndCountryCurrencyCode($transaction_type, $country_currency_code) )
        {
            if( $result = $corp_serv->getCorpServiceFeeByCorpServId($corp->getId(), $direction, NULL, $self_service) )
            {
                $result->info = $corp->getSelectedField(array('id', 'name', 'country_currency_code'));

                $this->setResponseCode(CorporateServiceFeeService::CODE_GET_CORPORATE_SERVICE_FEE_SUCCESS);
                return $result;
            }
        }

        $this->setResponseCode(MessageCode::CODE_SERVICE_NOT_FOUND);
        return false;
    }
}