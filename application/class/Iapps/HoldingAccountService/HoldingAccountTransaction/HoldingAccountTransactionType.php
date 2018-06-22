<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\SystemCode\SystemCodeInterface;

class HoldingAccountTransactionType implements SystemCodeInterface{

    const TOPUP = 'topup';
    const WITHDRAWAL = 'withdrawal';

    public static function getSystemGroupCode()
    {
        return 'transaction_type';
    }
}