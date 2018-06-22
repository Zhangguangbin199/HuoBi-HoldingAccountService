<?php

namespace Iapps\HoldingAccountService\Common;

class Logger {

    public static function debug($msg)
    {
        return log_message('debug', $msg);
    }

    public static function error($msg)
    {
        return log_message('error', $msg);
    }
}