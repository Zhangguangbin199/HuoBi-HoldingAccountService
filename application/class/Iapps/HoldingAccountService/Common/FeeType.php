<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\SystemCode\SystemCodeInterface;

class FeeType implements SystemCodeInterface{

	const CODE_SPREAD       = 'spread';
    const CODE_FEE       = 'fee';

    public static function getSystemGroupCode()
    {
        return 'fee_type';
    }
}