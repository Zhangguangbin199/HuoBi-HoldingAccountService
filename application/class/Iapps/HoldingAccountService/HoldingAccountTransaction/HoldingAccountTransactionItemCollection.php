<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\Transaction\TransactionItemCollection;

class HoldingAccountTransactionItemCollection extends TransactionItemCollection{

    public function groupItemsBySpreadAndFee()
    {
        $collection = new HoldingAccountTransactionItemCollection();

        if( $main_item = $this->_getMainItem() )
        {
            $collection->addData($main_item);
        }

        if( $serviceFee = $this->_getServiceFeeItems() ) {
            $collection->addData($serviceFee);
        }

        if( $paymentFee = $this->_getPaymentFeeItems() ) {
            $collection->addData($paymentFee);
        }

        $this->_combineOtherItems($collection);

        return $collection;
    }

    protected function _getMainItem()
    {
        foreach($this AS $item)
        {
            if( $item instanceof HoldingAccountTransactionItem )
            {
                if( $item->isMainItem() )
                {
                    return $item;
                }
            }
        }

        return false;
    }

    protected function _getServiceFeeItems()
    {
        $fee = NULL;
        foreach($this AS $item)
        {
            if( $item instanceof HoldingAccountTransactionItem )
            {
                if( $item->isServiceFee() )
                {
                    if( $fee == NULL )
                    {
                        $fee = $item;
                        $fee->setName('Service Fee');
                        $fee->setDescription('Service Fee');
                    }
                    else
                    {
                        $fee->combineItem($item);
                    }
                }
            }
        }

        return $fee;
    }

    protected function _getPaymentFeeItems()
    {
        $fee = NULL;
        foreach($this AS $item)
        {
            if( $item instanceof HoldingAccountTransactionItem )
            {
                if( $item->isPaymentFee() )
                {
                    if( $fee == NULL )
                    {
                        $fee = $item;
                        $fee->setName('Payment Fee');
                        $fee->setDescription('Payment Fee');
                    }
                    else
                    {
                        $fee->combineItem($item);
                    }
                }
            }
        }

        return $fee;
    }

    protected function _combineOtherItems(HoldingAccountTransactionItemCollection $itemCol)
    {
        foreach($this AS $item)
        {
            if( $item instanceof HoldingAccountTransactionItem )
            {
                if( !$item->isMainItem() AND
                    !$item->isServiceFee() AND
                    !$item->isPaymentFee() )
                    $itemCol->addData($item);

            }
            else
                $itemCol->addData($item);
        }

        return $itemCol;
    }
}