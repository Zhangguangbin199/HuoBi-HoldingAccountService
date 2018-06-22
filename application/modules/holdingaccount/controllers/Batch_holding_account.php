<?php

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountCreationListener;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;

class Batch_holding_account extends System_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccount/Holding_account_model');
    }

    public function listenHoldingAccountCreationQueue()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new HoldingAccountCreationListener();
        $listener->setUpdatedBy($systemUser);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->listenEvent();
        return true;
    }

    public function listenWorkCreditCreationQueue()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new WorkCreditListener();
        $listener->setUpdatedBy($systemUser);
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->listenEvent();
        return true;
    }
}