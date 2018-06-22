<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

use Iapps\HoldingAccountService\Common\SystemCodeServiceFactory;
use Iapps\HoldingAccountService\Common\TransactionType;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountType;

class HoldingAccountTransactionServiceFactory{

    protected static $_instance;

    public static function build($transaction_type_code = NULL)
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('HoldingAccounttransaction/Holding_account_transaction_model');
            $t_repo = new HoldingAccountTransactionRepository($_ci->Holding_account_transaction_model);

            $ti_serv = HoldingAccountTransactionItemServiceFactory::build();
            $sc_serv = SystemCodeServiceFactory::build();

            switch($transaction_type_code)
            {
                case TransactionType::CODE_TOP_UP:
                    self::$_instance = new HoldingAccountTopupTransactionService($t_repo, $ti_serv, $sc_serv);
                    break;
                case TransactionType::CODE_WITHDRAW:
                    self::$_instance = new HoldingAccountWithdrawalTransactionService($t_repo, $ti_serv, $sc_serv);
                    break;
                default:
                    self::$_instance = new HoldingAccountTransactionService($t_repo, $ti_serv, $sc_serv);
            }
        }

        return self::$_instance;
    }

    public static function reset()
    {
        self::$_instance = NULL;
    }
}