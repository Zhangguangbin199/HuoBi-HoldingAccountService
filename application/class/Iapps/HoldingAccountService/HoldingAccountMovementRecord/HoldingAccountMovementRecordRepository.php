<?php

namespace Iapps\HoldingAccountService\HoldingAccountMovementRecord;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Core\IappsDateTime;

class HoldingAccountMovementRecordRepository extends IappsBaseRepository{

    protected $frCreatedAt;
    protected $toCreatedAt;

    public function setFromCreatedAt(IappsDateTime $dt)
    {
        $this->frCreatedAt = $dt;
        $this->getDataMapper()->setFromCreatedAt($dt);
        return $this;
    }

    public function getFromCreatedAt()
    {
        return $this->frCreatedAt;
    }

    public function setToCreatedAt(IappsDateTime $dt)
    {
        $this->toCreatedAt = $dt;
        $this->getDataMapper()->setToCreatedAt($dt);
        return $this;
    }

    public function getToCreatedAt()
    {
        return $this->toCreatedAt;
    }

    public function findByHoldingAccountId($holding_account_id)
    {
        return $this->getDataMapper()->findByHoldingAccountId($holding_account_id);
    }

    public function findByHoldingAccountIds(array $ids)
    {
        return $this->getDataMapper()->findByHoldingAccountIds($ids);
    }

    public function insertRecord(HoldingAccountMovementRecord $movement)
    {
        return $this->getDataMapper()->insertRecord($movement);
    }
    
    public function findByParam(HoldingAccountMovementRecord $config ,$limit ,$page)
    {
        return $this->getDataMapper()->findByParam($config ,$limit ,$page);
    }
}