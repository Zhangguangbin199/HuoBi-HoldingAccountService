<?php

namespace Iapps\HoldingAccountService\Common;

use Iapps\Common\CommunicationService\CommunicationServiceProducer;
use Iapps\Common\Export\ReceiptExportService;
use Iapps\Common\Helper\CurrencyFormatter;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemCollection;
use Iapps\HoldingAccountService\Common\CoreConfigDataServiceFactory;

class HoldingAccountReceiptExportService extends ReceiptExportService{

    protected $header = array();

    protected function _notifyUser($html, $userProfileId, $transactionDetail, $cc = array())
    {
        $accountServ = AccountServiceFactory::build();
        $user = $accountServ->getUsers(array($userProfileId));
        $user = $user->getById($userProfileId);

        if(!$user)
            return false;

        $this->_sendEmail($user, $html);
        if( $accountServ->checkAccessByUserProfileId($user->getId(), FunctionCode::APP_PUBLIC_FUNCTIONS) )
        {//for APP user - send chat
            $this->_sendChat($user, $transactionDetail, true);
            //send SMS in addition
            $this->_sendSMS($user, $transactionDetail);
        }
        else if( $accountServ->checkAccessByUserProfileId($user->getId(), FunctionCode::AGENT_FUNCTIONS) )
        {//for Mobile Agent
            $this->_sendChat($user, $transactionDetail, false);
        }

        return true;
    }

    protected function _sendSMS(User $user, $transactionDetail)
    {
        if( $content = $this->_getChatMessage($transactionDetail) )
        {
            $dialing_code = $user->getMobileNumberObj()->getDialingCode();
            $mobile_number = $user->getMobileNumberObj()->getMobileNumber();
            if( isset($dialing_code) AND
                isset($mobile_number) )
            {
                $mobile_country_code = '+'. $dialing_code;
                $basic_mobile_number = $mobile_number;
                $chat_obj = new CommunicationServiceProducer();
                return $chat_obj->sendSMS(getenv("ICS_PROJECT_ID"), $content, $mobile_country_code, $basic_mobile_number, "", "");
            }
        }

        return false;
    }

    protected function _getChatMessage($transactionDetail)
    {
        $transaction = $transactionDetail->transaction;
        $transactionItem = $transactionDetail->transaction_items;

        if( $transaction instanceof HoldingAccountTransaction AND
            $transactionItem instanceof HoldingAccountTransactionItemCollection )
        {
            if( $transaction->getTransactionType()->getCode() == TransactionType::CODE_TOP_UP)
                $configCode = CoreConfigType::TOPUP_MESSAGE;
            else if( $transaction->getTransactionType()->getCode() == TransactionType::CODE_WITHDRAW)
                $configCode = CoreConfigType::WITHDRAWAL_MESSAGE;
            else
                return false;

            $configServ = CoreConfigDataServiceFactory::build();
            if( $message = $configServ->getConfig($configCode) )
            {
                $message = str_replace("[TRANSACTIONID]", $transaction->getTransactionID(), $message);
                foreach( $transactionItem AS $item )
                {
                    if( $item->isMainItem() )
                    {
                        $amount = CurrencyFormatter::format($item->getNetAmount(), $transaction->getCountryCurrencyCode());
                        $message = str_replace("[AMOUNT]", $amount, $message);
                        break;
                    }
                }

                if( isset($transactionDetail->payment[0]->payment_mode_name) )
                    $payment_mode = $transactionDetail->payment[0]->payment_mode_name;
                else
                    $payment_mode = $transaction->getConfirmPaymentCode();

                $message = str_replace("[PAYMENT_MODE]", $payment_mode, $message);

                return $message;
            }
        }

        return false;
    }

    protected function _getEmailSubject()
    {
        if( $this->_transaction != NULL )
        {
            if( $this->_transaction instanceof HoldingAccountTransaction )
            {
                $configCode = NULL;
                if( $this->_transaction->getTransactionType()->getCode() == TransactionType::CODE_TOP_UP)
                    $configCode = CoreConfigType::TOPUP_EMAIL_SUBJECT;
                else if( $this->_transaction->getTransactionType()->getCode() == TransactionType::CODE_WITHDRAW)
                    $configCode = CoreConfigType::WITHDRAWAL_EMAIL_SUBJECT;

                if( $configCode )
                {
                    $configServ = CoreConfigDataServiceFactory::build();
                    if( $subject = $configServ->getConfig($configCode) )
                    {
                        return $subject;
                    }
                }
            }
        }

        //return default subject
        return parent::_getEmailSubject();
    }
}