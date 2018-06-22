<?php

namespace Iapps\HoldingAccountService\HoldingAccountConfig;

use Iapps\Common\Validator\IappsValidator;

class HoldingAccountConfigValidator extends IappsValidator
{

    protected $holding_account_config_collection;

    public static function make(HoldingAccountConfigCollection $holding_account_config_collection)
    {
        $v = new static();

        $v->holding_account_config_collection = $holding_account_config_collection;
        $v->validate();

        return $v;
    }

    public function validate()
    {
        $this->isFailed = true;

        if ($this->_validateFields())
            $this->isFailed = false;
    }

    protected function _validateFields()
    {
        if ($this->holding_account_config_collection instanceof HoldingAccountConfigCollection) {
            foreach ($this->holding_account_config_collection as $col) {
                if ($col instanceof HoldingAccountConfig) {
                    if ($col->getModuleCode() == NULL ||
                        !$this->_validateBool($col->getIsSupported())
                    ) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }


    protected function _validateBool($value)
    {//not sure what to validate for now
        return $value == 0 || $value == 1;
    }

}