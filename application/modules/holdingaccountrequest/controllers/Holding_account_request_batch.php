<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\PaymentChangedHoldingAccountRequestListener;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;

class Holding_account_request_batch extends System_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccountrequest/Holding_account_request_model');
    }

    public function listenPaymentChangedHoldingAccountRequestQueue()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new PaymentChangedHoldingAccoutnRequestListener();
        $listener->setUpdatedBy($systemUser);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->listenEvent();
        return true;
    }
}