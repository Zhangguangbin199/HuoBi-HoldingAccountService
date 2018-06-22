<?php

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountRepository;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountService;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\InputValidator;

class System_holding_account extends System_Base_Controller{

    protected $_service;
    protected $repo;

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccount/Holding_account_model');
        $this->repo = new HoldingAccountRepository($this->Holding_account_model);
        $this->_service = new HoldingAccountService($this->repo);
        $this->_service->setIpAddress(IpAddress::fromString($this->_getIpAddress()));
    }

    public  function  test()
    {
    	$holdingAccountServ =  $this->_service;
    	$holdingAccountServ->test();
    }
    public function createHoldingAccount(){
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id', 'reference_id', 'holding_account_type', 'country_currency_code', 'config_info')) )
            return false;

        if( !$config_info = json_decode($this->input->post('config_info'), true) )
        {
            $errMsg = InputValidator::getInvalidParamMessage('config_info');
            $this->_response(InputValidator::constructInvalidParamResponse($errMsg));
            return false;
        }

        $user_profile_id = $this->input->post('user_profile_id');
        $reference_id = $this->input->post('reference_id');
        $holding_account_type = $this->input->post('holding_account_type');
        $country_currency_code = $this->input->post('country_currency_code');


        $this->_service->setUpdatedBy($systemUser);

        $holdingAccount = new HoldingAccount();
        $holdingAccount->setUserProfileId($user_profile_id);
        $holdingAccount->getHoldingAccountType()->setCode($holding_account_type);
        $holdingAccount->setCountryCurrencyCode($country_currency_code);
        $holdingAccount->setReferenceId($reference_id);

        $holdingAccountServ = HoldingAccountServiceFactory::build($holding_account_type);
        if ($obj = $holdingAccountServ->createHoldingAccount($holdingAccount, $config_info)) {

            $this->_respondWithSuccessCode($holdingAccountServ->getResponseCode(), array('result' => $obj));
            return true;
        }

        $this->_respondWithCode($holdingAccountServ->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getUserHoldingAccounts()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('user_profile_id')) )
            return false;

        $user_profile_id = $this->input->get('user_profile_id');

        $this->_serv = new HoldingAccountService($this->repo, $this->_getIpAddress(), $systemUser);

        if( $holding_accounts_currency = $this->_serv->getHoldingAccountsCurrency($user_profile_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $holding_accounts_currency));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

}