<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Aws\Sns\MessageValidator\Message;
use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Core\IappsBaseRepository;
use Iapps\Common\Core\IappsBaseService;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\Microservice\AccountService\AccountService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\LoanService\LoanClient;
use Iapps\Common\Microservice\LoanService\LoanServiceFactory;
use Iapps\Common\Microservice\LoanService\LoanTransactionServiceFactory;
use Iapps\Common\Microservice\ModuleCode;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Microservice\PaymentService\UserPaymentServiceFactory;
use Iapps\Common\Microservice\SalaryService\SalaryTransactionServiceFactory;
use Iapps\HoldingAccountService\Common\CoreConfigType;
use Iapps\HoldingAccountService\Common\IncrementIDAttribute;
use Iapps\HoldingAccountService\Common\IncrementIDServiceFactory;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\HoldingAccountRequestValidator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountFeeCalculator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;
use Iapps\HoldingAccountService\Common\CoreConfigDataServiceFactory;
use Iapps\HoldingAccountService\Common\HoldingAccountCorporateService;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\HoldingAccountService\Common\CorporateServServiceFactory;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;
use Iapps\HoldingAccountService\HoldingAccountRequest\RequestType;
use Iapps\HoldingAccountService\Common\ClientType;
use Illuminate\Database\Eloquent\Model;


class HoldingAccountRequestStatementService  extends IappsBaseService{

    protected $_trx = NULL;
    protected $_trx_serv = NULL;
    protected $client = NULL;

    public function getByHoldingAccountIds(array $ids, IappsDateTime $fromTime, IappsDateTime $toTime)
    {
        $this->getRepository()->setFromCreatedAt($fromTime);
        $this->getRepository()->setToCreatedAt($toTime);
        if( $history = $this->getRepository()->findByHoldingAccountIds($ids) )
        {
            return $history;
        }

        return false;
    }

    public function setClient($client){
        $this->client = $client;
        return $this ;
    }

    public function getClient(){
        return $this->client ;
    }

    public function getStatements($user_id, array $reference_ids,$limit,$page, $date_from=null ,$date_to=null)
    {
        $holdingAccountServ = HoldingAccountServiceFactory::build();

        if( $holding_account = $holdingAccountServ->getByReferenceIdArr($reference_ids, $limit, $page) ) {

            $holding_account_id_arr = array();

            $default_holding_account_id_arr = array('HA1', 'HA2', 'HA3');

            $holding_account_payment_mode_arr = array();
            $additionalTransactionIDS = array();

            $loanService = LoanServiceFactory::build();
            $supported_holding_account_payment_mode_arr = array();
            foreach ($holding_account->result as $data) {
                $holding_account_id_arr[] = $data->getId();
                switch($data->getHoldingAccountType()->getCode()){
                    case HoldingAccountType::BORROWER_ACCOUNT:
                        $supported_holding_account_payment_mode_arr[] = 'HA1';
                        $holding_account_payment_mode_arr = array_diff($default_holding_account_id_arr, $supported_holding_account_payment_mode_arr);
                        if($result = $loanService->getCompletedLoanPaymentRequestList(null, $data->getReferenceId(), $holding_account_payment_mode_arr)){
                            foreach($result as $lpr){
                                $additionalTransactionIDS[] = $lpr->transactionID;
                            }
                        }
                        break;
                    case HoldingAccountType::LOAN_ACCOUNT:
                        $supported_holding_account_payment_mode_arr[] = 'HA2';
                        break;
                    case HoldingAccountType::PERSONAL_ACCOUNT:
                        $supported_holding_account_payment_mode_arr[] = 'HA3';
                        break;
                }
            }

            $supported_holding_account_payment_mode_arr = array_unique($supported_holding_account_payment_mode_arr);


            $holding_account_request = new HoldingAccountRequest();

            $holding_account_request->setHoldingAccountIdArr($holding_account_id_arr);
            if ($date_from) {
                $holding_account_request->setDateFrom(IappsDateTime::fromString($date_from . ' 00:00:00'));
            }
            if ($date_to) {
                $holding_account_request->setDateTo(IappsDateTime::fromString($date_to . ' 23:59:59'));
            }
            $holding_account_request->setStatus(RequestStatus::SUCCESS);


            $excludeTrxType = array();
            if(!in_array('HA2', $supported_holding_account_payment_mode_arr)) {
                $excludeTrxType[] =  'loan_receive_deposit_out';
                $excludeTrxType[] =  'loan_receive_return_out';
            }

            $moduleCodes = array(ModuleCode::SALARY_MODULE, ModuleCode::LOAN_MODULE, ModuleCode::HOLDING_ACCOUNT_MODULE);

            $results = new \stdClass();
            $results->result = NULL;
            $results->total  = 0;
            $trxServ = array();

            foreach ($moduleCodes as $moduleCode) {
                if ($moduleCode == ModuleCode::SALARY_MODULE) {
                    $trxServ[ModuleCode::SALARY_MODULE] = SalaryTransactionServiceFactory::build($this->client);
                } else if ($moduleCode == ModuleCode::LOAN_MODULE) {
                    $trxServ[ModuleCode::LOAN_MODULE] = LoanTransactionServiceFactory::build($this->client);
                }else if ($moduleCode == ModuleCode::HOLDING_ACCOUNT_MODULE) {
                    $trxServ[ModuleCode::HOLDING_ACCOUNT_MODULE] = HoldingAccountTransactionServiceFactory::build($this->client);
                }
            }


            $searchedTransactionIDs = array();
            if ($collection = $this->getRepository()->findHoldingRequest($holding_account_request, $limit,$page)) {
                foreach($collection->result as $data){
                    if(isset($trxServ[$data->getModuleCode()])) {
                        if($data->getModuleCode()==ModuleCode::HOLDING_ACCOUNT_MODULE){
                            $transaction = new \Iapps\Common\Transaction\Transaction();
                            $transaction->setTransactionID($data->getTransactionID());
                            if ($transColl = $trxServ[$data->getModuleCode()]->getTransactionDetail($transaction, 1, 1)){
                                $results->result[] = $transColl;
                            }
                        }else {
                            if ($transColl = $trxServ[$data->getModuleCode()]->getTransactionHistoryDetailByRefId($data->getTransactionID(), 1, 1)) {
                                if(isset($transColl->result->transaction->transaction_type_code)){
                                    if (in_array($transColl->result->transaction->transaction_type_code, $excludeTrxType)) {
                                        continue;
                                    }
                                }
                                $results->result[] = $transColl->result;
                            }
                        }
                        $searchedTransactionIDs[] = $data->getTransactionID();
                    }
                }
                $results->total = $collection->total;
            }

            if($additionalTransactionIDS){
                $diffTransactionIDS = array_diff($additionalTransactionIDS, $searchedTransactionIDs);
                $diffTransactionIDS = array_unique($diffTransactionIDS);
                foreach($diffTransactionIDS as $transactionID){
                    if ($transColl = $trxServ[ModuleCode::LOAN_MODULE]->getTransactionHistoryDetailByRefId($transactionID, 1, 1)) {
                        if(isset($transColl->result->transaction->transaction_type_code)){
                            if (in_array($transColl->result->transaction->transaction_type_code, $excludeTrxType)) {
                                continue;
                            }
                        }
                        $results->result[] = $transColl->result;

                    }
                }
            }


            if($results){
                usort($results->result, function($a, $b) {

                    if (isset($b->payment) && isset($a->payment)) {

                        return $b->payment[0]->created_at > $a->payment[0]->created_at;
                    }
                });


                $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_HISTORY_SUCCESS);
                return $results;
            }
        }
        $this->setResponseCode(MessageCode::CODE_GET_HOLDING_ACCOUNT_HISTORY_NOT_FOUND);
        return false;
    }


    protected function _getHoldingAccountService()
    {
        $holdingAccount_service =  HoldingAccountServiceFactory::build($this->getHoldingAccountTypeCode());
        $holdingAccount_service->setUpdatedBy($this->getUpdatedBy());
        $holdingAccount_service->setIpAddress($this->getIpAddress());
        return $holdingAccount_service;
    }

    protected function _getRequestType()
    {
        $sc_serv = SystemCodeServiceFactory::build();
        return $sc_serv->getByCode($this->getRequestType(), RequestType::getSystemGroupCode());
    }

}