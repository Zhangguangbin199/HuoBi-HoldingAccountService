<?php

use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\HoldingAccountService\Common\UserCorporateServiceExtendedService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\Common\FunctionCode;

class Admin_corporate_service extends Admin_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_model');
        $this->_serv = new UserCorporateServiceExtendedService($this->_getIpAddress());
    }


}