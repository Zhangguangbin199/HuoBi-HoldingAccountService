<?php

namespace Iapps\HoldingAccountService\HoldingAccountRequest;

use Iapps\Common\Core\IappsBaseEntity;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;
use Iapps\Common\SystemCode\SystemCode;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\HoldingAccountService\Common\GeneralDescription;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountFeeCalculator;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;

class HoldingAccountRequest extends IappsBaseEntity{

    protected $module_code;
    protected $transactionID;
    protected $request_token;
    protected $request_type;
    protected $holdingAccount;
    protected $status;
    protected $amount;
    protected $display_rate;
    protected $to_amount;
    protected $to_country_currency_code;
    protected $payment_code;
    protected $payment_request_id;
    protected $reference_no;
    protected $expired_at;
    protected $date_from;
    protected $date_to;

    protected $user_profile_id;

    protected $description = NULL;
    protected $itemDescription = NULL;

    protected $holding_account_id_arr = array();

    function __construct()
    {
        parent::__construct();

        $this->request_type = new SystemCode();
        $this->holdingAccount = new HoldingAccount();

        $this->expired_at = new IappsDateTime();
        $this->itemDescription = new GeneralDescription();
    }

    public function setModuleCode($module_code)
    {
        $this->module_code = $module_code;
        return $this;
    }

    public function getModuleCode()
    {
        return $this->module_code;
    }

    public function setTransactionID($transactionID)
    {
        $this->transactionID = $transactionID;
        return $this;
    }

    public function getTransactionID()
    {
        return $this->transactionID;
    }

    public function setRequestToken($request_token)
    {
        $this->request_token = $request_token;
        return $this;
    }

    public function getRequestToken()
    {
        return $this->request_token;
    }

    public function setRequestType(SystemCode $code)
    {
        $this->request_type = $code;
        return $this;
    }

    public function getRequestType()
    {
        return $this->request_type;
    }

    public function setHoldingAccount(HoldingAccount $holdingAccount)
    {
        $this->holdingAccount = $holdingAccount;
        return $this;
    }

    public function getHoldingAccount()
    {
        return $this->holdingAccount;
    }

    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setToAmount($to_amount)
    {
        $this->to_amount = $to_amount;
        return $this;
    }

    public function getToAmount()
    {
        return $this->to_amount;
    }

    public function setPaymentCode($payment_code)
    {
        $this->payment_code = $payment_code;
        return $this;
    }

    public function getPaymentCode()
    {
        return $this->payment_code;
    }

    public function setPaymentRequestId($payment_request_id)
    {
        $this->payment_request_id = $payment_request_id;
        return $this;
    }

    public function getPaymentRequestId()
    {
        return $this->payment_request_id;
    }

    public function setReferenceNo($reference_no)
    {
        $this->reference_no = $reference_no;
        return $this;
    }

    public function getReferenceNo()
    {
        return $this->reference_no;
    }

    public function setExpiredAt(IappsDateTime $dt)
    {
        $this->expired_at = $dt;
        return $this;
    }

    public function getExpiredAt()
    {
        return $this->expired_at;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setItemDescription(GeneralDescription $itemDesc)
    {
        $this->itemDescription = $itemDesc;
        return $this;
    }

    public function getItemDescription()
    {
        return $this->itemDescription;
    }

    public function generateToken()
    {
        $token = md5(base64_encode(pack('N6', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), uniqid())));
        $this->setRequestToken($token);
        return $token;
    }

    public function setUserProfileId($user_profile_id){
        $this->user_profile_id = $user_profile_id;
        return $this;
    }

    public function getUserProfileId()
    {
        return $this->user_profile_id;
    }

    public function setHoldingAccountIdArr(array $holding_account_id_arr)
    {
        $this->holding_account_id_arr = $holding_account_id_arr;
        return $this;
    }

    public function getHoldingAccountIdArr()
    {
        return $this->holding_account_id_arr;
    }

    public function setDateFrom(IappsDateTime $date_from)
    {
        $this->date_from = $date_from;
        return $date_from;
    }

    public function getDateFrom()
    {
        return $this->date_from;
    }

    public function setDateTo(IappsDateTime $date_to)
    {
        $this->date_to = $date_to;
        return $date_to;
    }

    public function getDateTo()
    {
        return $this->date_to;
    }

    public function setDisplayRate($display_rate)
    {
        $this->display_rate = $display_rate;
        return $display_rate;
    }

    public function getDisplayRate()
    {
        return $this->display_rate;
    }

    public function setToCountryCurrencyCode($to_country_currency_code)
    {
        $this->to_country_currency_code = $to_country_currency_code;
        return $to_country_currency_code;
    }

    public function getToCountryCurrencyCode()
    {
        return $this->to_country_currency_code;
    }

    public function jsonSerialize()
    {
        $json = parent::jsonSerialize();

        $json['module_code'] = $this->getModuleCode();
        $json['transactionID'] = $this->getTransactionID();
        $json['module_code'] = $this->getModuleCode();
        $json['status'] = $this->getStatus();
        $json['amount'] = $this->getAmount();
        $json['to_amount'] = $this->getToAmount();
        $json['to_country_currency_code'] = $this->getToCountryCurrencyCode();
        $json['display_rate'] = $this->getDisplayRate();
        $json['payment_code'] = $this->getPaymentCode();
        $json['expired_at'] = $this->getExpiredAt()->getString();
        $json['user_profile_id'] = $this->getUserProfileId();
        $json['description'] = $this->getDescription();

        return $json;
    }

    public function generateTransaction(HoldingAccountFeeCalculator $calculator, $user_profile_id, $module_code, $transactionID, $remark)
    {
        $trx = new HoldingAccountTransaction();
        $trx->setId(GuidGenerator::generate());
        $trx->getTransactionType()->setId($calculator->getCorporateService()->getTransactionTypeId());
        $trx->setTransactionID($transactionID);
        $trx->setUserProfileId($user_profile_id);
        $trx->getStatus()->setCode(TransactionStatus::CONFIRMED);
        $trx->setCountryCurrencyCode($calculator->getInCountryCurrencyCode());
        $trx->setRemark($remark);
        $trx->setConfirmPaymentCode($calculator->getCashInMode());
        $trx->setDescription($this->getDescription());

        foreach($calculator->getTransactionItems() AS $item)
        {
            $trx->addItem($item);
        }

        $this->setModuleCode($module_code);
        $this->setTransactionID($transactionID);

        return $trx;
    }

    public function complete()
    {
        if( $this->getStatus() == RequestStatus::PENDING )
        {
            $this->setStatus(RequestStatus::SUCCESS);
            return $this;
        }

        return false;
    }

    public function cancel()
    {
        if( $this->getStatus() == RequestStatus::PENDING )
        {
            $this->setStatus(RequestStatus::CANCEL);
            return $this;
        }

        return false;
    }

    public function isRequestType(SystemCode $code)
    {
        return ($this->getRequestType()->getCode() == $code->getCode() );
    }

    public function belongsTo($user_profile_id)
    {
        if( $user_profile_id )
        {
            return $this->getHoldingAccount()->getUserProfileId() == $user_profile_id;
        }

        return false;
    }
}