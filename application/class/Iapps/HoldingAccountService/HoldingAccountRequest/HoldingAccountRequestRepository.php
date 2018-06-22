<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Core\IappsBaseRepository;

class HoldingAccountRequestRepository extends IappsBaseRepository{

    public function findByToken($token)
    {
        return $this->getDataMapper()->findByToken($token);
    }

    public function findByHoldingAccountId($holding_account_id, $from_date, $to_date, $requestType = array(), $status = array())
    {

        return $this->getDataMapper()->findByHoldingAccountId($holding_account_id, $from_date, $to_date, $requestType, $status);
    }

    public function findActiveByHoldingAccountId($holding_account_id)
    {
        return $this->getDataMapper()->findActiveByHoldingAccountId($holding_account_id);
    }

    public function findActiveByUser($user_profile_id)
    {
        return $this->getDataMapper()->findActiveByUser($user_profile_id);
    }

    public function findByParam(HoldingAccountRequest $holdingAccountRequest)
    {
        return $this->getDataMapper()->findByParam($holdingAccountRequest);
    }

    public function findHoldingRequest(HoldingAccountRequest $holdingAccountRequest, $limit=null, $page=null)
    {
        return $this->getDataMapper()->findHoldingRequest($holdingAccountRequest, $limit, $page);
    }

    public function findByTransactionID($module_code, $transactionID)
    {
        return $this->getDataMapper()->findByTransactionID($module_code, $transactionID);
    }

    public function findExpiredRequest()
    {
        return $this->getDataMapper()->findExpiredRequest();
    }

    public function insertRequest(HoldingAccountRequest $request)
    {
        return $this->getDataMapper()->insertRequest($request);
    }

    public function updateRequestStatus(HoldingAccountRequest $request)
    {
        return $this->getDataMapper()->updateRequestStatus($request);
    }
}