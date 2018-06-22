<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

interface HoldingAccountPaymentInterface {
    function getLastResponse();
    function paymentRequest(HoldingAccountRequest $request, array $paymentInfo);
    function paymentComplete(HoldingAccountRequest $request, array $response = array());
    function paymentCancel(HoldingAccountRequest $request);
}