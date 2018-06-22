<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CorporateService\CorporateService;

class HoldingAccountCorporateService extends CorporateService{

    protected $min_value;
    protected $max_value;

    public function setMinValue($value)
    {
        $this->min_value = $value;
        return $this;
    }

    public function getMinValue()
    {
        return $this->min_value;
    }

    public function setMaxValue($value)
    {
        $this->max_value = $value;
        return $this;
    }

    public function getMaxValue()
    {
        return $this->max_value;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['min_value'] = $this->getMinValue();
        $json['max_value'] = $this->getMaxValue();

        return $json;
    }
}