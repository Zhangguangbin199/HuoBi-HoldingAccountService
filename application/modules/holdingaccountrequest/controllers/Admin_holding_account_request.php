<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestRepository;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\HoldingAccountRequest\PaymentInfoValidator;
use Iapps\HoldingAccountService\Common\FunctionCode;
use Iapps\HoldingAccountService\HoldingAccountRequest\AdminHoldingAccountPayment;
use Iapps\HoldingAccountService\HoldingAccountRequest\VoidHoldingAccountRequestService;

class Admin_holding_account_request extends Admin_Base_Controller{

    protected $repo;
    protected $paymentInterface;
    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccountrequest/Holding_account_request_model');
        $this->repo = new HoldingAccountRequestRepository($this->Holding_account_request_model);
    }

}