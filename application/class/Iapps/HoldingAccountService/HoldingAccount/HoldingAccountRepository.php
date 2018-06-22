<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Core\IappsBaseRepository;
use Iapps\HoldingAccountService\Common\CacheKey;

class HoldingAccountRepository extends IappsBaseRepository{

    protected $defaultCacheKey = CacheKey::HOLDING_ACCOUNT_ID;
    protected $_removeCacheHoldingAccount = null;

    public function findByUserProfileId($user_profile_id)
    {
        $cache_key = CacheKey::HOLDING_ACCOUNT_USER_PROFILE_ID . $user_profile_id;
        if( !$result = $this->getElasticCache($cache_key) )
        {
            if( $result = $this->getDataMapper()->findByUserProfileId($user_profile_id) )
            {
                $this->setElasticCache($cache_key, $result);
            }
        }

        return $result;
    }

    public function insertHoldingAccount(HoldingAccount $holding_account)
    {
        //need to remove cache also if there is a newly added holding account under same user profile
        $this->_removeCaches($holding_account);
        return $this->getDataMapper()->insertHoldingAccount($holding_account);
    }

    public function updateHoldingAccountValue(HoldingAccount $holding_account)
    {
        if( $result = $this->getDataMapper()->updateHoldingAccountValue($holding_account) )
        {
            $this->_removeCaches($holding_account);
        }

        return $result;
    }

    public function updateHoldingAccount(HoldingAccount $holding_account)
    {
        if( $result = $this->getDataMapper()->updateHoldingAccount($holding_account) )
        {
            $this->_removeCaches($holding_account);
        }

        return $result;
    }


    protected function _removeCaches(HoldingAccount $holding_account)
    {
        $this->_removeCacheHoldingAccount = $holding_account;

        $cache_keys = array(
            CacheKey::HOLDING_ACCOUNT_ID . $holding_account->getId()
        );

        //remove caches
        foreach($cache_keys AS $key)
        {
            $this->deleteElastiCache($key);
        }
    }

    public function findByParam(HoldingAccount $holding_account)
    {//can't use cache for dynamic select
        return $this->getDataMapper()->findByParam($holding_account);
    }

    public function findByReferenceIdArr(array $reference_id_arr, $limit=null, $page=null)
    {//can't use cache for dynamic select
        return $this->getDataMapper()->findByReferenceIdArr($reference_id_arr, $limit, $page);
    }

    public function onDBTransactionCompleted()
    {
        //removed cached again on db completed to make sure
        if( $this->_removeCacheHoldingAccount instanceof HoldingAccount )
            $this->_removeCaches($this->_removeCacheHoldingAccount);

        return parent::onDBTransactionCompleted();
    }
}