<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;

class HoldingAccountCreationListener extends BroadcastEventConsumer{

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            $serv = new HoldingAccountCreationService();
            $serv->setUpdatedBy($this->getUpdatedBy());
            $serv->setIpAddress($this->getIpAddress());
            $this->setForceAcknowledgement(false);
            return $serv->createHoldingAccount($data->user_profile_id);
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    /*
     * app users will have holding account(s)
     */
    public function listenEvent()
    {
        $this->listen('account.appuser.created', NULL, 'holdingaccount.queue.createHoldingAccount');
        $this->listen('account.appuser.converted', NULL, 'holdingaccount.queue.createHoldingAccount');
    }
}