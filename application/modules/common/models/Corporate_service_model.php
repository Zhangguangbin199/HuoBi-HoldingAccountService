<?php

use Iapps\HoldingAccountService\Common\ICorporateServiceExtendedDataMapper;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\Common\HoldingAccountCorporateService;
use Iapps\Common\CorporateService\CorporateServiceCollection;
use Iapps\Common\CorporateService\CorporateService;

class Corporate_service_model extends Base_Model implements ICorporateServiceExtendedDataMapper{

    public function map(stdClass $data)
    {
        $entity = new HoldingAccountCorporateService();

        if( isset($data->id) )
            $entity->setId($data->id);

        if( isset($data->country_code) )
            $entity->setCountryCode($data->country_code);

        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);

        if( isset($data->name) )
            $entity->setName($data->name);

        if( isset($data->description) )
            $entity->setDescription($data->description);

        if( isset($data->transaction_type_id) )
            $entity->setTransactionTypeId($data->transaction_type_id);

        if( isset($data->country_currency_code) )
          $entity->setCountryCurrencyCode($data->country_currency_code);

        if( isset($data->daily_limit) )
          $entity->setDailyLimit($data->daily_limit);


//Rahul
        if( isset($data->daily_accumulate_amount) )
            $entity->setDailyAccumulateAmount($data->daily_accumulate_amount);

        if( isset($data->min_value) )
            $entity->setMinValue($data->min_value);

        if( isset($data->max_value) )
            $entity->setMaxValue($data->max_value);

        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if( isset($data->deleted_at) )
            $entity->setDeletedAt(IappsDateTime::fromUnix($data->deleted_at));

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findAll($limit, $page)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_holding_account.corporate_service');
        $this->db->where('deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServiceCollection(), $total);
        }

        return false;
    }

    public function findByTransactionTypeAndCountryCurrencyCode($transaction_type_id, $country_currency_code)
    {
        $this->db->select('*');
        $this->db->from('iafb_holding_account.corporate_service');
        $this->db->where('transaction_type_id', $transaction_type_id);
        $this->db->where('country_currency_code', $country_currency_code);
        $this->db->where('deleted_at', NULL);
        $this->db->limit(1);

        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->start_cache(); //to cache active record query
        $this->db->select('*');

        $this->db->from('iafb_holding_account.corporate_service');
        $this->db->where('id', $id);
        if(!$deleted) 
        {
            $this->db->where('deleted_at', NULL);
        }
        $this->db->stop_cache();

        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            //return $this->mapCollection($query->result(), new CorporateServiceCollection(),$query->num_rows());
            return $this->map($query->row());
        }

        return false;

    }

    public function insert(CorporateService $serv){
        $this->db->set('id', $serv->getId());
        $this->db->set('country_code', $serv->getCountryCode());
        $this->db->set('service_provider_id', $serv->getServiceProviderId());
        $this->db->set('name', $serv->getName());
        $this->db->set('description', $serv->getDescription());
        $this->db->set('transaction_type_id', $serv->getTransactionTypeId());
        $this->db->set('country_currency_code', $serv->getCountryCurrencyCode());
        $this->db->set('daily_limit', $serv->getDailyLimit());
        if( $serv instanceof HoldingAccountCorporateService )
        {
            $this->db->set('min_value', $serv->getMinValue());
            $this->db->set('max_value', $serv->getMaxValue());
        }
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $serv->getCreatedBy());

        if( $this->db->insert('iafb_holding_account.corporate_service') )
        {
            return true;
        }

        return false;
    }

    public function update(CorporateService $serv){
        //non null value
        if( $serv->getCountryCode() != NULL)
            $this->db->set('country_code', $serv->getCountryCode());
        if( $serv->getServiceProviderId() != NULL)
            $this->db->set('service_provider_id', $serv->getServiceProviderId());
        if( $serv->getName() != NULL)
            $this->db->set('name', $serv->getName());
        if( $serv->getDescription() != NULL)
            $this->db->set('description', $serv->getDescription());
        if( $serv->getCountryCurrencyCode() != NULL)
            $this->db->set('country_currency_code', $serv->getCountryCurrencyCode());
        if( $serv->getDailyLimit() != NULL )
            $this->db->set('daily_limit', $serv->getDailyLimit());
        if( $serv instanceof HoldingAccountCorporateService )
        {
            if( $serv->getMinValue() != NULL )
                $this->db->set('min_value', $serv->getMinValue());
            if( $serv->getMaxValue() != NULL )
                $this->db->set('max_value', $serv->getMaxValue());
        }
        
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $serv->getUpdatedBy());
        $this->db->where('id', $serv->getId());

        if( $this->db->update('iafb_holding_account.corporate_service') )
        {
            return true;
        }

        return false;
    }

    public function delete(CorporateService $serv){
        
    }
    
// Rahul    
	public function updateAccumulateAmount( $total_net_amount, $item_id){
		if( $total_net_amount != NULL ) {
			$this->db->set('daily_accumulate_amount', $total_net_amount);
			$this->db->set('daily_accumulate_last_run', IappsDateTime::now()->getUnix());
		}
		$this->db->set('updated_at', IappsDateTime::now()->getUnix());
		$this->db->where('id', $item_id);
		if( $this->db->update('iafb_holding_account.corporate_service') ) {
			return true;
		}
		return false;
	}
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}
