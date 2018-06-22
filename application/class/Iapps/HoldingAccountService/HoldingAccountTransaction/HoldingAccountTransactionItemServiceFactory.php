<?php

namespace Iapps\HoldingAccountService\HoldingAccountTransaction;

class HoldingAccountTransactionItemServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('holdingaccounttransaction/Holding_account_transaction_item_model');
            $ti_repo = new HoldingAccountTransactionItemRepository($_ci->Holding_account_transaction_item_model);

            self::$_instance = new HoldingAccountTransactionItemService($ti_repo);
        }

        return self::$_instance;
    }
}