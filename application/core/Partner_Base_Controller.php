<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

use Iapps\Common\Microservice\AccountService\FunctionCode;
use Iapps\Common\Microservice\AccountService\AccessType;
use Iapps\Common\Microservice\AccountService\SessionType;

class Partner_Base_Controller extends Base_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->_authoriseClient();
    }

    //override
    protected function _get_user_id($function = NULL, $access_type = NULL, $session_type = NULL)
    {
        if($function == NULL)
        {
            return false;
        }
        if($access_type == NULL)
        {
            $access_type =  AccessType::WRITE;
        }
        if($session_type == NULL)
        {
            $session_type = SessionType::LOGIN;
        }

        return $this->_getUserProfileId($function, $access_type, $session_type);
    }


}