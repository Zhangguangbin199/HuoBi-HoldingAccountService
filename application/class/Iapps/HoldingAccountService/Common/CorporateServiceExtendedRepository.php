<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CorporateService\CorporateServiceRepository;

class CorporateServiceExtendedRepository extends CorporateServiceRepository{

    public function findByTransactionTypeAndCountryCurrencyCode($transaction_type_id, $country_currency_code)
    {
        return $this->getDataMapper()->findByTransactionTypeAndCountryCurrencyCode($transaction_type_id, $country_currency_code);
    }
}