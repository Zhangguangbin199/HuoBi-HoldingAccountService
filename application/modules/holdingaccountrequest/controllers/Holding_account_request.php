<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\CollectionHoldingAccountRequestService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestRepository;
use Iapps\HoldingAccountService\HoldingAccountRequest\SystemHoldingAccountPayment;

/*
 * This controller will be called regardless of client,
 * Minimum valid access token is needed
 */
class Holding_account_request extends Base_Controller{

    protected $repo;
    protected $paymentInterface;

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccountrequest/Holding_account_request_model');
        $this->repo = new HoldingAccountRequestRepository($this->Holding_account_request_model);
        $this->paymentInterface = new SystemHoldingAccountPayment();
    }

    public function requestHoldingAccountCollection()
    {
        if( !$admin_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('user_profile_id',
            'module_code',
            'transactionID',
            'country_currency_code',
            'amount')) )
        {
            return false;
        }

        $user_id = $this->input->post('user_profile_id');
        $module_code = $this->input->post('module_code');
        $transactionID = $this->input->post('transactionID');
        $country_currency_code = $this->input->post('country_currency_code');
        $amount = $this->input->post('amount');
        $is_collection = $this->input->post('is_collection') ? $this->input->post('is_collection') : false; //default is for refund

        $holding_account_type = $this->input->post('holding_account_type');
        $reference_id = $this->input->post('reference_id');
        $payment_code = $this->input->post('payment_code');

        $this->_serv = new CollectionHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $admin_id, $this->paymentInterface);
        $this->_serv->setIsCollection($is_collection);
        if( $result = $this->_serv->request($user_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_code, $module_code, $transactionID) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function completeHoldingAccountCollection()
    {
        if( !$admin_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');
        $is_collection = $this->input->post('is_collection') ? $this->input->post('is_collection') : false; //default is for refund

        $this->_serv = new CollectionHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $admin_id, $this->paymentInterface);
        $this->_serv->setIsCollection($is_collection);

        if( $result = $this->_serv->complete($token) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function cancelHoldingAccountCollection()
    {
        if( !$admin_id = $this->_getUserProfileId() )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');
        $is_collection = $this->input->post('is_collection') ? $this->input->post('is_collection') : false; //default is for refund

        $this->_serv = new CollectionHoldingAccountRequestService($this->repo,$this->_getIpAddress(), $admin_id, $this->paymentInterface);
        $this->_serv->setIsCollection($is_collection);

        if( $this->_serv->cancel($token) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }
}