<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Core\IappsBasicBaseService;
use Iapps\Common\Microservice\AccountService\AccountServiceFactory;
use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\User;
use Iapps\Common\Microservice\AccountService\UserType;
use Iapps\Common\Microservice\PaymentService\PaymentServiceFactory;

class HoldingAccountCreationService extends IappsBasicBaseService{

    public function createPersonalAccount($user_profile_id)
    {
        if( $user = $this->_getUser($user_profile_id) )
        {
            $ewl_serv = HoldingAccountServiceFactory::build(HoldingAccountType::PERSONAL_ACCOUNT);
            $ewl_serv->setUpdatedBy($this->getUpdatedBy());
            $ewl_serv->setIpAddress($this->getIpAddress());

            if( $user->getUserType() == UserType::USER AND
                $this->_isUserAccessible($user_profile_id, FunctionCode::APP_PUBLIC_FUNCTIONS) )
            {
                if($this->_create($user, $ewl_serv)) {
                    return true;
                }
            }
        }

        //if user is not found, then push back to queue
        return false;
    }

    protected function _getUser($user_profile_id)
    {
        //get user data
        $acc_serv = AccountServiceFactory::build();

        return $acc_serv->getUser(NULL, $user_profile_id);
    }

    protected function _isUserAccessible($user_profile_id, $function)
    {
        //get user data
        $acc_serv = AccountServiceFactory::build();

        return $acc_serv->checkAccessByUserProfileId($user_profile_id, $function);
    }

    protected function _create(User $user, HoldingAccountService $serv)
    {
        //get curencies
        $payment_serv = PaymentServiceFactory::build();
        if( $currencies = $payment_serv->getCountryCurrencyInfoByCountryCode($user->getHostCountryCode()) )
        {
            foreach($currencies AS $currency)
            {
                if(!$serv->createHoldingAccount($user->getId(), $currency->getCode()))
                {
                    return false;
                }
            }
        }

        return true;
    }
}