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
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;
use Iapps\Common\Transaction\TransactionCollection;
use Iapps\HoldingAccountService\Common\CoreConfigType;
use Iapps\HoldingAccountService\Common\IncrementIDAttribute;
use Iapps\HoldingAccountService\Common\IncrementIDServiceFactory;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigServiceFactory;
use Iapps\HoldingAccountService\HoldingAccountRequestValidator\HoldingAccountRequestValidator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountFeeCalculator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountFeeCalculatorFactory;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionServiceFactory;
use Iapps\HoldingAccountService\Common\CoreConfigDataServiceFactory;
use Iapps\HoldingAccountService\Common\HoldingAccountCorporateService;
use Iapps\SalaryService\Payment\PaymentInterface;

abstract class HoldingAccountRequestService extends IappsBaseService{

    protected $_request;
    protected $paymentInfo;
    protected $passcode;
    protected $paymentInterface;
    protected $remark;
    protected $self_service = FALSE;
    protected $_holdingAccountRequest;
    protected $_user;
    protected $user_profile_id;

    protected $client;

    function __construct(HoldingAccountRequestRepository $rp, $ipAddress='127.0.0.1', $updatedBy=NULL, HoldingAccountPaymentInterface $paymentInterface)
    {
        parent::__construct($rp, $ipAddress, $updatedBy);

        $this->paymentInterface = $paymentInterface;
    }


    public function setClient($client){
        $this->client = $client;
        return $this ;
    }

    public function getClient(){
        return $this->client ;
    }


    public function setPaymentInfo($info)
    {
        $this->paymentInfo = $info;
        return $this;
    }

    public function getPaymentInfo()
    {
        return $this->paymentInfo;
    }

    public function setPasscode($code)
    {
        $this->passcode = $code;
        return $this;
    }

    public function getPasscode()
    {
        return $this->passcode;
    }

    public function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }

    public function getRemark()
    {
        return $this->remark;
    }

    public function getSelfService()
    {
        return $this->self_service;
    }

    public function setSelfService($self_service)
    {
        $this->self_service = $self_service;
        return $this;
    }

    public function setHoldingAccountRequest(HoldingAccountRequest $holdingAccountRequest)
    {
        $this->_holdingAccountRequest = $holdingAccountRequest;
        return $this;
    }

    public function getHoldingAccountRequest()
    {
        return $this->_holdingAccountRequest;
    }

    public function setUserProfileId($user_profile_id)
    {
        $this->user_profile_id = $user_profile_id;
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->user_profile_id;
    }

    public function requestByAccount($mobile_number,
                                     $country_currency_code, $amount, $payment_mode, AccountService $accountService = NULL)
    {

        if( $accountService == NULL )
            $accountService = AccountServiceFactory::build();

        if( $this->_user = $accountService->searchUserByDialingMobileNumber($mobile_number) )
        {

            return $this->request($this->_user->getId(), $country_currency_code, $amount, $payment_mode);
        }

        $this->setResponseCode(MessageCode::CODE_USER_NOT_FOUND);
        return false;
    }

    public function retrieveByHoldingAccountId($holding_account_id, $from_date, $to_date, $requestType = array(), $status = array())
    {
        return $this->getRepository()->findByHoldingAccountId($holding_account_id, $from_date, $to_date, $requestType, $status);
    }

    public function retrieveActiveRequest($user_profile_id)
    {
        if( $info = $this->getRepository()->findActiveByUser($user_profile_id) )
        {
            $tran_serv = HoldingAccountTransactionServiceFactory::build();
            $tran_serv->setUpdatedBy($this->getUpdatedBy());
            $tran_serv->setIpAddress($this->getIpAddress());

            $collection = $info->result;

            $result = array();
            foreach($collection AS $request)
            {
                if( $request->isRequestType($this->_getRequestType()) )
                {
                    //find transaction
                    if( $transaction = $tran_serv->findByTransactionID($request->getTransactionID()) )
                    {//authorize
                        if( $transaction->getUserProfileId($user_profile_id) )
                        {
                            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_SUCCESS);

                            $record['token'] = $request->getRequestToken();
                            $record['transaction_info'] = $transaction->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark', 'confirm_payment_code',
                                'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));
                            $result[] = $record;
                        }
                    }
                }
            }

            if( count($result) > 0 )
            {
                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_SUCCESS);
                return $result;
            }
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
        return false;
    }

    public function retrieveAllRequest($holding_account_id, $user_profile_id, $limit = NULL, $page = NULL)
    {
        $holdingAccountRequest = new HoldingAccountRequest();
        $holdingAccountRequest->getHoldingAccount()->setId($holding_account_id);
        $holdingAccountRequest->setRequestType($this->_getRequestType());
        if( $holdingAccountRequestColl = $this->getRepository()->findByParam($holdingAccountRequest) )
        {
            if($holdingAccountRequestColl->result instanceof HoldingAccountRequestCollection) {

                $transactionID_arr = array();
                foreach ($holdingAccountRequestColl->result as $holdingAccountRequestEach) {
                    $transactionID_arr[] = $holdingAccountRequestEach->getTransactionID();
                }

                $tran_serv = HoldingAccountTransactionServiceFactory::build();
                $tran_serv->setUpdatedBy($this->getUpdatedBy());
                $tran_serv->setIpAddress($this->getIpAddress());

                $transaction = new \Iapps\Common\Transaction\Transaction();
                $transaction->setUserProfileId($user_profile_id);
                if( $transactionColl = $tran_serv->getTransactionListForUserByRefIDArr($transaction, $transactionID_arr) ) {
                    if($transactionColl->result instanceof TransactionCollection) {

                        if($limit != NULL && $page != NULL) {
                            $transactionColl = $transactionColl->result->pagination($limit, $page);
                        }

                        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_SUCCESS);
                        return $transactionColl;

                    }
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
        return false;
    }

    public function findRequest($transactionID){
        if( $request = $this->getRepository()->findByTransactionID(getenv('MODULE_CODE'), $transactionID) )
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_SUCCESS);
            return $request;
        }
        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
        return false;
    }

    public function retrieveRequest($user_profile_id, $transactionID, $passcode = null, array $countryCurrencyCodes = array())
    {
        if( $this->_request = $this->getRepository()->findByTransactionID(getenv('MODULE_CODE'), $transactionID) )
        {
            if( $this->_request->isRequestType($this->_getRequestType() ) AND
                $this->_request->getStatus() == RequestStatus::PENDING )
            {
                //find transaction
                $tran_serv = HoldingAccountTransactionServiceFactory::build();
                $tran_serv->setUpdatedBy($this->getUpdatedBy());
                $tran_serv->setIpAddress($this->getIpAddress());
                if( $transaction = $tran_serv->findByTransactionID($this->_request->getTransactionID()) )
                {//authorize
                    if( $transaction->getUserProfileId($user_profile_id) )
                    {
                        if( $passcode != NULL )
                        {
                            if( !$transaction->getPasscode()->authorize($passcode) )
                            {
                                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                                return false;
                            }
                        }

                        if( count($countryCurrencyCodes) > 0 )
                        {//check if transaction is one of it
                            if( !in_array($transaction->getCountryCurrencyCode(), $countryCurrencyCodes) )
                            {
                                $this->setResponseCode(MessageCode::CODE_CURRENCY_NOT_SUPPORTED);
                                return false;
                            }
                        }

                        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_SUCCESS);

                        $result['token'] = $this->_request->getRequestToken();
                        $result['transaction_info'] = $transaction->getCombinedTransactionArray(array('id', 'transactionID', 'created_at', 'status', 'country_currency_code', 'total_amount', 'remark',
                            'items' => array('id', 'item_type', 'name', 'description', 'quantity', 'unit_price', 'net_amount')));
                        return $result;
                    }
                }
            }
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
        return false;
    }

    public function request($user_profile_id, $holding_account_type, $reference_id, $country_currency_code, $amount, $payment_mode = NULL,
                            $module_code = NULL, $transactionID = NULL)
    {
        //find HoldingAccount
        $holdingAccount = new HoldingAccount();

        $this->_getHoldingAccountService()->setHoldingAccountType($holding_account_type);
        $holdingAccount->setReferenceId($reference_id);
        if($this->paymentInterface == new UserHoldingAccountPayment()){
            $holdingAccount->setUserProfileId($user_profile_id);
        }

        $holdingAccount->setCountryCurrencyCode($country_currency_code);

        if( $holdingAccount = $this->_findHoldingAccount($holdingAccount) )
        {
            if( !$this->_validateHoldingAccount($holdingAccount) )
            {
                return false;
            }

            if( $request_type = $this->_getRequestType() )
            {

                $this->_request = new HoldingAccountRequest();
                $this->_request->setId(GuidGenerator::generate());
                $this->_request->generateToken();
                $this->_request->setRequestType($request_type);
                $this->_request->setHoldingAccount($holdingAccount);
                $this->_request->setStatus(RequestStatus::PENDING);
                $this->_request->setAmount($amount);
                $this->_request->setToAmount($amount);
                $this->_request->setDisplayRate(1);
                $this->_request->setToCountryCurrencyCode($holdingAccount->getCountryCurrencyCode());
                $this->_request->setPaymentCode($payment_mode);
                $this->_request->setModuleCode($module_code);
                $this->_request->setTransactionID($transactionID);
                $this->_request->setCreatedBy($this->getUpdatedBy());
                $this->_setExpiryData($this->_request);

                //check active request
                if( !$this->_checkActiveRequest($this->_request) )
                {
                    return false;
                }
                $this->_request->setUserProfileId($user_profile_id);

                $this->getRepository()->startDBTransaction();

                Logger::debug('HoldingAccount.request: requesting action - ' . $this->_request->getId());
                if( $this->_requestAction($this->_request) )
                {
                    Logger::debug('HoldingAccount.request: validating request record - ' . $this->_request->getId());
                    if( $this->_validateRequest($this->_request) )
                    {
                        //save request
                        Logger::debug('HoldingAccount.request: inserting request record - ' . $this->_request->getId());
                        if ($this->getRepository()->insertRequest($this->_request))
                        {
                            $this->getRepository()->completeDBTransaction();

                            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_SUCCESS);
                            return array(
                                'token' => $this->_request->getRequestToken()
                            );
                        }
                    }
                }

                $this->getRepository()->rollbackDBTransaction();
            }
        }
        else
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_NOT_FOUND);

        //if no code define, use general code
        if( $this->getResponseCode() == NULL )
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_FAILED);

        return false;
    }

    public function cancel($token)
    {
        if( $this->_request = $this->getRepository()->findByToken($token) )
        {
            if( !$this->_request->isRequestType($this->_getRequestType()))
            {
                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                return false;
            }

            $ori_request = clone($this->_request);

            $holdingAccount_serv = $this->_getHoldingAccountService();
            $holdingAccount_serv->setUpdatedBy($this->getUpdatedBy());
            $holdingAccount_serv->setIpAddress($this->getIpAddress());
            if( $holdingAccount = $holdingAccount_serv->findById($this->_request->getHoldingAccount()->getId()) )
            {
                $this->_request->setHoldingAccount($holdingAccount);

                if( $this->getUserProfileId() )
                {
                    if( !$this->_request->belongsTo($this->getUserProfileId()) )
                    {
                        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                        return false;
                    }
                }

                if( $this->_request->cancel() )
                {
                    if( $this->getUpdatedBy() == NULL )
                        $this->setUpdatedBy($this->_request->getHoldingAccount()->getUserProfileId());
                    $this->_request->setUpdatedBy($this->getUpdatedBy());

                    $this->getRepository()->startDBTransaction();

                    Logger::debug('HoldingAccount.request: cancelling action - ' . $this->_request->getId());
                    if( $this->_cancelAction($this->_request) )
                    {
                        //complete request
                        if( $this->getRepository()->updateRequestStatus($this->_request) )
                        {
                            $this->getRepository()->completeDBTransaction();
                            $this->fireLogEvent('iafb_holding_account.holding_account_request', AuditLogAction::UPDATE, $this->_request->getId(), $ori_request);

                            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_CANCEL_SUCCESS);
                            return true;
                        }
                    }

                    $this->getRepository()->rollbackDBTransaction();
                }
            }
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
            return false;
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_CANCEL_FAILED);
        return false;
    }

    public function complete($token, $reference_no = NULL)
    {
        if( $this->_request = $this->getRepository()->findByToken($token) )
        {
            if( !$this->_request->isRequestType($this->_getRequestType()) )
            {
                $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                return false;
            }

            $ori_request = clone($this->_request);

            $hldingAccount_serv = $this->_getHoldingAccountService();
            $hldingAccount_serv->setUpdatedBy($this->getUpdatedBy());
            $hldingAccount_serv->setIpAddress($this->getIpAddress());
            if( $holdingAccount = $hldingAccount_serv->findById($this->_request->getHoldingAccount()->getId()) )
            {
                $this->_request->setHoldingAccount($holdingAccount);
                $this->_request->setReferenceNo($reference_no);

                if( $this->getUserProfileId() AND $this->paymentInterface != new SystemHoldingAccountPayment())
                {
                    if( !$this->_request->belongsTo($this->getUserProfileId()) )
                    {
                        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
                        return false;
                    }
                }

                if( $this->getUpdatedBy() == NULL )
                    $this->setUpdatedBy($this->_request->getHoldingAccount()->getUserProfileId());

                $this->_request->setUpdatedBy($this->getUpdatedBy());
                if( $this->_request->complete() )
                {
                    $this->getRepository()->startDBTransaction();
                    Logger::debug('HoldingAccount.request: completing action - ' . $this->_request->getId());
                    if ($this->_completeAction($this->_request)) {

                        //complete request
                        if ($this->getRepository()->updateRequestStatus($this->_request)) {
                            $this->getRepository()->completeDBTransaction();
                            $this->fireLogEvent('iafb_holding_account.holding_account_request', AuditLogAction::UPDATE, $this->_request->getId(), $ori_request);

                            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_COMPLETE_SUCCESS);
                            return true;
                        }
                    }

                    $this->getRepository()->rollbackDBTransaction();
                }
            }
        }
        else
        {
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_NOT_FOUND);
            return false;
        }

        if( $this->getResponseCode() == NULL )
            $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_REQUEST_COMPLETE_FAILED);
        return false;
    }

    abstract protected function _getCorporateService($country_currency_code);
    abstract protected function _requestAction(HoldingAccountRequest $request);
    abstract protected function _completeAction(HoldingAccountRequest $request);
    abstract protected function _cancelAction(HoldingAccountRequest $request);

    protected function _findHoldingAccount(HoldingAccount $holdingAccount)
    {
        return $this->_getHoldingAccountService()->findHoldingAccount($holdingAccount);
    }

    protected function _paymentRequest(HoldingAccountRequest $request, array $paymentInfo, HoldingAccountTransaction $trx)
    {
        Logger::debug('HoldingAccount.request: requesting payment - ' . $request->getId());

        $trxServ = HoldingAccountTransactionServiceFactory::build();
        $trxServ->setUpdatedBy($this->getUpdatedBy());
        $trxServ->setIpAddress($this->getIpAddress());
        Logger::debug('HoldingAccount.request: updating commission structure - ' . $request->getId());
        if( $trxServ->updateAgentId($trx) )
        {
            Logger::debug('HoldingAccount.request: validating payment amount - ' . $request->getId());
            if( $trx->getItems()->validatePaymentAmount($paymentInfo['amount']) ) {
                if($result = $this->paymentInterface->paymentRequest($request, $paymentInfo) )
                {
                    return $result;
                }

                $lastResponse = $this->paymentInterface->getLastResponse();
                if( isset($lastResponse['status_code']) )
                    $this->setResponseCode($lastResponse['status_code']);

                if( isset($lastResponse['message']) )
                    $this->setResponseMessage($lastResponse['message']);

            }
            else
            {//invalid amount
                $this->setResponseCode(MessageCode::CODE_INVALID_PAYMENT_AMOUNT);
                return false;
            }
        }

        if( $this->getResponseCode() == NULL )
            $this->setResponseCode(MessageCode::CODE_PAYMENT_FAILED);
        return false;
    }

    protected function _paymentComplete(HoldingAccountRequest $request, array $response = array())
    {
        Logger::debug('HoldingAccount.request: completing payment - ' . $request->getId());
        if( $result = $this->paymentInterface->paymentComplete($request, $response) )
        {
            return $result;
        }


        $lastResponse = $this->paymentInterface->getLastResponse();
        if( isset($lastResponse['status_code']) )
            $this->setResponseCode($lastResponse['status_code']);
        else
            $this->setResponseCode(MessageCode::CODE_PAYMENT_FAILED);

        if( isset($lastResponse['message']) )
            $this->setResponseMessage($lastResponse['message']);

        return false;
    }

    protected function _paymentCancel(HoldingAccountRequest $request)
    {
        Logger::debug('HoldingAccount.request: cancelling payment - ' . $request->getId());
        if( $result = $this->paymentInterface->paymentCancel($request) )
        {
            return $result;
        }

        $lastResponse = $this->paymentInterface->getLastResponse();
        if( isset($lastResponse['status_code']) )
            $this->setResponseCode($lastResponse['status_code']);
        else
            $this->setResponseCode(MessageCode::CODE_PAYMENT_FAILED);

        if( isset($lastResponse['message']) )
            $this->setResponseMessage($lastResponse['message']);

        return false;
    }

    protected function _createTransaction(HoldingAccountRequest $request)
    {
        Logger::debug('HoldingAccount.request: creating trx - ' . $request->getId());
        if( $corp_serv = $this->_getCorporateService($request->getHoldingAccount()->getCountryCurrencyCode()))
        {
            $this->_constructMainItemDescription($request);

            $promo_id = null;
            $payment_info = $this->getPaymentInfo();
            if( isset($payment_info['user_promo_reward_id']) )
                $promo_id = $payment_info['user_promo_reward_id'];

            $payment_mode = null;
            if ( isset($payment_info['payment_mode']) )
                $payment_mode = $payment_info['payment_mode'];

            Logger::debug('HoldingAccount.request: calculating fees - ' . $request->getId());
            $calc_serv = HoldingAccountFeeCalculatorFactory::build($payment_mode);
            $calc_serv->setUpdatedBy($this->getUpdatedBy());

            if ($calculator = $calc_serv->calculate($corp_serv->getId(), $request, $this->getSelfService(), $promo_id, $this->getUpdatedBy())) {
                $inc_serv = IncrementIDServiceFactory::build();
                if ($transactionID = $inc_serv->getIncrementID(IncrementIDAttribute::TRANSACTION_ID)) {
                    $transaction = $request->generateTransaction($calculator, $request->getUserProfileId(), getenv('MODULE_CODE'), $transactionID, $this->getRemark());

                    if ($this->getPasscode() != NULL)
                        $transaction->getPasscode()->setCode($this->getPasscode());

                    $tran_serv = HoldingAccountTransactionServiceFactory::build();
                    $tran_serv->setUpdatedBy($this->getUpdatedBy());
                    $tran_serv->setIpAddress($this->getIpAddress());

                    Logger::debug('HoldingAccount.request: saving transaction - ' . $request->getId());
                    if ($tran_serv->saveTransaction($transaction)) {
                        return $transaction;
                    }

                    $this->setResponseCode($tran_serv->getResponseCode());
                }
            } else
                $this->setResponseCode(MessageCode::CODE_COMPUTE_FEE_FAILED);

        }

        return false;
    }

    protected function _completeTransaction(HoldingAccountTransaction $transaction)
    {
        HoldingAccountTransactionServiceFactory::reset();
        $trx_serv = HoldingAccountTransactionServiceFactory::build($transaction->getTransactionType()->getCode());
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());
        $trx_serv->setHoldingAccountRequest($this->getHoldingAccountRequest());

        if( !$result = $trx_serv->completeTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    protected function _cancelTransaction(HoldingAccountTransaction $transaction)
    {
        HoldingAccountTransactionServiceFactory::reset();
        $trx_serv = HoldingAccountTransactionServiceFactory::build($transaction->getTransactionType()->getCode());
        $trx_serv->setUpdatedBy($this->getUpdatedBy());
        $trx_serv->setIpAddress($this->getIpAddress());
        $trx_serv->setHoldingAccountRequest($this->getHoldingAccountRequest());

        if( !$result = $trx_serv->cancelTransaction($transaction) )
        {
            $this->setResponseCode(MessageCode::CODE_ADD_HOLDING_ACCOUNT_TRANSACTION_FAILED);
            return false;
        }

        return $result;
    }

    /*
     * This will check all the existing request plus the coming request is still within max limit
     */
    protected function _checkActiveRequest(HoldingAccountRequest $currentRequest)
    {
        return true;
    }

    protected function _getHoldingAccountService()
    {
        $holdingAccount_service =  HoldingAccountServiceFactory::build();
        $holdingAccount_service->setUpdatedBy($this->getUpdatedBy());
        $holdingAccount_service->setIpAddress($this->getIpAddress());
        return $holdingAccount_service;
    }

    protected function _validateRequest(HoldingAccountRequest $request)
    {
        $v = HoldingAccountRequestValidator::make($request);
        $this->setResponseCode($v->getErrorCode());
        return !$v->fails();
    }

    protected function _validateHoldingAccount(HoldingAccount $holdingAccount)
    {
        if( $holdingAccount->getIsActive() )
        {
            return true;
        }

        $this->setResponseCode(MessageCode::CODE_HOLDING_ACCOUNT_INACTIVE);
        return false;
    }

    protected function _setExpiryData(HoldingAccountRequest $request)
    {
        $needApproval = FALSE;
        if( $request->getPaymentCode() )
        {
            $payment_serv = PaymentServiceFactory::build();
            if($paymentModeInfo = $payment_serv->getPaymentModeInfo($request->getPaymentCode()))
            {
                $needApproval = $paymentModeInfo->getNeedApproval();
            }
        }

        if(!$needApproval) {
            $config_serv = CoreConfigDataServiceFactory::build();
            $period = NULL;
            if($request->getRequestType()->getCode() == RequestType::UTILISE) {
                $period = $config_serv->getConfig(CoreConfigType::UTILIZE_REQUEST_EXPIRED_PERIOD);
            } else {
                $period = $config_serv->getConfig(CoreConfigType::REQUEST_EXPIRED_PERIOD);
            }

            if ($period) {
                //$period in mins
                $period_sec = $period * 60;
                $expiry_date = IappsDateTime::now()->addSecond($period_sec);

                $request->setExpiredAt($expiry_date);
            }
        }

        return true;
    }

    abstract protected function _getRequestType();

    protected function _constructMainItemDescription(HoldingAccountRequest $request)
    {
        return true;
    }
}