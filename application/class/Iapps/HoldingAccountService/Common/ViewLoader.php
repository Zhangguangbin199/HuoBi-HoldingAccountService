<?php

namespace Iapps\AccountService\Common;

require_once BASEPATH . 'core/Loader.php';

class ViewLoader implements ViewLoaderInterface{

    public static function load($viewFileName, $param)
    {
        $a = new \CI_Loader();

        return $a->view($viewFileName, $param);
    }
}