<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\CorporateLimitChecker;
use Iapps\HoldingAccountService\Common\MessageCode;

class Corporate_service_batch extends Base_Controller {

    protected $_service;
    protected $_table_name = "iafb_holding_account.corporate_service";

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_model');
    }
   
    public function batchUpdateServiceLimit(){
       
        $transItemServ = \Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemServiceFactory::build();
        $corpServ = \Iapps\HoldingAccountService\Common\CorporateServServiceFactory::build();
        $systemCodeServ = \Iapps\HoldingAccountService\Common\SystemCodeServiceFactory::build();
        $corpServCode = $systemCodeServ->getByCode(\Iapps\HoldingAccountService\HoldingAccountTransaction\ItemType::CORPORATE_SERVICE, \Iapps\HoldingAccountService\HoldingAccountTransaction\ItemType::getSystemGroupCode());
        
        $checker = new CorporateLimitChecker($this->_getIpAddress(), 0, $transItemServ, $corpServ, $corpServCode);
        //$checker->updateAccumulate();

        if( $updater = $checker->updateAccumulate())  {
            $this->_respondWithSuccessCode(MessageCode::CODE_SERVICE_LIMIT_UPDATE_SUCCESSFULL );
            return true;
        }

        $this->_respondWithCode(MessageCode::CODE_SERVICE_LIMIT_UPDATE_FAIL, ResponseHeader::HEADER_NOT_FOUND);
        return false;



    }
    
}
