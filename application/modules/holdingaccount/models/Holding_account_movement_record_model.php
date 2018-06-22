<?php

use Iapps\HoldingAccountService\HoldingAccountMovementRecord\IHoldingAccountMovementRecordDataMapper;
use Iapps\HoldingAccountService\HoldingAccountMovementRecord\HoldingAccountMovementRecord;
use Iapps\HoldingAccountService\HoldingAccountMovementRecord\HoldingAccountMovementRecordCollection;
use Iapps\Common\Core\IappsDateTime;

class Holding_account_movement_record_model extends Base_Model
                                    implements IHoldingAccountMovementRecordDataMapper
{
    private $table_name = 'iafb_holding_account.holding_account_movement_record';

    private $table_fields = 'hamr.id as holding_account_movement_record_id,
                           hamr.holding_account_id,
                           hamr.module_code,
                           hamr.transactionID,
                           hamr.movement_type,
                           hamr.amount,
                           hamr.last_balance,
                           hamr.description,
                           hamr.created_at,
                           hamr.created_by,
                           hamr.updated_at,
                           hamr.updated_by,
                           hamr.deleted_at,
                           hamr.deleted_by';

    public function map(\stdClass $data)
    {
        $entity = new HoldingAccountMovementRecord();

        if( isset($data->holding_account_movement_record_id) )
            $entity->setId($data->holding_account_movement_record_id);

        if( isset($data->holding_account_id) )
            $entity->setHoldingAccountId($data->holding_account_id);

        if( isset($data->module_code) )
            $entity->setModuleCode($data->module_code);

        if( isset($data->transactionID) )
            $entity->setTransactionID($data->transactionID);

        if( isset($data->movement_type) )
            $entity->setMovementType($data->movement_type);

        if( isset($data->amount) )
           $entity->getAmount()->setValue($data->amount);

        if( isset($data->last_balance) )
           $entity->getLastBalance()->setOriginalEncodedValue($data->last_balance);

        if( isset($data->description) )
            $entity->setDescription($data->description);

        if( isset($data->created_at) )
            $entity->setCreatedAt(IappsDateTime::fromUnix($data->created_at));

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->setUpdatedAt(IappsDateTime::fromUnix($data->updated_at));

        if( isset($data->updated_by) )
            $entity->setUpdatedBy($data->updated_by);

        if( isset($data->deleted_at) )
            $entity->getDeletedAt()->setDateTimeUnix($data->deleted_at);

        if( isset($data->deleted_by) )
            $entity->setDeletedBy($data->deleted_by);

        return $entity;
    }

    public function findById($id, $deleted = false)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' hamr');
        $this->db->where('hamr.id', $id);
        if( !$deleted )
            $this->db->where('hamr.deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByHoldingAccountId($holding_account_id)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' hamr');
        $this->db->where('holding_account_id', $holding_account_id);
        $this->db->where('deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountMovementRecordCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByHoldingAccountIds(array $ids)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' hamr');
        $this->db->where_in('hamr.holding_account_id', $ids);
        if( !$this->getFromCreatedAt()->isNUll() )
            $this->db->where('hamr.created_at >=', $this->getFromCreatedAt()->getUnix());
        if( !$this->getToCreatedAt()->isNUll() )
            $this->db->where('hamr.created_at <=', $this->getToCreatedAt()->getUnix());
        $this->db->where('hamr.deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountMovementRecordCollection(), $query->num_rows());
        }

        return false;
    }

    public function insertRecord(HoldingAccountMovementRecord $movement)
    {
        $this->db->set('id', $movement->getId());
        $this->db->set('holding_account_id', $movement->getHoldingAccountId());
        $this->db->set('module_code', $movement->getModuleCode());
        $this->db->set('transactionID', $movement->getTransactionID());
        $this->db->set('movement_type', $movement->getMovementType());
        $this->db->set('amount', $movement->getAmount()->getValue());
        $this->db->set('last_balance', $movement->getLastBalance()->getEncodedValue());
        $this->db->set('description', $movement->getDescription());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $movement->getCreatedBy());

        if( $this->db->insert($this->table_name) )
        {
            return true;
        }

        return false;
    }
    
    
    
     public function findByParam(HoldingAccountMovementRecord $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' hamr');
        $this->db->where('hamr.deleted_at', NULL);
        
        if($config->getHoldingAccountId()) {
            $this->db->where('hamr.holding_account_id', $config->getHoldingAccountId());
        }

        if($config->getHoldingAccountIdArr()) {
            $this->db->where_in('hamr.holding_account_id', $config->getHoldingAccountIdArr());
        }

        if($config->getDateFrom()){
            $this->db->where('hamr.created_at >=', $config->getDateFrom()->getUnix());
        }

        if($config->getDateTo()){
            $this->db->where('hamr.created_at <=', $config->getDateTo()->getUnix());
        }

        $this->db->order_by('hamr.created_at', 'desc');

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountMovementRecordCollection(), $total);
        }

        return false;
    }
}