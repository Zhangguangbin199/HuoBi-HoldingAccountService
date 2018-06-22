<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CorporateService\ICorporateServiceMapper;

interface ICorporateServiceExtendedDataMapper extends ICorporateServiceMapper{

    public function findByTransactionTypeAndCountryCurrencyCode($transaction_type_id, $country_currency_code);
}