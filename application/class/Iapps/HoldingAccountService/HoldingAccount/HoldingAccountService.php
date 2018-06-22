<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigServiceFactory;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigCollection;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigService;
use Iapps\HoldingAccountService\HoldingAccountMovementRecord\HoldingAccountMovementRecordServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountMovementRecord\MovementType;
use Iapps\HoldingAccountService\Common\IncrementIDGenerator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestStatementServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequest\RequestStatus;

class HoldingAccountService extends IappsBaseService{

    protected $_holdingAccountType = HoldingAccountType::PERSONAL_ACCOUNT;

    protected $_holdingAccountConfigService;

    public function setHoldingAccountType($holding_account_type)
    {
        $this->_holdingAccountType = $holding_account_type;
    }

    public function setHoldingAccountConfigService(HoldingAccountConfigService $_holdingAccountConfigService)
    {
        $this->_holdingAccountConfigService = $_holdingAccountConfigService;
    }

    public function getHoldingAccountConfigService()
    {
        if( !$this->_holdingAccountConfigService )
        {
            $this->_holdingAccountConfigService = HoldingAccountConfigServiceFactory::build();
        }

        return $this->_holdingAccountConfigService;
    }

    public function findById($holding_account_id)
    {
        if( $holding_account = $this->getRepository()->findById($holding_account_id) )
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);
            return $holding_account;
        }

        //holding account not found
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function hasHoldingAccount($user_profile_id, array $country_currency_codes)
    {
        if( $holding_accounts = $this->getHoldingAccounts($user_profile_id, false) )
        {
            $c = $holding_accounts->getCountryCurrencies();
            foreach($country_currency_codes AS $country_curency_code)
            {
                if( !in_array($country_curency_code, $c) )
                {
                    //holding account not found
                    $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
                    return false;
                }
            }

            //holding account found
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);
            return true;
        }

        //holding account not found
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function findHoldingAccount(HoldingAccount $holdingAccount)
    {
        $holdingAccount->setIsActive(1);
        if( $holdingAccountType = $this->_getHoldingAccountType() )
        {
            $holdingAccount->setHoldingAccountType($holdingAccountType);
        }
        if( $info = $this->getRepository()->findByParam($holdingAccount) )
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);
            return $info->result->current();
        }

        //holding account not found
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function getHoldingAccounts($user_profile_id)
    {
        //use this for better performance
        if( $info = $this->getRepository()->findByUserProfileId($user_profile_id) )
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);

            return $info;
        }

        //holding account not found
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function getHoldingAccountsCurrency($user_profile_id)
    {
        if( $info = $this->getRepository()->findByUserProfileId($user_profile_id) )
        {
            $holding_accounts = $info->result->getSelectedField(array('id', 'holding_account_type', 'holdingAccountID', 'country_currency_code', 'is_active'));
            $count_holding_accounts = count($holding_accounts);

            $holding_accounts_array = array();
            $holding_account_type = '';
            $holding_account_array = array();
            for ($i = 0; $i < $count_holding_accounts; $i++)
            {
                if (!empty($holding_account_type) && $holding_account_type != $holding_accounts[$i]['holding_account_type']) {
                    $holding_accounts_array[] = array('holding_account_type' => $holding_account_type,
                                                'currency' => $holding_account_array);
                    $holding_account_type = '';
                    $holding_account_array = array();
                }

                $holding_account_type = $holding_accounts[$i]['holding_account_type'];
                $holding_account_array[] = array('holding_account_id' => $holding_accounts[$i]['id'],
                                        'country_currency_code' => $holding_accounts[$i]['country_currency_code'],
                                        'holdingAccountID' => $holding_accounts[$i]['holdingAccountID'],
                                        'is_active' => $holding_accounts[$i]['is_active']);

            }
            // last batch of holding_account_type data after for-loop
            $holding_accounts_array[] = array('holding_account_type' => $holding_account_type,
                                        'currency' => $holding_account_array);

            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);
            return $holding_accounts_array;
        }

        //holding account not found
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function createHoldingAccount(HoldingAccount $holdingAccount, $configInfo = array())
    {
        Logger::debug('holdingaccount.create: creating - ' . $holdingAccount->getUserProfileId() . ' - ' . $holdingAccount->getReferenceId());

        if( !$holdingAccountType = $this->_getHoldingAccountType() )
        {
            Logger::debug('holdingaccount.create: invalid holding account type - ' . $holdingAccount->getUserProfileId() . ' - ' . $holdingAccount->getReferenceId());
            return false;
        }
        $holdingAccount->setHoldingAccountType($holdingAccountType);
        $holdingAccount->setCreatedBy($this->getUpdatedBy());

        $holdingAccountConfigCol = new HoldingAccountConfigCollection();
        foreach($configInfo as $config){
            $holdingAccountConfig = new HoldingAccountConfig();
            $holdingAccountConfig->setModuleCode($config['module_code']);
            $holdingAccountConfig->setIsSupported(!isset($config['is_supported']) || $config['is_supported'] ? 1 : 0);
            $holdingAccountConfigCol->addData($holdingAccountConfig);
        }
        if( !$this->searchByFilter($holdingAccount) ) {//only can proceed if holding account does not exists

            $this->getRepository()->startDBTransaction();

            $this->getHoldingAccountConfigService()->setIpAddress($this->getIpAddress());
            $this->getHoldingAccountConfigService()->setUpdatedBy($this->getUpdatedBy());

            $holdingAccount->setId(GuidGenerator::generate());
            if ($holdingAccountID = IncrementIDGenerator::generate($holdingAccountType->getCode() . 'HOAID')) {
                $holdingAccount->setHoldingAccountID($holdingAccountID);
            }

            if ($this->getRepository()->insertHoldingAccount($holdingAccount) &&
                $this->getHoldingAccountConfigService()->createHoldingAccountConfig($holdingAccount, $holdingAccountConfigCol)
            ) {
                $this->fireLogEvent('iafb_holding_account.holding_account', AuditLogAction::CREATE, $holdingAccount->getId());

                //activate holding account if required
                if( !$this->_activateHoldingAccount($holdingAccount) )
                {
                    Logger::debug('holdingaccount.create: failed to activate - ' . $holdingAccount->getUserProfileId() . ' - ' . $holdingAccount->getReferenceId());

                    $this->getRepository()->rollbackDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_CREATE_FAILED);
                    return false;
                }

                $this->getRepository()->completeDBTransaction();
                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_CREATE_SUCCESS);

                //HoldingAccountCreatedEventProducer::publishHoldingAccountCreated($reference_id, $user_profile_id);

                Logger::debug('holdingaccount.create: success - ' . $holdingAccount->getUserProfileId() . ' - ' . $holdingAccount->getReferenceId());

                return $holdingAccount;
            }

            $this->getRepository()->rollbackDBTransaction();
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_CREATE_FAILED);
            return false;

        }

        Logger::debug('holdingaccount.create: already exists - ' . $holdingAccount->getUserProfileId() . ' - ' . $holdingAccount->getReferenceId());

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_ALREADY_EXISTS);
        return false;
    }

    public function searchByFilter(HoldingAccount $holding_account, $page = 1, $limit = MAX_VALUE)
    {
        if( $info = $this->getRepository()->findByParam($holding_account) )
        {
            $holding_accounts = $info->result;
            $holding_accounts = $holding_accounts->pagination($limit, $page);

            $holdingAccountConfigServ = $this->getHoldingAccountConfigService();
            $holdingAccountCol = new HoldingAccountCollection();
            foreach($holding_accounts->result as $holding_account){
                $holdingAccountConfig = new HoldingAccountConfig();
                $holdingAccountConfig->setHoldingAccountId($holding_account->getId());
                if ($holdingAccountConfig = $holdingAccountConfigServ->getHoldingAccountConfigByFilter($holdingAccountConfig)) {
                    $holding_account->setHoldingAccountConfigs($holdingAccountConfig->result);
                }
                $holdingAccountCol->addData($holding_account);
            }
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);
            $holding_accounts->result = $holdingAccountCol;
            return $holding_accounts;
        }

        //holding account not found
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function activate($holding_account_id)
    {
        if( $holding_account = $this->findById($holding_account_id) )
        {
            if( $holding_account instanceof HoldingAccount)
            {
                $ori_holding_account = clone($holding_account);

                $holding_account->activate();
                $holding_account->setUpdatedBy($this->getUpdatedBy());

                if( $this->getRepository()->updateHoldingAccount($holding_account) )
                {
                    $this->fireLogEvent('iafb_holding_account.holding_account', AuditLogAction::UPDATE, $holding_account->getId(), $ori_holding_account);

                    $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_UPDATE_SUCCESS);
                    return true;
                }

                $this->getRepository()->rollbackDBTransaction();
                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_UPDATE_FAILED);
                return false;
            }
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function setDefault($user_profile_id, $holding_account_id)
    {
        if( $holding_accounts = $this->getHoldingAccounts($user_profile_id, false) )
        {
            if( $holding_accounts instanceof HoldingAccountCollection )
            {
                if( $holding_accounts->setDefault($holding_account_id) )
                {
                    $this->getRepository()->startDBTransaction();

                    foreach($holding_accounts AS $holding_account)
                    {
                        $holding_account->setUpdatedBy($this->getUpdatedBy());
                        if( !$this->getRepository()->updateHoldingAccount($holding_account) )
                        {
                            $this->getRepository()->rollbackDBTransaction();

                            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_UPDATE_FAILED);
                            return false;
                        }
                    }

                    //update
                    $this->getRepository()->completeDBTransaction();
                    $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_UPDATE_SUCCESS);
                    return true;
                }

                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_UPDATE_FAILED);
                return false;
            }
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);
        return false;
    }

    public function topUp(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        return $this->_addValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Top Up');
    }

    public function withdrawal(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        return $this->_deductValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Cash Out');
    }

    public function utilise(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        return $this->_deductValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Payment');
    }

    public function collect(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        return $this->_addValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Collection');
    }

    public function refund(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        return $this->_addValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Refund');
    }

    public function void(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        if( $amount > 0 )
            return $this->_addValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Void');
        else
            return $this->_deductValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Void');
    }

    public function revert(HoldingAccount $holding_account, $amount, $module_code, $transactionID)
    {
        return $this->_addValue($holding_account, $amount, $module_code, $transactionID, MovementType::BALANCE, 'Cancelled');
    }

    protected function _getHoldingAccountConfig($country_currency_code)
    {
        $configService = HoldingAccountConfigServiceFactory::build();

        return $configService->getHoldingAccountConfigInfo($country_currency_code);
    }

    protected function _getHoldingAccountType()
    {
        $sysServ = SystemCodeServiceFactory::build();

        return $sysServ->getByCode($this->_holdingAccountType, HoldingAccountType::getSystemGroupCode());
    }

    protected function _activateHoldingAccount(HoldingAccount $holding_account)
    {
        if( $result = $this->activate($holding_account->getId()) )
        {
            $holding_account->activate();
        }

        return $result;
    }

    protected function _addValue(HoldingAccount $holding_account, $amount, $module_code, $transactionID, $movementType, $description)
    {
        $ori_holding_account = clone($holding_account);

        if( $holding_account->addBalance(abs($amount)) )
        {
            $holding_account->setUpdatedBy($this->getUpdatedBy());
            $this->getRepository()->startDBTransaction();

            if( $this->getRepository()->updateHoldingAccountValue($holding_account) )
            {
                //add log
                $this->fireLogEvent('iafb_holding_account.holding_account', AuditLogAction::UPDATE, $holding_account->getId(), $ori_holding_account);

                //add movement record
                $movement_serv = HoldingAccountMovementRecordServiceFactory::build();
                $movement_serv->setUpdatedBy($this->getUpdatedBy());
                $movement_serv->setIpAddress($this->getIpAddress());
                if( $movement_serv->insertMovement($holding_account, $module_code, $transactionID, $movementType, $description) )
                {
                    $this->getRepository()->completeDBTransaction();
                    return $holding_account;
                }
            }

            $this->getRepository()->rollbackDBTransaction();
        }

        return false;
    }

    protected function _deductValue(HoldingAccount $holding_account, $amount, $module_code, $transactionID, $movementType, $description)
    {
        $ori_holding_account = clone($holding_account);

        if( $holding_account->deductBalance(abs($amount)) )
        {
            $holding_account->setUpdatedBy($this->getUpdatedBy());
            $this->getRepository()->startDBTransaction();

            if( $this->getRepository()->updateHoldingAccountValue($holding_account) )
            {
                //add log
                $this->fireLogEvent('iafb_holding_account.holding_account', AuditLogAction::UPDATE, $holding_account->getId(), $ori_holding_account);

                //add movement record
                $movement_serv = HoldingAccountMovementRecordServiceFactory::build();
                $movement_serv->setUpdatedBy($this->getUpdatedBy());
                $movement_serv->setIpAddress($this->getIpAddress());
                if( $movement_serv->insertMovement($holding_account, $module_code, $transactionID, $movementType, $description) )
                {
                    $this->getRepository()->completeDBTransaction();
                    return $holding_account;
                }
            }

            $this->getRepository()->rollbackDBTransaction();
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_INSUFFICIENT_FUND);
            return false;
        }

        $this->setResponseCode(MessageCode::CODE_DEDUCT_FUND_FAILED);
        return false;
    }

    public function getByReferenceIdArr(array $reference_ids,$limit,$page){
        if( $holding_account = $this->getRepository()->findByReferenceIdArr($reference_ids, $limit, $page) )
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_FOUND);
            return $holding_account;
        }
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_EXISTS);
        return false;
    }

    public function getHoldingAccountHistory($reference_ids,$limit,$page, $date_from=null ,$date_to=null)
    {

        if( $holding_account = $this->getByReferenceIdArr($reference_ids, $limit, $page) )
        {

            $holding_accountmove_service = HoldingAccountMovementRecordServiceFactory::build();
            $holding_accountmovement = new \Iapps\HoldingAccountService\HoldingAccountMovementRecord\HoldingAccountMovementRecord();

            $holding_accountmovement->setHoldingAccountIdArr($reference_ids);
            if($date_from) {
                $holding_accountmovement->setDateFrom(IappsDateTime::fromString($date_from . ' 00:00:00'));
            }
            if($date_to) {
                $holding_accountmovement->setDateTo(IappsDateTime::fromString($date_to . ' 23:59:59'));
            }

            if( $holding_accountmovementinfo = $holding_accountmove_service->getByParam($holding_accountmovement,$limit,$page) )
            {
                $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_HISTORY_SUCCESS);
                return $holding_accountmovementinfo;

            }
        }

        $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_HISTORY_NOT_FOUND);
        return false;
    }
    
    public function test()
    {
    	$bn = new \Moontoast\Math\BigNumber('9,223,372,036,854,775,808');
    	$bn->multiply(35);
    	$bn->setValue("1111111111111111111111");
    	$bn->add("1111111111111111111111");
    	var_dump($bn->getValue());
//     	var_dump($bn->getValue());
//     	var_dump($bn->convertToBase(16));

    }
    
    
}