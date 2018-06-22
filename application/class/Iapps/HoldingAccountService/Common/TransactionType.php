<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\SystemCode\SystemCodeInterface;

class TransactionType implements SystemCodeInterface{

    const CODE_TOP_UP       = 'topup';
    const CODE_WITHDRAW      = 'withdrawal';

    public static function getSystemGroupCode()
    {
        return 'transaction_type';
    }
}