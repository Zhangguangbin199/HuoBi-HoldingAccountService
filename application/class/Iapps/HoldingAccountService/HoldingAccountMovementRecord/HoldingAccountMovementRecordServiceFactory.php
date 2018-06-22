<?php

namespace Iapps\HoldingAccountService\HoldingAccountMovementRecord;

class HoldingAccountMovementRecordServiceFactory {

    protected static $_instance;

    public static function build()
    {
        if( self::$_instance == NULL )
        {
            $_ci = get_instance();
            $_ci->load->model('holdingaccount/Holding_account_movement_record_model');
            $repo = new HoldingAccountMovementRecordRepository($_ci->Holding_account_movement_record_model);

            self::$_instance = new HoldingAccountMovementRecordService($repo);
        }

        return self::$_instance;
    }
}