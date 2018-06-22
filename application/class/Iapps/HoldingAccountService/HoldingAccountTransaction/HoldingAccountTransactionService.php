<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Microservice\PromoCode\PromoCodeClientFactory;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionService;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\Common\Transaction\TransactionItemService;
use Iapps\Common\Microservice\PromoCode\PromoCodeClient;
use Iapps\HoldingAccountService\Common\TransactionType;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestRepository;
use Iapps\HoldingAccountService\HoldingAccountRequest\UserHoldingAccountPayment;
use Iapps\HoldingAccountService\HoldingAccountRequest\WithdrawalHoldingAccountRequestService;
use Iapps\SalaryService\Payment\PaymentInterface;


class HoldingAccountTransactionService extends TransactionService{

    protected $_holdingAccountRequest;

    function __construct(IappsBaseRepository $trans_repo,
                         TransactionItemService $trans_item_serv,
                         SystemCodeService $syscode_serv)
    {
        parent::__construct($trans_repo, $trans_item_serv, $syscode_serv);

        $this->_holdingAccountRequest = new HoldingAccountRequest();
    }

    public function setHoldingAccountRequest(HoldingAccountRequest $holdingAccountRequest)
    {
        $this->_holdingAccountRequest = $holdingAccountRequest;
        return $this;
    }

    public function getHoldingAccountRequest()
    {
        return $this->_holdingAccountRequest;
    }

    public function findByTransactionID($transactionID)
    {
        if( $trx = parent::findByTransactionID($transactionID) )
        {
            $_serv = HoldingAccountTransactionItemServiceFactory::build();
            foreach($trx->getItems() AS $item)
            {
                $_serv->getItemInfo($item);
            }

            return $trx;
        }

        return false;
    }

    public function getTransactionDetail(Transaction $transaction ,$limit, $page)
    {
        if( $result = parent::getTransactionDetail($transaction, $limit, $page) ) {
            if($result->transaction->getTransactionType()->getCode() == TransactionType::CODE_WITHDRAW) {
                //reverse direction
                foreach($result->transaction_items as $key=>$data){
                    $data->setUnitPrice(abs($data->getUnitPrice()));
                }

                $hoaInfo = array();

                $_ci = get_instance();
                $_ci->load->model('holdingaccountrequest/Holding_account_request_model');
                $repo = new HoldingAccountRequestRepository($_ci->Holding_account_request_model);

                $withdrawalServ = new WithdrawalHoldingAccountRequestService($repo,$this->getIpAddress()->getString(), $this->getUpdatedBy(), new UserHoldingAccountPayment());

                if($detail = $withdrawalServ->findRequest($transaction->getTransactionID())){
                    $hoaInfo['request'] = $detail;
                }

                if($result->payment) {
                    $result->payment[0]->amount = abs($result->payment[0]->amount);
                }

                $result->hoa = $hoaInfo;
            }
            $result->transaction->setItems($result->transaction_items);

            $this->setResponseCode(self::CODE_GET_TRANSACTION_ITEM_HISTORY_SUCCESS);
            return $result;
        }

        $this->setResponseCode(self::CODE_GET_TRANSACTION_ITEM_HISTORY_FAILED);
        return false ;
    }

    public function saveTransaction(Transaction $transaction)
    {
        if( !($transaction instanceof HoldingAccountTransaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_TRANSACTION_SUCCESS);
            return false;
        }

        //get item type if needed
        Logger::debug('HoldingAccount.transaction: getting item type - ' . $transaction->getId());

        foreach($transaction->getItems() AS $item)
        {
            if( $item->getItemType()->getId() == NULL )
            {
                if( !$itemType = $this->_syscode_serv->getByCode($item->getItemType()->getCode(), ItemType::getSystemGroupCode()) )
                {
                    $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_TRANSACTION_FAILED);
                    return false;
                }
                $item->setItemType($itemType);
            }
        }

        Logger::debug('HoldingAccount.transaction: saving into database - ' . $transaction->getId());
        if( parent::saveTransaction($transaction) )
        {
            $this->fireLogEvent('iafb_holding_account.transaction', AuditLogAction::CREATE, $transaction->getId());

            Logger::debug('HoldingAccount.transaction: saving into database success - ' . $transaction->getId());
            return true;
        }

        return false;
    }

    public function completeTransaction(Transaction $transaction)
    {
        $ori_transaction = clone($transaction);
        if( parent::completeTransaction($transaction) ) {
            if ($this->_completeAction()) {

                foreach($transaction->getItems() AS $item)
                {
                    if( $item->getItemType()->getCode() == ItemType::DISCOUNT )
                    {
                        $promoServ = PromoCodeClientFactory::build(2);
                        if( !$promoServ->apply($item->getItemId()) )
                        {
                            Logger::debug('Failed to apply promo code' . $transaction->getId());
                            return false;
                        }

                    }
                }

                HoldingAccountTransactionEventProducer::publishTransactionCreated($transaction->getId());
                $this->fireLogEvent('iafb_holding_account.transaction', AuditLogAction::UPDATE, $transaction->getId(), $ori_transaction);
                return true;
            }
        }

        return false;
    }

    public function cancelTransaction(Transaction $transaction)
    {
        $ori_transaction = clone($transaction);
        if( parent::cancelTransaction($transaction) )
        {
            $this->fireLogEvent('iafb_holding_account.transaction', AuditLogAction::UPDATE, $transaction->getId(), $ori_transaction);

            if ($this->_cancelAction()) {
                return true;
            }
        }

        return false;
    }


    protected function _completeAction()
    {
        //do nothing
        return true;
    }

    protected function _cancelAction()
    {
        //do nothing
        return true;
    }

    protected function _getHoldingAccountService()
    {
        $holdingAccount_service =  HoldingAccountServiceFactory::build();
        $holdingAccount_service->setUpdatedBy($this->getUpdatedBy());
        $holdingAccount_service->setIpAddress($this->getIpAddress());
        return $holdingAccount_service;
    }
    
    
    
}