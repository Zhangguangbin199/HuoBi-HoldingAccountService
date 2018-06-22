<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigCollection;
use Iapps\HoldingAccountService\ValueObject\HoldingAccountValue;
use Iapps\HoldingAccountService\ValueObject\HoldingAccountValueFactory;

class HoldingAccount extends IappsBaseEntity{

    protected $holdingAccountID;
    protected $user_profile_id;
    protected $reference_id;
    protected $holding_account_type;
    protected $country_currency_code;
    protected $is_active = 0;   //by default is inactive
    protected $balance = 0;

    protected $holding_account_configs;

    function __construct()
    {
        parent::__construct();

        $this->holding_account_type = new SystemCode();
        $this->balance = HoldingAccountValueFactory::build();
        $this->holding_account_configs = new HoldingAccountConfigCollection();
    }

    public function setHoldingAccountID($holdingAccountID)
    {
        $this->holdingAccountID = $holdingAccountID;
        return $this;
    }

    public function getHoldingAccountID()
    {
        return $this->holdingAccountID;
    }

    public function setUserProfileId($user_profile_id)
    {
        $this->user_profile_id = $user_profile_id;
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->user_profile_id;
    }

    public function setReferenceId($reference_id)
    {
        $this->reference_id = $reference_id;
        return $this;
    }

    public function getReferenceId()
    {
        return $this->reference_id;
    }

    public function setHoldingAccountType(SystemCode $code)
    {
        $this->holding_account_type = $code;
        return $this;
    }

    public function getHoldingAccountType()
    {
        return $this->holding_account_type;
    }

    public function setCountryCurrencyCode($code)
    {
        $this->country_currency_code = $code;
        return $this;
    }

    public function getCountryCurrencyCode()
    {
        return $this->country_currency_code;
    }

    public function setIsActive($active)
    {
        $this->is_active = $active;
        return $this;
    }

    public function getIsActive()
    {
        return $this->is_active;
    }

    public function setBalance(HoldingAccountValue $value)
    {
        $this->balance = $value;
        return $this;
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function setHoldingAccountConfigs(HoldingAccountConfigCollection $holding_account_configs)
    {
        $this->holding_account_configs = $holding_account_configs;
        return $this;
    }

    public function getHoldingAccountConfigs()
    {
        return $this->holding_account_configs;
    }


    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['holdingAccountID'] = $this->getHoldingAccountID();
        $json['user_profile_id'] = $this->getUserProfileId();
        $json['reference_id'] = $this->getReferenceId();
        $json['holding_account_type'] = $this->getHoldingAccountType()->getCode();
        $json['country_currency_code'] = $this->getCountryCurrencyCode();
        $json['balance'] = $this->getBalance()->getValue();
        $json['is_active'] = $this->getIsActive();
        $json['holding_account_config'] = $this->getHoldingAccountConfigs();

        return $json;
    }

    public function addBalance($value)
    {
        if( $this->getIsActive() )
        {
            $initial_value = $this->getBalance()->getValue();
            $this->getBalance()->setValue($initial_value + $value);
            return true;
        }

        return false;
    }

    public function deductBalance($value)
    {
        if( $this->getIsActive() )
        {
            $initial_value = $this->getBalance()->getValue();;
            if( ($initial_value - $value) >= 0.0 )
            {
                $this->getBalance()->setValue($initial_value - $value);
                return true;
            }
        }

        return false;
    }


    public function isCountryCurrencyCode($code)
    {
        return ($this->getCountryCurrencyCode() == $code);
    }

    public function isType($type)
    {
        return ($this->getHoldingAccountType()->getCode() == $type);
    }

    public function activate()
    {
        $this->setIsActive(1);
        return $this;
    }

    public function deactivate()
    {
        $this->setIsActive(0);
        return $this;
    }
}