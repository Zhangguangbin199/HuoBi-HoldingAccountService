<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestRepository;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\HoldingAccountService\HoldingAccountRequest\TopupSelfHoldingAccountRequestService;
use Iapps\HoldingAccountService\HoldingAccountRequest\WithdrawalHoldingAccountRequestService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\HoldingAccountService\HoldingAccountRequest\PaymentInfoValidator;
use Iapps\HoldingAccountService\HoldingAccountRequest\UserHoldingAccountPayment;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;

class User_self_holding_account_request extends User_Base_Controller
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

    public function requestTopup()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->post(), array(
            'country_currency_code',
            'amount',
            'payment_info',
            'holding_account_type',
            'reference_id',
        )) )
        {
            return false;
        }

        $payment_info = $this->input->post('payment_info');
        $payment_info = json_decode($payment_info, true);
        $country_currency_code = $this->input->post('country_currency_code');
        $amount = $this->input->post('amount');
        $holding_account_type = $this->input->post('holding_account_type');
        $reference_id = $this->input->post('reference_id');

        $payment_validator = PaymentInfoValidator::make($payment_info);
        if ($payment_validator->fails()) {
            $this->_respondWithCode($payment_validator->getErrorCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $payment_code = $payment_info['payment_code'];

        $this->_serv = new TopupSelfHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);
        $this->_serv->setPaymentInfo($payment_info);
        $this->_serv->setSelfService(TRUE);

        if ($result = $this->_serv->request($user_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_code)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function completeTopup()
    {
        if( !$agent_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->post(), array('request_token')) )
        {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new TopupSelfHoldingAccountRequestService($this->repo,$this->_getIpAddress(), $agent_id, $this->paymentInterface);

        if( $result = $this->_serv->complete($token) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function cancelTopup()
    {
        if (!$agent_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION))
            return false;

        if (!$this->is_required($this->input->post(), array('request_token'))) {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new TopupSelfHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $agent_id, $this->paymentInterface);

        if ($this->_serv->cancel($token)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function retrieveActiveWithdrawalRequest()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        $this->_serv = new WithdrawalHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);

        if( $result = $this->_serv->retrieveActiveRequest($user_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function retrieveAllWithdrawalRequest()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->get(), array('holding_account_id')) )
        {
            return false;
        }

        $holding_account_id = $this->input->get('holding_account_id');
        $limit = $this->_getLimit();
        $page = $this->_getPage();

        $this->_serv = new WithdrawalHoldingAccountRequestService($this->repo,$this->_getIpAddress(), $user_id, $this->paymentInterface);

        if( $resultColl = $this->_serv->retrieveAllRequest($holding_account_id, $user_id, $limit, $page) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $resultColl->result,  'total' => $resultColl->total));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function requestWithdrawal()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION) )
            return false;

        if( !$this->is_required($this->input->post(), array(
            'country_currency_code',
            'amount',
            'collection_info',
            'holding_account_type',
            'reference_id'
            )) )
        {
            return false;
        }
        $country_currency_code = $this->input->post('country_currency_code');
        $amount = $this->input->post('amount');
        $collection_info = $this->input->post('collection_info');
        $collection_info = json_decode($collection_info, true);
        $holding_account_type = $this->input->post('holding_account_type');
        $reference_id = $this->input->post('reference_id');

        $payment_validator = PaymentInfoValidator::make($collection_info);
        if( $payment_validator->fails() )
        {
            $this->_respondWithCode($payment_validator->getErrorCode(), ResponseHeader::HEADER_NOT_FOUND);
            return false;
        }

        $payment_code = $collection_info['payment_code'];

        $passcode = $this->input->post('passcode') ? $this->input->post('passcode') : NULL;

        $this->_serv = new WithdrawalHoldingAccountRequestService($this->repo,$this->_getIpAddress(), $user_id, $this->paymentInterface);
        $this->_serv->setPaymentInfo($collection_info);
        $this->_serv->setPasscode($passcode);

        if( $result = $this->_serv->request($user_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_code) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function completeWithdrawal()
    {
        if (!$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION))
            return false;

        if (!$this->is_required($this->input->post(), array('request_token'))) {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new WithdrawalHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);

        $collection_info = $this->input->post('collection_info') ? $this->input->post('collection_info') : NULL;
        if( $collection_info )
        {
            $collection_info = json_decode($collection_info, true);

            $payment_validator = PaymentInfoValidator::make($collection_info);
            if( $payment_validator->fails() )
                return false;

            $this->_serv->setPaymentInfo($collection_info);
        }

        if ($result = $this->_serv->complete($token)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result));
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());
        return false;
    }

    public function cancelWithdrawal()
    {
        if (!$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::TRANSACTION))
            return false;

        if (!$this->is_required($this->input->post(), array('request_token'))) {
            return false;
        }

        $token = $this->input->post('request_token');

        $this->_serv = new WithdrawalHoldingAccountRequestService($this->repo, $this->_getIpAddress(), $user_id, $this->paymentInterface);

        if ($this->_serv->cancel($token)) {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND, NULL, NULL, $this->_serv->getResponseMessage());

    }
}