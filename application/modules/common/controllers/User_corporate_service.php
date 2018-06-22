<?php

use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\HoldingAccountService\Common\UserCorporateServiceExtendedService;
use Iapps\Common\Helper\ResponseHeader;

class User_corporate_service extends User_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_model');
        $this->_serv = new UserCorporateServiceExtendedService($this->_getIpAddress());
    }

    public function getTopUpChannel()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('country_currency_code')) )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        $code =  $this->input->get('country_currency_code');
        if( $result = $this->_serv->getTopUpChannel($code, true) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getWithdrawalChannel()
    {
        if( !$user_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->get(), array('country_currency_code')) )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        $code =  $this->input->get('country_currency_code');
        if( $result = $this->_serv->getWithdrawalChannel($code, true) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}