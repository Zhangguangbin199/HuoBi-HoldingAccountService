<?php

use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountRepository;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountService;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Microservice\AccountService\SessionType;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;

class Agent_holding_account extends Agent_Base_Controller{

    protected $_serv;

    function __construct()
    {
        parent::__construct();

        $this->load->model('holdingaccount/Holding_account_model');
        $repo = new HoldingAccountRepository($this->Holding_account_model);
        $this->_serv = new HoldingAccountService($repo, $this->_getIpAddress());

        $this->_service_audit_log->setTableName('iafb_holding_account.holding_account');
    }

    public function getHoldingAccounts()
    {
        if( !$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::LOGIN) )
            return false;

        if( $result = $this->_serv->getHoldingAccounts($user_id) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(), array('result' => $result->result->toArray(), 'total' => $result->total));
            return false;
        }

        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function searchHoldingAccounts()
    {
        if (!$user_id = $this->_getUserProfileId(NULL, NULL, SessionType::LOGIN))
            return false;

        if (!$this->is_required($this->input->post(), array('reference_id', 'holding_account_type')))
            return false;

        $reference_id = $this->input->post('reference_id');
        $holding_account_type = $this->input->post('holding_account_type');

        $holdingAccount = new HoldingAccount();
        if ($reference_id) {
            $holdingAccount->setReferenceId($reference_id);
        }
        if ($holding_account_type) {
            $holdingAccount->getHoldingAccountType()->setCode($holding_account_type);
        }


        if( $result = $this->_serv->searchByFilter($holdingAccount) )
        {
            $this->_respondWithSuccessCode($this->_serv->getResponseCode(),  array('result' => $result->result->toArray(), 'total' => $result->total));
            return false;
        }
        $this->_respondWithCode($this->_serv->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getHoldingAccountHistory()
    {
        if( !$user_id = $this->_getUserProfileId() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('reference_ids')) )
        {
            return false;
        }

        $reference_ids = $this->input->post("reference_ids");

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $holdingAcctRequestStatementServ = \Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestStatementServiceFactory::build();

        $holdingAcctRequestStatementServ->setClient(\Iapps\Common\Microservice\LoanService\LoanClient::AGENT);
        if( $result = $holdingAcctRequestStatementServ->getStatements($user_id, $reference_ids ,$limit,$page) )
        {
            $this->_respondWithSuccessCode($holdingAcctRequestStatementServ->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return false;
        }

        $this->_respondWithCode($holdingAcctRequestStatementServ->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }



    public function getHoldingAccountHistoryByDate()
    {


        if( !$user_id = $this->_getUserProfileId() )
        {
            return false;
        }

        if( !$this->is_required($this->input->post(), array('reference_ids')) )
        {
            return false;
        }

        $reference_ids = $this->input->post("reference_ids");

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $date_from= $this->input->post('date_from') ? $this->input->post('date_from') : NULL;
        $date_to= $this->input->post('date_to') ? $this->input->post('date_to') : NULL;

        $holdingAcctRequestStatementServ = \Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestStatementServiceFactory::build();

        $holdingAcctRequestStatementServ->setClient(\Iapps\Common\Microservice\LoanService\LoanClient::AGENT);
        if( $result = $holdingAcctRequestStatementServ->getStatements($user_id, $reference_ids ,$limit,$page, $date_from, $date_to) )
        {
            $this->_respondWithSuccessCode($holdingAcctRequestStatementServ->getResponseCode(), array('result' => $result->result, 'total' => $result->total));
            return false;
        }

        $this->_respondWithCode($holdingAcctRequestStatementServ->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }
}