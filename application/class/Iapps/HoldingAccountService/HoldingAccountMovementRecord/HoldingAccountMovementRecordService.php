<?php

namespace Iapps\HoldingAccountService\HoldingAccountMovementRecord;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\Common\MessageCode;

class HoldingAccountMovementRecordService extends IappsBaseService{

    public function insertMovement(HoldingAccount $holdingAccount, $module_code, $transactionID, $movement_type, $description = null)
    {
        $movement = HoldingAccountMovementRecord::createFromHoldingAccount($holdingAccount, $module_code, $transactionID, $movement_type, $description);
        $movement->setCreatedBy($this->getUpdatedBy());
        //validate

        if( $this->getRepository()->insertRecord($movement) )
        {
            return true;
        }

        return false;
    }

    public function getByHoldingAccountIds(array $ids, IappsDateTime $fromTime, IappsDateTime $toTime)
    {
        $this->getRepository()->setFromCreatedAt($fromTime);
        $this->getRepository()->setToCreatedAt($toTime);
        if( $movement = $this->getRepository()->findByHoldingAccountIds($ids) )
        {
            return $movement;
        }

        return false;
    }
    
    public function getByParam(HoldingAccountMovementRecord $config, $limit = 100, $page = 1)
    {
        if( $movement = $this->getRepository()->findByParam($config, $limit, $page) )
        {                        
            $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_HISTORY_SUCCESS);
            return $movement;
        }
        $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_HISTORY_NOT_FOUND);
        return false;
    }
}