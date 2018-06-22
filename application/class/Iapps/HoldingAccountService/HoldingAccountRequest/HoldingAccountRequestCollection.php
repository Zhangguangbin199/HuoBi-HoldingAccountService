<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Core\IappsBaseEntityCollection;

class HoldingAccountRequestCollection extends IappsBaseEntityCollection{

    public function getSum()
    {
        $sum = 0;
        foreach($this AS $request)
            $sum += $request->getAmount();

        return $sum;
    }
}