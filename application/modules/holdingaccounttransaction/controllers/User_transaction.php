<?php

use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Transaction\TransactionHistoryRepository;
use Iapps\Common\Transaction\TransactionHistoryService;
use Iapps\Common\Core\IpAddress;
use Iapps\Common\Helper\ResponseHeader;

use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\Common\FlagImageS3Uploader;
use Iapps\Common\Core\S3FileUrl;

use Iapps\HoldingAccountService\TransactionItem\TransactionItem;
use Iapps\HoldingAccountService\TransactionItem\TransactionItemService;
use Iapps\HoldingAccountService\TransactionItem\TransactionItemServiceFactory;

use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionRepository;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionService;

use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemRepository;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemService;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemServiceFactory;

use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\SystemCode\SystemCodeService;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;


class User_transaction extends User_Base_Controller{

    protected $_service;    
    
    function __construct()
    {

        parent::__construct();

        $this->load->model('holdingaccounttransaction/Holding_account_transaction_item_model');


        $repoItem = new HoldingAccountTransactionItemRepository($this->Holding_account_transaction_item_model);
        $this->_holding_account_transaction_item_service = new HoldingAccountTransactionItemService($repoItem);

        $this->_system_code_service = SystemCodeServiceFactory::build();

        $this->load->model('holdingaccounttransaction/Holding_account_transaction_model');
        $repo = new HoldingAccountTransactionRepository($this->Holding_account_transaction_model);
        //$this->_service = new HoldingAccountTransactionService($repo);
        $this->_service = new HoldingAccountTransactionService($repo, $this->_holding_account_transaction_item_service, $this->_system_code_service);


    }

    public function getTransactionHistoryList()
    {
        if( !$user_id = $this->_getUserProfileId() )

        {
            return false;
        }


        $config = new \Iapps\Common\Transaction\Transaction();
        $config->setUserProfileId($user_id);

        $this->_service->setUpdatedBy($user_id);

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $object = $this->_service->getTransactionHistoryList($config, $limit, $page) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }


    public function getTransactionHistoryDetailByTransactionId()
    {
       
        if( !$user_id = $this->_getUserProfileId() )

        {
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


    public function getTransactionHistoryDetailByRefId()
    {
       
        if( !$user_id = $this->_getUserProfileId() )

        {
            return false;
        }

        if( !$this->is_required($this->input->get(), array('transactionID')) )
        {
            return false;
        }

        $page = $this->_getPage();
        $limit = $this->_getLimit();


        $transactionID = $this->input->get("transactionID");

        $transaction = new \Iapps\Common\Transaction\Transaction();
        $transaction->setTransactionID($transactionID);
        $this->_service->setUpdatedBy($user_id);

        if( $object = $this->_service->getTransactionDetail($transaction ,$limit,$page) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $object  ));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;

    }

    public function getTransactionHistoryListByDate()
    {
       /*
        if (!$user_id = $this->_get_user_id(FunctionCode::LIST_BILL_TRANSACTION, AccessType::READ)) {
            return false;
        }*/

        if( !$user_id = $this->_getUserProfileId() )
        {
            return false;
        }

        $config = new \Iapps\Common\Transaction\Transaction();

        $this->_service->setUpdatedBy($user_id);

        $date_from= $this->input->get('date_from') ? $this->input->get('date_from') : NULL;
        if ($date_from){
            $config->setDateFrom(IappsDateTime::fromString($date_from. ' 00:00:00' ));
        }
        $date_to= $this->input->get('date_to') ? $this->input->get('date_to') : NULL;
        if ($date_to){
            $config->setDateTo(IappsDateTime::fromString($date_to. ' 23:59:59' ));
        }

        $config->setUserProfileId($user_id);
        $config->setTransactionID($this->input->get("transactionID"));

        $page = $this->_getPage();
        $limit = $this->_getLimit();

        if( $object = $this->_service->getTransactionHistoryListByDate($config, $limit, $page ) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result->toArray(), 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getTransactionListByRefIDArr()
    {

        if( !$user_id = $this->_getUserProfileId() )
        {
            return false;
        }

        $user_profile_id = $this->input->post("user_profile_id");


        $config = new \Iapps\Common\Transaction\Transaction();
        $this->_service->setUpdatedBy($user_id);

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