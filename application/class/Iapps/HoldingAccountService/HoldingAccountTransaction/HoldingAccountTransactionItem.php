<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\CorporateService\FeeType;
use Iapps\Common\Transaction\TransactionItem;

class HoldingAccountTransactionItem extends TransactionItem
{
    protected $itemInfo;

    public function setItemInfo(IappsBaseEntity $item)
    {
        $this->itemInfo = $item;
        return $this;
    }

    public function getItemInfo()
    {
        return $this->itemInfo;
    }

    public function isMainItem()
    {
        return ($this->getItemType()->getCode() == ItemType::CORPORATE_SERVICE);
    }

    public function isServiceFee()
    {
        return $this->getItemType()->getCode() == ItemType::CORPORATE_SERVICE_FEE;
    }

    public function isPaymentFee()
    {
        return $this->getItemType()->getCode() == ItemType::PAYMENT_FEE;
    }

    public function combineItem(HoldingAccountTransactionItem $item)
    {
        $this->setUnitPrice($this->getUnitPrice() + $item->getUnitPrice());
    }
}