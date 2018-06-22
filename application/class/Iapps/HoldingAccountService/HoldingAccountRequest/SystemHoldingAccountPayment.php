<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Microservice\PaymentService\AdminPaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentService;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\SystemPaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentUserType;

class SystemHoldingAccountPayment implements HoldingAccountPaymentInterface{

    protected $_lastResponse;
    protected $_clientResponse;
    protected $_paymentService;

    public function setPaymentService(PaymentService $paymentService)
    {
        $this->_paymentService = $paymentService;
        return $this;
    }

    public function getPaymentService()
    {
        if( $this->_paymentService == NULL )
        {
            $this->_paymentService = SystemPaymentServiceFactory::build();
            return $this->_paymentService;
        }

        return $this->_paymentService;
    }

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    public function setClientResponse(array $response)
    {
        $this->_clientResponse = $response;
        return $this;
    }

    public function getClientResponse()
    {
        return $this->_clientResponse;
    }

    public function paymentRequest(HoldingAccountRequest $request, array $paymentInfo)
    {
        $payment_amount = isset($paymentInfo['collection_amount']) ? $paymentInfo['collection_amount'] : $paymentInfo['amount'];

        $option = array();
        if( isset($paymentInfo['option']) )
            $option = $paymentInfo['option'];
        $option['HoldingAccount_request_token'] = $request->getRequestToken();
        $option['user_type'] = PaymentUserType::USER;

        $payment_service = $this->getPaymentService();
        if( $request_id = $payment_service->requestPayment(
            $request->getHoldingAccount()->getUserProfileId(),
            $request->getPaymentCode(),
            $request->getToCountryCurrencyCode(),
            $payment_amount,
            $request->getModuleCode(),
            $request->getTransactionID(),
            $option) )
        {
            $this->_lastResponse = $payment_service->getLastResponse();
            $request->setPaymentRequestId($request_id);
            return true;
        }

        $this->_lastResponse = $payment_service->getLastResponse();
        return false;
    }

    public function paymentComplete(HoldingAccountRequest $request, array $response = array())
    {
        $response = $this->getClientResponse();
        //$response['reference_no'] = $request->getReferenceNo();

        $payment_service = $this->getPaymentService();
        $result = $payment_service->completePayment(
            $request->getHoldingAccount()->getUserProfileId(),
            $request->getPaymentRequestId(),
            $request->getPaymentCode(),
            $this->getClientResponse());

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }

    public function paymentCancel(HoldingAccountRequest $request)
    {
        $payment_service = $this->getPaymentService();
        $result = $payment_service->cancelPayment(
            $request->getHoldingAccount()->getUserProfileId(),
            $request->getPaymentRequestId(),
            $request->getPaymentCode());

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }
}