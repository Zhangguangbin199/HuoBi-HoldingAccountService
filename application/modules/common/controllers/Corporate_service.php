<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Iapps\Common\CorporateService\CorporateService;
use Iapps\Common\CorporateService\CorporateServiceRepository;
use Iapps\HoldingAccountService\Common\CorporateServiceExtendedService;
use Iapps\Common\CorporateService\CorporateServiceFee;
use Iapps\Common\Helper\ResponseHeader;
use Iapps\Common\Helper\InputValidator;
use Iapps\Common\Core\IappsDateTime;

class Corporate_service extends Base_Controller {

    protected $_service;
    protected $_table_name = "iafb_holding_account.corporate_service";

    function __construct()
    {
        parent::__construct();

        $this->load->model('common/corporate_service_model');
        $repo = new CorporateServiceRepository($this->corporate_service_model);
        $this->_service = new CorporateServiceExtendedService($repo, $this->_table_name);
    }

    public function getAllCorpService() 
    {
        $limit               = $this->input->get("limit");
        $page                = $this->input->get("page");

        if($object = $this->_service->getAll($limit, $page))
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(),array('result' => $object->result, 'total' => $object->total));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorpServiceInfo()
    {
        if( !$this->is_required($this->input->get(), array('id')) )
        {
            return false;
        }
        $id = $this->input->get("id");

        if( $corpServInfo = $this->_service->getCorporateService($id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServInfo));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function getCorpServiceFeeByCorpServId()
    {
        if( !$this->is_required($this->input->get(), array('corporate_service_id')) )
        {
            return false;
        }
        $corporate_service_id = $this->input->get("corporate_service_id");

        if( $result = $this->_service->getCorpServiceFeeByCorpServId($corporate_service_id) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $result));
            return false;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }




    public function addCorpService()
    {
        if( !$this->is_required($this->input->post(), array('country_currency_code',
            'service_provider_id',
            'name',
            'description',
            'transaction_type_code',
            'daily_limit')))
        {
            return false;
        }
        $country_currency_code = $this->input->post("country_currency_code");
        $service_provider_id = $this->input->post("service_provider_id");
        $name = $this->input->post("name");
        $description = $this->input->post("description");
        $transaction_type_code = $this->input->post("transaction_type_code");
        $daily_limit = $this->input->post("daily_limit");
        $admin_id = $this->_get_admin_id();


        $corpServ = new \Iapps\Common\CorporateService\CorporateService();
        $corpServ->setCountryCurrencyCode($country_currency_code);
        $corpServ->setServiceProviderId($service_provider_id);
        $corpServ->setName($name);
        $corpServ->setDescription($description);
        $corpServ->setDailyLimit($daily_limit);

        $this->_service->setUpdatedBy($admin_id);

        if( $corpServ = $this->_service->addCorpService($corpServ, $transaction_type_code))
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServ));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    public function editCorpService()
    {
        if( !$this->is_required($this->input->post(), array('id',
            'country_currency_code',
            'service_provider_id',
            'name',
            'description',
            'transaction_type_code',
            'daily_limit')))
        {
            return false;
        }

        $id = $this->input->post("id");
        $country_currency_code = $this->input->post("country_currency_code");
        $service_provider_id = $this->input->post("service_provider_id");
        $name = $this->input->post("name");
        $description = $this->input->post("description");
        $transaction_type_code = $this->input->post("transaction_type_code");
        $daily_limit = $this->input->post("daily_limit");
        $admin_id = $this->_get_admin_id();

        $corpServ = new \Iapps\Common\CorporateService\CorporateService();
        $corpServ->setId($id);
        $corpServ->setCountryCurrencyCode($country_currency_code);
        $corpServ->setServiceProviderId($service_provider_id);
        $corpServ->setName($name);
        $corpServ->setDescription($description);
        $corpServ->setDailyLimit($daily_limit);

        $this->_service->setUpdatedBy($admin_id);
        if( $corpServ = $this->_service->editCorpService($corpServ, $transaction_type_code) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode(), array('result' => $corpServ));
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }

    /*
    public function removeCorpService()
    {
        if( !$this->is_required($this->input->post(), array('id')) )
        {
            return false;
        }

        $id = $this->input->post("id");
        $admin_id = $this->_get_admin_id();

        $corpServ = new \Iapps\Common\CorporateService\CorporateService();
        $corpServ->setId($id);

        $this->_service->setUpdatedBy($admin_id);

        if( $this->_service->removeService($corpServ) )
        {
            $this->_respondWithSuccessCode($this->_service->getResponseCode());
            return true;
        }

        $this->_respondWithCode($this->_service->getResponseCode(), ResponseHeader::HEADER_NOT_FOUND);
        return false;
    }*/

}