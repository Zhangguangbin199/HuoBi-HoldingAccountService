<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestRepository;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestAutoCancelService;
use Iapps\Common\Helper\RequestHeader;
use Iapps\HoldingAccountService\HoldingAccountRequest\SystemHoldingAccountPayment;
use Iapps\HoldingAccountService\HoldingAccountRequest\SystemHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\NilPaymentInfoValidator;
use Iapps\HoldingAccountService\HoldingAccountRequest\UtilizeHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\CashInHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\CommisionHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\AgentHoldingAccountPayment;
use Iapps\HoldingAccountService\HoldingAccountRequest\TopupHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\WithdrawalHoldingAccountRequestService;

class System_holding_account_request extends System_Base_Controller{

    protected $repo;
    protected $paymentInterface;

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccountrequest/Holding_account_request_model');
        $this->repo = new HoldingAccountRequestRepository($this->Holding_account_request_model);
        $this->paymentInterface = new SystemHoldingAccountPayment();
    }

    public function autoCancelRequest()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        RequestHeader::set(ResponseHeader::FIELD_X_AUTHORIZATION, $this->clientToken);

        $this->_serv = new HoldingAccountRequestAutoCancelService($this->repo, $this->_getIpAddress(), $systemUser);
        if( $this->_serv->process() )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function requestUtilise()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('module_code',
            'transactionID',
            'country_currency_code',
            'amount',
            'holding_account_type',
            'reference_id',
        )) )
        {
            return false;
        }

        $module_code = $this->input->post('module_code');
        $transactionID = $this->input->post('transactionID');
        $country_currency_code = $this->input->post('country_currency_code');
        $amount = $this->input->post('amount');
        $holding_account_type = $this->input->post('holding_account_type');
        $reference_id = $this->input->post('reference_id');
        $payment_code = $this->input->post('payment_code');

        $this->_serv = new UtilizeHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $systemUser, $this->paymentInterface);

        if( $result = $this->_serv->request($systemUser, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_code, $module_code, $transactionID) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function completeUtilise()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new UtilizeHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $systemUser, $this->paymentInterface);
        $this->_serv->setUserProfileId($systemUser);
        if( $result = $this->_serv->complete($token) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }


    public function cancelUtilise()
    {
        if( !$systemUser = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new UtilizeHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $systemUser, new SystemHoldingAccountPayment());

        if( $this->_serv->cancel($token) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }


}