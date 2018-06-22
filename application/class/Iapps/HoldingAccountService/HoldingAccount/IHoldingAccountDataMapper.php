<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IHoldingAccountDataMapper extends IappsBaseDataMapper{

    public function findByUserProfileId($user_profile_id);
    public function insertHoldingAccount(HoldingAccount $holding_account);
    public function updateHoldingAccountValue(HoldingAccount $holding_account);
    public function updateHoldingAccount(HoldingAccount $holding_account);
    public function findByParam(HoldingAccount $holding_account);
    public function findByReferenceIdArr(array $reference_id_arr, $limit=null, $page=null);
}