<?php

use Iapps\HoldingAccountService\HoldingAccountConfig\IHoldingAccountConfigDataMapper;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfig;
use Iapps\HoldingAccountService\HoldingAccountConfig\HoldingAccountConfigCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Helper\GuidGenerator;

class Holding_account_config_model extends Base_Model implements IHoldingAccountConfigDataMapper{

    private $table_name = 'iafb_holding_account.holding_account_config';

    private $table_fields = 'hac.id as holding_account_config_id,
                           hac.module_code,
                           hac.is_supported,
                           hac.holding_account_id,
                           hac.created_at,
                           hac.created_by,
                           hac.updated_at,
                           hac.updated_by,
                           hac.deleted_at,
                           hac.deleted_by';

    public function map(stdClass $data)
    {
        $entity = new HoldingAccountConfig();
        if( isset($data->holding_account_config_id) )
            $entity->setId($data->holding_account_config_id);

        if( isset($data->module_code) )
            $entity->setModuleCode($data->module_code);

        if( isset($data->is_supported) )
            $entity->setIsSupported($data->is_supported);

        if( isset($data->holding_account_id) )
            $entity->setHoldingAccountId($data->holding_account_id);

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

    public function findById($id, $deleted = false)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name .' hac');
        $this->db->where('hac.id', $id);
        if( !$deleted )
            $this->db->where('hac.deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAll($limit, $page)
    {
        $total = 0;
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name .' hac');
        $this->db->where('hac.deleted_at', NULL);
        $this->db->stop_cache();
        
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountConfigCollection(), $total);
        }

        return false;
    }

    public function findAllByFilter(HoldingAccountConfig $config, $limit, $page)
    {
        $offset = ($page - 1) * $limit;

        $this->db->start_cache(); //to cache active record query
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name .' hac');
        if($config->getModuleCode())
        {
            $this->db->where('hac.module_code', $config->getModuleCode());
        }
        if($config->getHoldingAccountId()) {
            $this->db->where('hac.holding_account_id', $config->getHoldingAccountId());
        }

        if($config->getIsSupported()!==NULL) {
            $this->db->where('hac.is_supported', $config->getIsSupported());
        }
        $this->db->where('hac.deleted_at', NULL);
        $this->db->stop_cache();

        $total = $this->db->count_all_results(); //to get total num of result w/o limit

        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountConfigCollection(), $total);
        }

        return false;
    }

    public function insert(HoldingAccountConfig $config)
    {
        $this->db->set('id', $config->getId());
        $this->db->set('holding_account_id', $config->getHoldingAccountId());
        $this->db->set('module_code', $config->getModuleCode());
        $this->db->set('is_supported', $config->getIsSupported());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $config->getCreatedBy());

        if( $this->db->insert($this->table_name) )
        {
            return true;
        }

        return false;
    }

    public function update(HoldingAccountConfig $config)
    {
        $this->db->set('module_code', $config->getModuleCode());
        $this->db->set('is_supported', $config->getIsSupported());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $config->getUpdatedBy());
        $this->db->where('id', $config->getId());

        if( $this->db->update($this->table_name) )
        {
            return true;
        }

        return false;
    }
}