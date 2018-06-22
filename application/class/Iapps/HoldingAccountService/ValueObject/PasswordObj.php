<?php

namespace Iapps\HoldingAccountService\ValueObject;

use Iapps\Common\Helper\PasswordHasher;
use Iapps\Common\Helper\SaltGenerator;
use Iapps\AccountService\Account\PasswordPolicy;
use Iapps\AccountService\Account\PasswordGenerator;

class PasswordObj {

    protected $salt;
    protected $password;

    protected $generatedPassword;   //this required to store original string as to inform the user

    public function setSalt($salt)
    {
        $this->salt = $salt;
        return true;
    }

    public function getSalt()
    {
        return $this->salt;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return true;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setGeneratedPassword($password)
    {
        $this->generatedPassword = $password;
        return true;
    }

    public function getGeneratedPassword()
    {
        return $this->generatedPassword;
    }

    public function setNewPassword($password, PasswordPolicy $policy = NULL)
    {
        if( $policy != NULL )
        {
            if( !$policy->validate($password) )
                return false;
        }

        $this->setSalt(SaltGenerator::generate());
        $this->setPassword(PasswordHasher::hash($password, $this->getSalt()));
        return true;
    }

    public function generatePassword(PasswordPolicy $policy = NULL)
    {
        $this->setSalt(SaltGenerator::generate());
        $randomPassword = PasswordGenerator::generate(10, $policy);
        $this->setPassword(PasswordHasher::hash($randomPassword, $this->getSalt()));
        $this->setGeneratedPassword($randomPassword);
        return $randomPassword;
    }
}