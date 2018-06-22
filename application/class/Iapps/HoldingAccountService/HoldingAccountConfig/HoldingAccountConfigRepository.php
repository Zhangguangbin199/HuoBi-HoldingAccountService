<?php

namespace Iapps\HoldingAccountService\HoldingAccountConfig;

use Iapps\Common\Core\IappsBaseRepository;

class HoldingAccountConfigRepository extends IappsBaseRepository{

    public function findAll($limit, $page)
    {
        return $this->getDataMapper()->findAll($limit, $page);
    }

    public function findAllByFilter(HoldingAccountConfig $holding_account_config, $limit, $page)
    {
        return $this->getDataMapper()->findAllByFilter($holding_account_config, $limit, $page);
    }

    public function insert(HoldingAccountConfig $config)
    {
        return $this->getDataMapper()->insert($config);
    }

    public function update(HoldingAccountConfig $config)
    {
        return $this->getDataMapper()->update($config);
    }
}