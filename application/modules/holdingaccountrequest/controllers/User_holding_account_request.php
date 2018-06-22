<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestRepository;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\HoldingAccountService\HoldingAccountRequest\TopupHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\WithdrawalHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\UtilizeHoldingAccountRequestService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\HoldingAccountRequest\PaymentInfoValidator;
use Iapps\HoldingAccountService\HoldingAccountRequest\UserHoldingAccountPayment;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;

class User_holding_account_request extends User_Base_Controller
{

    protected $repo;
    protected $paymentInterface;

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccountrequest/Holding_account_request_model');
        $this->repo = new HoldingAccountRequestRepository($this->Holding_account_request_model);
        $this->paymentInterface = new UserHoldingAccountPayment();
    }

    public function requestUtilise()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
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

        $this->_serv = new UtilizeHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);

        if( $result = $this->_serv->request($user_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_code, $module_code, $transactionID) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function completeUtilise()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new UtilizeHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);
        $this->_serv->setUserProfileId($user_id);
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
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new UtilizeHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);
        $this->_serv->setUserProfileId($user_id);
        if( $this->_serv->cancel($token) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

}