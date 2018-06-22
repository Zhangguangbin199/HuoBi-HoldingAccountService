<?php

namespace Iapps\HoldingAccountService\HoldingAccountConfig;

use Iapps\Common\Core\IappsBaseDataMapper;

interface IHoldingAccountConfigDataMapper extends IappsBaseDataMapper{

	public function findAll($limit, $page);
    public function findAllByFilter(HoldingAccountConfig $config, $limit, $page);
    public function insert(HoldingAccountConfig $config);
    public function update(HoldingAccountConfig $config);
}