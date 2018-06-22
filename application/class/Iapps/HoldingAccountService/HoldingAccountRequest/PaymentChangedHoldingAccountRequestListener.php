<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\CountryService\CountryServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentStatus;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;

class PaymentChangedHoldingAccountRequestListener extends BroadcastEventConsumer{

    protected $header = array();

    public function setHeader(array $header)
    {
        $this->header = $header;
        return $this;
    }

    public function getHeader()
    {
        return $this->header;
    }

    protected function doTask($msg)
    {
        $data = json_decode($msg->body);

        try
        {
            $serv = PaymentChangedHoldingAccountRequestServiceFactory::build();
            $serv->setUpdatedBy($this->getUpdatedBy());
            $serv->setIpAddress($this->getIpAddress());

            //check payment
            $payment_serv = PaymentServiceFactory::build();
            if ($paymentInfo =  $payment_serv->getPaymentByTransactionID($data->module_code, $data->transactionID)) {
                if(property_exists($paymentInfo, "result")) {
                    if (count($paymentInfo->result) > 0) {
                        //check payment status
                        if ($paymentInfo->result[0]->status == PaymentStatus::COMPLETE) {
                            //complete
                            $serv->complete($data->module_code, $data->transactionID);
                            return true;
                        }
                    }
                }
            }

            //if not found treat as cancel
            $serv->cancel($data->module_code, $data->transactionID);
            return true;

        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    /*
     * listen for payment request change
     */
    public function listenEvent()
    {
        $this->listen('payment.request.changed', NULL, 'holdingaccount.queue.updateRequest');
    }
}