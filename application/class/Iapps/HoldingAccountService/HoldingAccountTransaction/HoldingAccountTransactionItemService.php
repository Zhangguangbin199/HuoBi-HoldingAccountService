<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\BillService\Common\CorporateServiceFeeExtendedServiceFactory;
use Iapps\HoldingAccountService\Common\CorporateServicePaymentModeFeeServiceFactory;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\Common\Transaction\TransactionItemService;
use Iapps\HoldingAccountService\Common\CorporateServiceFeeServiceFactory;

class HoldingAccountTransactionItemService extends TransactionItemService{

    public function getItemInfo(HoldingAccountTransactionItem $item)
    {
        switch( $item->getItemType()->getCode() )
        {
            case ItemType::CORPORATE_SERVICE:
                $_serv = CorporateServServiceFactory::build();
                if( $info = $_serv->getCorporateService($item->getItemId()) )
                {
                    $item->setItemInfo($info);
                }
                break;
            case ItemType::CORPORATE_SERVICE_FEE:
            case ItemType::PAYMENT_FEE:
                $_serv = CorporateServicePaymentModeFeeServiceFactory::build();
                if( $info = $_serv->getPaymentModeFee($item->getItemId()) )
                {
                    $item->setItemInfo($info);
                }
                break;
            default:
                break;
        }

        return $item;
    }
}