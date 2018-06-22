<?php

namespace Iapps\HoldingAccountService\Common;

class IncrementIDGenerator{

    public static function generate($attribute)
    {
        $inc_serv = IncrementIDServiceFactory::build();
        return $inc_serv->getIncrementID($attribute);
    }
}
