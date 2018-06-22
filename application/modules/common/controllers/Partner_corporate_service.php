<?php

use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\HoldingAccountService\Common\UserCorporateServiceExtendedService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\Common\FunctionCode;

class Partner_corporate_service extends Partner_Base_Controller{

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_model');
        $this->_serv = new UserCorporateServiceExtendedService($this->_getIpAddress());
    }

    public function getTopUpChannel()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_WORKCREDIT_TOPUP) )
            return false;

        if( !$this->is_required($this->input->get(), array('country_currency_code')) )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        $code =  $this->input->get('country_currency_code');
        if( $result = $this->_serv->getWorkCreditTopUpChannel($code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getWithdrawalChannel()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_WORKCREDIT_WITHDRAWAL) )
            return false;

        if( !$this->is_required($this->input->get(), array('country_currency_code')) )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        $code =  $this->input->get('country_currency_code');
        if( $result = $this->_serv->getWorkCreditWithdrawalChannel($code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getDepositChannel()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::PARTNER_DEPOSIT) )
            return false;

        if( !$this->is_required($this->input->get(), array('country_currency_code')) )
            return false;

        $this->_serv->setUpdatedBy($user_id);

        $code =  $this->input->get('country_currency_code');
        if( $result = $this->_serv->getDepositChannel($code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}