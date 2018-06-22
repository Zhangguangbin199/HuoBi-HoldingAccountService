<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;

class HoldingAccountTransactionEventProducer extends BroadcastEventProducer{

    protected $transaction_id;

    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    public function getMessage()
    {
        $temp['transaction_id'] = $this->getTransactionId();
        return json_encode($temp);
    }

    public static function publishTransactionCreated($transaction_id)
    {
        $e = new HoldingAccountTransactionEventProducer();

        $e->setTransactionId($transaction_id);
        return $e->trigger(HoldingAccountTransactionEventType::HOLDING_ACCOUNT_TRANSACTION_CREATED, NULL, $e->getMessage());
    }
}