<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\PaymentUserType;

class UserHoldingAccountPayment implements HoldingAccountPaymentInterface{

    protected $_lastResponse;

    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    public function paymentRequest(HoldingAccountRequest $request, array $paymentInfo)
    {
        $payment_amount = isset($paymentInfo['collection_amount']) ? $paymentInfo['collection_amount'] : $paymentInfo['amount'];

        $option = array();
        if( isset($paymentInfo['option']) )
            $option = $paymentInfo['option'];
        $option['HoldingAccount_request_token'] = $request->getRequestToken();
        $option['user_type'] = PaymentUserType::USER;

        $payment_service = PaymentServiceFactory::build();
        if( $request_id = $payment_service->requestPaymentByUserSelf(
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
        $payment_service = PaymentServiceFactory::build();
        $result = $payment_service->completePaymentByUser(
            $request->getPaymentRequestId(),
            $request->getPaymentCode(),
            $response);

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }

    public function paymentCancel(HoldingAccountRequest $request)
    {
        $payment_service = PaymentServiceFactory::build();
        $result = $payment_service->cancelPaymentByUserSelf(
            $request->getPaymentRequestId(),
            $request->getPaymentCode());

        $this->_lastResponse = $payment_service->getLastResponse();
        return $result;
    }
}