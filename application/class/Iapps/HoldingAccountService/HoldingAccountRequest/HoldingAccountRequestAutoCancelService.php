<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\SystemPaymentService;
use Iapps\Common\Microservice\PaymentService\SystemPaymentServiceFactory;
use Iapps\HoldingAccountService\Common\MessageCode;

class HoldingAccountRequestAutoCancelService extends IappsBaseService{

    protected static $_instance = array();
    protected $paymentInterface = NULL;

    protected function _getPaymentInterface()
    {
        if( $this->paymentInterface == NULL )
        {
            $this->paymentInterface = new SystemHoldingAccountPayment();
            $paymentService = SystemPaymentServiceFactory::build();
            $this->paymentInterface->setPaymentService($paymentService);
        }

        return $this->paymentInterface;
    }

    public function process()
    {
        if( $info = $this->getRepository()->findExpiredRequest() )
        {

            $expiredRequests = $info->result;

            foreach( $expiredRequests AS $request )
            {
                $this->_cancelByRequestType($request);
            }
        }

        $this->setResponseCode(MessageCode::CODE_AUTOCANCEL_PROCESSED);
        return true;
    }

    protected function _cancelByRequestType(HoldingAccountRequest $request)
    {
        if( $serv = $this->_getServiceByType($request->getRequestType()->getCode()) )
        {
            $serv->cancel($request->getRequestToken());
        }
    }

    protected function _getServiceByType($type)
    {
        if( !array_key_exists($type, self::$_instance ) )
        {
            $paymentInterface = $this->_getPaymentInterface();

            switch($type) {
                case RequestType::TOPUP:
                    self::$_instance[$type] = new TopupHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    break;
                case RequestType::UTILISE:
                    self::$_instance[$type] = new UtilizeHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    break;
                case RequestType::WITHDRAWAL:
                    self::$_instance[$type] = new WithdrawalHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    break;
                case RequestType::REFUND:
                    $serv = new CollectionHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    $serv->setIsCollection(false);
                    self::$_instance[$type] = new CollectionHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    break;
                case RequestType::COLLECTION:
                    $serv = new CollectionHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    $serv->setIsCollection(true);
                    self::$_instance[$type] = new CollectionHoldingAccountRequestService($this->getRepository(),$this->getIpAddress()->getString(), $this->getUpdatedBy(), $paymentInterface);
                    break;
                default:
                    return false;
            }
        }

        return self::$_instance[$type];
    }
}