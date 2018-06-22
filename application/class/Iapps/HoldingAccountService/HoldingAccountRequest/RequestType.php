<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\SystemCode\SystemCodeInterface;

class RequestType implements SystemCodeInterface{

    const TOPUP = 'topup';
    const WITHDRAWAL = 'withdrawal';
    const UTILISE = 'utilise';
    const COLLECTION = 'collection';
    const REFUND = 'refund';

    public static function getSystemGroupCode()
    {
        return 'holding_account_request_type';
    }
}