<?php

use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\Common\FunctionCode;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;
use Iapps\Common\Microservice\AccountService\AccessType;

class Admin_holding_account extends Admin_Base_Controller
{

    protected $repo;
    protected $paymentInterface;

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccount/Holding_account_model');
    }

	/* @param :: holding_account_id (required)
	 * return :: Resoponse Message
	 * return :: boolean
	 */
    public function activateHoldingAccount() {
        if( !$admin_id = $this->_getUserProfileId(FunctionCode::ADMIN_ACTIVATE_HOLDING_ACCOUNT, AccessType::WRITE) )
            return false;

        if( !$this->is_required($this->input->post(), array('holding_account_id')) )
            return false;

        $holding_account_id = $this->input->post('holding_account_id');
        $this->_serv = HoldingAccountServiceFactory::build(HoldingAccountType::WORK_CREDIT);

        if( $result = $this->_serv->activate($holding_account_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
    
}
