<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IHoldingAccountRequestDataMapper extends IappsBaseDataMapper{

    public function findByToken($token);
    public function findActiveByHoldingAccountId($holding_account_id);
    public function findActiveByUser($user_profile_id);
    public function findByParam(HoldingAccountRequest $holdingAccountRequest);
    public function findHoldingRequest(HoldingAccountRequest $holdingAccountRequest, $limit=null, $page=null);
    public function findByTransactionID($module_code, $transactionID);
    public function findExpiredRequest();
    public function findByHoldingAccountId($holding_account_id, $from_date, $to_date, $requestType = array(), $status = array());
    public function insertRequest(HoldingAccountRequest $request);
    public function updateRequestStatus(HoldingAccountRequest $request);
}
