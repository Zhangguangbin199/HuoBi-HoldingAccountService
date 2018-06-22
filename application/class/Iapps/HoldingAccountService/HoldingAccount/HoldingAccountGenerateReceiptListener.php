<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\HoldingAccountService\Common\HoldingAccountReceiptExportService;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionEventType;
use Iapps\Common\Helper\MessageBroker\BroadcastEventConsumer;
use Iapps\Common\Transaction\Transaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;

class HoldingAccountGenerateReceiptListener extends BroadcastEventConsumer
{
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
        $trx_serv = HoldingAccountTransactionServiceFactory::build();
        $pay_serv = PaymentServiceFactory::build();

        $data = json_decode($msg->body);
        try{
            //wait one second for the data to be commited to DB
            sleep(1);

            $transaction = new Transaction();
            $transaction->setId($data->transaction_id);
            $trx_serv->setPaymentService($pay_serv);
            $transactionDetail = $trx_serv->getTransactionDetail($transaction,PHP_INT_MAX, 1);

            if (empty($transactionDetail)){
                return false;
            }

            $receiptExportService = new HoldingAccountReceiptExportService();
            $viewPath = APPPATH . 'class/Iapps/HoldingAccountService/Export/ViewReceiptExportTopUpAndCashOut.php';
            $receiptExportService->generateAndNotifyUser($transactionDetail , $viewPath);
        } catch (\Exception $e){
            return false;
        }
    }

    /*
     * app users will have holding account(s)
     */
    public function listenEvent()
    {
        $this->listen(HoldingAccountTransactionEventType::HOLDING_ACCOUNT_TRANSACTION_CREATED, NULL, 'holdingaccount.queue.generateReceipt');
    }
}

