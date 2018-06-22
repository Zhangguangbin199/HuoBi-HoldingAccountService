<?php

namespace Iapps\HoldingAccountService\HoldingAccountConfig;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsBaseEntity;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigRepository;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\Common\Microservice\CountryService\Country;

class HoldingAccountConfigService extends IappsBaseService{

    public function getHoldingAccountConfigList($limit, $page)
    {
        if( $object = $this->getRepository()->findAll($limit, $page) )
        {
            if( $object->result instanceof HoldingAccountConfigCollection )
            {
                $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_CONFIG_SUCCESS);
                $object->result = $object->result->toArray();
                return $object;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_CONFIG_FAILED);
        return false;
    }

    public function getHoldingAccountConfigByFilter(HoldingAccountConfig $holding_account_config, $limit=null, $page=null)
    {
        if( $object = $this->getRepository()->findAllByFilter($holding_account_config, $limit, $page) )
        {
            if( $object->result instanceof HoldingAccountConfigCollection )
            {
                $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_CONFIG_SUCCESS);
                return $object;
            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_CONFIG_FAILED);
        return false;
    }

    public function getHoldingAccountConfigInfo($country_currency_code)
    {
        if( $countryInfo = $this->getRepository()->findByCountryCurrencyCode($country_currency_code) )
        {
            $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_CONFIG_SUCCESS);
            return $countryInfo;
        }

        $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_CONFIG_FAILED);
        return false;
    }

    public function createHoldingAccountConfig(HoldingAccount $holdingAccount, HoldingAccountConfigCollection $holdingAccountConfigCol)
    {
        if ($holdingAccountConfigCol) {
            //validate holding account config
            $v = HoldingAccountConfigValidator::make($holdingAccountConfigCol);

            if (!$v->fails()) {

                foreach ($holdingAccountConfigCol AS $config) {

                    //assign an id
                    $config->setId(GuidGenerator::generate());
                    $config->setCreatedBy($this->getUpdatedBy());
                    $config->setHoldingAccountId($holdingAccount->getId());

                    if ($this->getRepository()->insert($config)) {
                        //dispatch event to auditLog
                        $this->fireLogEvent('iafb_holding_account.holding_account_config', AuditLogAction::CREATE, $holdingAccount->getId());
                    } else {
                        $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_CONFIG_FAILED);
                        return false;
                    }
                }

                $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_CONFIG_SUCCESS);
                return true;
            }
        }


        $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_CONFIG_FAILED);
        return false;
    }
}