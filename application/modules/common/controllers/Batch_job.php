<?php

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountGenerateReceiptListener;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\RequestHeader;
use Iapps\Common\Helper\ResponseHeader;

class Batch_job extends System_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccounttransaction/Holding_account_transaction_model');
    }

    public function listenGenerateReceipt()
    {
        if( !$system_user_id = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $listener = new HoldingAccountGenerateReceiptListener();
        $listener->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
        $listener->setUpdatedBy($system_user_id);
        $listener->listenEvent();
        return true;
    }
}