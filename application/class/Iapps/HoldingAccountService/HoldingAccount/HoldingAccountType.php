<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\SystemCode\SystemCodeInterface;

class HoldingAccountType implements SystemCodeInterface{

    const BORROWER_ACCOUNT = 'borrower';
    const LOAN_ACCOUNT = 'loan';
    const PERSONAL_ACCOUNT = 'personal';
    const CRYPTOCURRENCY_ACCOUNT = 'cryptocurrency';

    public static function getSystemGroupCode()
    {
        return 'holding_account_type';
    }
}