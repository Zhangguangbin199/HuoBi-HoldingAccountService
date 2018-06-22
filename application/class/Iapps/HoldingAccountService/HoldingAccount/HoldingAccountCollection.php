<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Core\IappsBaseEntityCollection;
use Iapps\Common\SystemCode\SystemCode;

class HoldingAccountCollection extends IappsBaseEntityCollection{

    public function getUserProfileIds()
    {
        $userIds = array();

        foreach( $this AS $holdingAccount )
        {
            if( $holdingAccount instanceof HoldingAccount )
            {
                if( $userId = $holdingAccount->getUserProfileId() )
                {
                    if( !in_array($userId, $userIds) )
                        $userIds[] = $userId;
                }
            }
        }

        return $userIds;
    }
}