<?php

namespace Iapps\HoldingAccountService\HoldingAccountConfig;

use Iapps\Common\Microservice\ModuleCode;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;

class HoldingAccountConfig extends IappsBaseEntity
{
    protected $module_code;
    protected $holding_account_id;
    protected $is_supported = 1;

    function __construct()
    {
        parent::__construct();
    }

    public function setHoldingAccountId($holding_account_id)
    {
        $this->holding_account_id = $holding_account_id;
        return $this;
    }

    public function getHoldingAccountId()
    {
        return $this->holding_account_id;
    }


    public function setModuleCode($module_code)
    {
        $this->module_code = $module_code;
        return $this;
    }

    public function getModuleCode()
    {
        return $this->module_code;
    }

    public function setIsSupported($is_supported)
    {
        $this->is_supported = $is_supported;
        return $this;
    }

    public function getIsSupported()
    {
        return $this->is_supported;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['module_code'] = $this->getModuleCode();
        $json['is_supported'] = (bool)$this->getIsSupported();
        $json['holding_account_id'] = $this->getHoldingAccountId();

        return $json;
    }

    public function generateDefaultConfig()
    {
        return array(
            array("module_code"=>ModuleCode::LOAN_MODULE,"is_supported"=>1)
        );
    }

}