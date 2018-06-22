<?php

use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionHistoryRepository;
use Iapps\Common\Transaction\TransactionHistoryService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;

use Iapps\HoldingAccountService\HoldingAccountTransaction\TransactionItem;
use Iapps\HoldingAccountService\HoldingAccountTransaction\TransactionItemService;
use Iapps\HoldingAccountService\HoldingAccountTransaction\TransactionItemServiceFactory;

use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionRepository;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionService;

use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemRepository;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemService;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemServiceFactory;

use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;


use Iapps\Common\Helper\DateTimeHelper;
use Iapps\HoldingAccountService\Common\FunctionCode;


class Admin_transaction extends Admin_Base_Controller
{


    protected $_service;

    function __construct()
    {
        parent::__construct();

        $this->load->model('HoldingAccountTransaction/Holding_account_transaction_item_model');

        $repoItem = new HoldingAccountTransactionItemRepository($this->Holding_account_transaction_item_model);
        $this->_holding_account_transaction_item_service = new HoldingAccountTransactionItemService($repoItem);

        $this->_system_code_service = SystemCodeServiceFactory::build();

        $this->load->model('HoldingAccountTransaction/Holding_account_transaction_model');
        $repo = new HoldingAccountTransactionRepository($this->Holding_account_transaction_model);
        //$this->_service = new HoldingAccountTransactionService($repo);
        $this->_service = new HoldingAccountTransactionService($repo, $this->_holding_account_transaction_item_service, $this->_system_code_service);
    }

    public function getTransactionListForUserByRefIDArr()
    {
        if (!$user_id = $this->_get_user_id(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {

                return false;
        }
        if( !$this->is_required($this->input->post(), array('transactionIDs')) )
        {
            return false;
        }

        $transactionID_arr = $this->input->post("transactionIDs");
        if(!is_array($transactionID_arr))
        {
            return false;
        }
        $agent_id = $this->input->post("agent_id");
        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $transaction = new Transaction();
        $transaction->setUserProfileId($agent_id);

        if( $object = $this->_service->getTransactionListForUserByRefIDArr($transaction, $transactionID_arr) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function getTransactionHistoryDetailByTransactionId()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {
            return false;
        }

        if( !$this->is_required($this->input->get(), array('transaction_id')) )
        {
            return false;
        }

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        $transaction_id = $this->input->get("transaction_id");

        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setId($transaction_id);
        $this->_service->setUpdatedBy($user_id);

        if( $object = $this->_service->getTransactionDetail($transaction ,$limit,$page) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object  ));
            return true;
        }
        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }




    public function getTransactionListByRefIDArr()
    {
        if( !$user_id = $this->_getUserProfileId(FunctionCode::ADMIN_LIST_TRANSACTION_FOR_OTHERS, AccessType::READ)) {
            return false;
        }

        $user_profile_id = $this->input->post("user_profile_id");

        $config = new \Iapps\Common\Transaction\Transaction();
        $this->_service->setUpdatedBy($user_id);

        $page = $this->input->post("page");
        $limit = $this->input->post("limit");

        $config->setUserProfileId($user_profile_id);

        $transactionIDs = $this->input->post("transactionIDs");

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $object = $this->_service->getTransactionListForUserByRefIDArr($config, $transactionIDs) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;


    }
}