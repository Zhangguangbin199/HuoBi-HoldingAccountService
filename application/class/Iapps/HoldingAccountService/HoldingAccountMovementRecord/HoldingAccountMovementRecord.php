<?php

namespace Iapps\HoldingAccountService\HoldingAccountMovementRecord;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\ValueObject\HoldingAccountValue;
use Iapps\HoldingAccountService\ValueObject\HoldingAccountValueFactory;
use Iapps\Common\Core\IappsDateTime;


class HoldingAccountMovementRecord extends IappsBaseEntity{

    protected $holding_account_id;
    protected $module_code;
    protected $transactionID;
    protected $movement_type;
    protected $amount;
    protected $last_balance;
    protected $date_from;
    protected $date_to;
    protected $description;


    protected $holding_account_id_arr = array();

    function __construct()
    {
        parent::__construct();
        
        $this->amount = HoldingAccountValueFactory::build();
        $this->last_balance = HoldingAccountValueFactory::build();
    }

    public function setHoldingAccountId($id)
    {
        $this->holding_account_id = $id;
        return $this;
    }

    public function getHoldingAccountId()
    {
        return $this->holding_account_id;
    }

    public function setModuleCode($code)
    {
        $this->module_code = $code;
        return $this;
    }

    public function getModuleCode()
    {
        return $this->module_code;
    }

    public function setTransactionID($id)
    {
        $this->transactionID = $id;
        return $this;
    }

    public function getTransactionID()
    {
        return $this->transactionID;
    }

    public function setMovementType($type)
    {
        $this->movement_type = $type;
        return $this;
    }

    public function getMovementType()
    {
        return $this->movement_type;
    }

    public function setAmount(HoldingAccountValue $value)
    {
        $this->amount = $value;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setLastBalance(HoldingAccountValue $value)
    {
        $this->last_balance = $value;
        return $this;
    }

    public function getLastBalance()
    {
        return $this->last_balance;
    }

    public function setDateFrom(IappsDateTime $date_from)
    {
        $this->date_from = $date_from;
        return $date_from;
    }

    public function getDateFrom()
    {
        return $this->date_from;
    }

    public function setDateTo(IappsDateTime $date_to)
    {
        $this->date_to = $date_to;
        return $date_to;
    }

    public function getDateTo()
    {
        return $this->date_to;
    }

    public function setDescription($desc)
    {
        $this->description = $desc;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setHoldingAccountIdArr(array $holding_account_id_arr)
    {
        $this->holding_account_id_arr = $holding_account_id_arr;
        return $this;
    }

    public function getHoldingAccountIdArr()
    {
        return $this->holding_account_id_arr;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['holding_account_id'] = $this->getHoldingAccountId();
        $json['module_code'] = $this->getModuleCode();
        $json['transactionID'] = $this->getTransactionID();
        $json['movement_type'] = $this->getMovementType();
        $json['amount'] = $this->getAmount()->getValue();
        $json['last_balance'] = $this->getLastBalance()->getValue();
        $json['description'] = $this->getDescription();

        return $json;
    }



    public static function createFromHoldingAccount(HoldingAccount $holdingAccount, $module_code, $transactionID, $movementType,
                                             $description = null)
    {
        $movement = new HoldingAccountMovementRecord();

        $movement->setId(GuidGenerator::generate());
        $movement->setHoldingAccountId($holdingAccount->getId());
        $movement->setMovementType($movementType);
        $movement->setModuleCode($module_code);
        $movement->setTransactionID($transactionID);
        $movement->setDescription($description);

        if( $movementType == MovementType::BALANCE )
        {
            $movement->getAmount()->setValue($holdingAccount->getBalance()->getMovementValue());
            $movement->getLastBalance()->setValue($holdingAccount->getBalance()->getValue());
        }
        elseif( $movementType == MovementType::DEPOSIT )
        {
            $movement->getAmount()->setValue($holdingAccount->getDeposit()->getMovementValue());
            $movement->getLastBalance()->setValue($holdingAccount->getDeposit()->getValue());
        }

        return $movement;
    }
}