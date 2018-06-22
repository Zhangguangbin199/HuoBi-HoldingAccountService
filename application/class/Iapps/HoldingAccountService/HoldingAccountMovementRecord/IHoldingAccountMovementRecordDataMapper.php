<?php

namespace Iapps\HoldingAccountService\HoldingAccountMovementRecord;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IHoldingAccountMovementRecordDataMapper extends IappsBaseDataMapper{

    public function findByHoldingAccountId($holding_account_id);
    public function findByHoldingAccountIds(array $ids);
    public function insertRecord(HoldingAccountMovementRecord $movement);
    public function findByParam(HoldingAccountMovementRecord $movement ,$limit ,$page);

}