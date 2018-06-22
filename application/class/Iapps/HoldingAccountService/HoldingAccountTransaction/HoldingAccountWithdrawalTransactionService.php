<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\Common\AuditLog\AuditLogAction;
use Iapps\Common\Transaction\Transaction;
use Iapps\Common\Transaction\TransactionService;
use Iapps\HoldingAccountService\Common\Logger;
use Iapps\HoldingAccountService\Common\MessageCode;

class HoldingAccountWithdrawalTransactionService extends HoldingAccountTransactionService {

    protected function _cancelAction() //override
    {
        $holdingAccount_serv = $this->_getHoldingAccountService();

        if($holdingAccount_serv->revert($this->getHoldingAccountRequest()->getHoldingAccount(),
            $this->getHoldingAccountRequest()->getAmount(),
            $this->getHoldingAccountRequest()->getModuleCode(),
            $this->getHoldingAccountRequest()->getTransactionID()))
        {
            return true;
        }

        return false;
    }

}
