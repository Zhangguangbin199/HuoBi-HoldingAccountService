<?php

use Iapps\HoldingAccountService\HoldingAccountTransaction\IHoldingAccountTransactionItemDataMapper;
use Iapps\Common\Transaction\TransactionItem;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItem;
use Iapps\HoldingAccountService\HoldingAccountTransaction\ItemType;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionItemCollection;

class Holding_account_transaction_item_model extends Base_Model
                                    implements IHoldingAccountTransactionItemDataMapper{

    public function map(stdClass $data)
    {
        $entity = new HoldingAccountTransactionItem();

        if( isset($data->transaction_item_id) )
            $entity->setId($data->transaction_item_id);

        if( isset($data->item_type_id) )
            $entity->getItemType()->setId($data->item_type_id);

        if( isset($data->item_type_code))
            $entity->getItemType()->setCode($data->item_type_code);

        if( isset($data->item_type_name))
            $entity->getItemType()->setDisplayName($data->item_type_name);

        if( isset($data->item_type_group_id))
            $entity->getItemType()->getGroup()->setId($data->item_type_group_id);

        if( isset($data->item_type_group_code))
            $entity->getItemType()->getGroup()->setId($data->item_type_group_code);

        if( isset($data->item_type_group_name))
            $entity->getItemType()->getGroup()->setId($data->item_type_group_name);

        if( isset($data->item_id) )
            $entity->setItemId($data->item_id);

        if( isset($data->name) )
            $entity->setName($data->name);
        if( isset($data->description) )
            $entity->setDescription($data->description);
        if( isset($data->quantity) )
            $entity->setQuantity($data->quantity);
        if( isset($data->refunded_quantity) )
            $entity->setRefundedQuantity($data->refunded_quantity);
        if( isset($data->unit_price) )
            $entity->setUnitPrice($data->unit_price);
        if( isset($data->net_amount) )
            $entity->setNetAmount($data->net_amount);

        if( isset($data->line_no) )
            $entity->setLineNumber($data->line_no);
        if( isset($data->transaction_id) )
            $entity->setTransactionId($data->transaction_id);
        if( isset($data->ref_transaction_item_id) )
            $entity->setRefTransactionItemId($data->ref_transaction_item_id);
        if( isset($data->agent_id) )
            $entity->setAgentId($data->agent_id);

        //added new fields
        if( isset($data->cost_country_currency_code) )
            $entity->setCostCountryCurrencyCode($data->cost_country_currency_code);
        if( isset($data->cost) )
            $entity->setCost($data->cost);

        if( isset($data->is_commission) )
            $entity->setIsCommission($data->is_commission);

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
        $this->db->select('ti.id as transaction_item_id,
                           ti.item_type_id,
                           sc.code as item_type_code,
                           sc.display_name as item_type_name,
                           scg.id as item_type_group_id,
                           scg.code as item_type_group_code,
                           scg.display_name as item_type_group_name,
                           ti.item_id,
                           ti.name,
                           ti.description,
                           ti.quantity,
                           ti.refunded_quantity,
                           ti.unit_price,
                           ti.net_amount,
                           ti.line_no,
                           ti.transaction_id,
                           ti.ref_transaction_item_id,
                           ti.agent_id,
                           ti.cost_country_currency_code,
                           ti.cost,
                           ti.is_commission,
                           ti.created_at,
                           ti.created_by,
                           ti.updated_at,
                           ti.updated_by,
                           ti.deleted_at,
                           ti.deleted_by
                        ');
        $this->db->from('iafb_holding_account.transaction_item ti');
        $this->db->join('iafb_holding_account.system_code sc', 'ti.item_type_id = sc.id');
        $this->db->join('iafb_holding_account.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', ItemType::getSystemGroupCode());
        if(!$deleted)
        {
            $this->db->where('ti.deleted_at', NULL);
        }
        $this->db->where('ti.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByTransactionId($transaction_id)
    {
        $this->db->select('ti.id as transaction_item_id,
                           ti.item_type_id,
                           sc.code as item_type_code,
                           sc.display_name as item_type_name,
                           scg.id as item_type_group_id,
                           scg.code as item_type_group_code,
                           scg.display_name as item_type_group_name,
                           ti.item_id,
                           ti.name,
                           ti.description,
                           ti.quantity,
                           ti.refunded_quantity,
                           ti.unit_price,
                           ti.net_amount,
                           ti.line_no,
                           ti.transaction_id,
                           ti.ref_transaction_item_id,
                           ti.agent_id,
                           ti.cost_country_currency_code,
                           ti.cost,
                           ti.is_commission,
                           ti.created_at,
                           ti.created_by,
                           ti.updated_at,
                           ti.updated_by,
                           ti.deleted_at,
                           ti.deleted_by
                        ');
        $this->db->from('iafb_holding_account.transaction_item ti');
        $this->db->join('iafb_holding_account.system_code sc', 'ti.item_type_id = sc.id');
        $this->db->join('iafb_holding_account.system_code_group scg', 'sc.system_code_group_id = scg.id');
        $this->db->where('scg.code', ItemType::getSystemGroupCode());
        $this->db->where('ti.transaction_id', $transaction_id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionItemCollection(), $query->num_rows());
        }

        return false;
    }

    public function insert(TransactionItem $item)
    {
        $this->db->set('id', $item->getId());
        $this->db->set('item_type_id', $item->getItemType()->getId());
        $this->db->set('item_id', $item->getItemId());
        $this->db->set('name', $item->getName());
        $this->db->set('description', $item->getDescription());
        $this->db->set('quantity', $item->getQuantity());
        $this->db->set('refunded_quantity', $item->getRefundedQuantity());
        $this->db->set('unit_price', $item->getUnitPrice());
        $this->db->set('net_amount', $item->getNetAmount());
        $this->db->set('line_no', $item->getLineNumber());
        $this->db->set('transaction_id', $item->getTransactionId());
        $this->db->set('ref_transaction_item_id', $item->getRefTransactionItemId());
        $this->db->set('agent_id', $item->getAgentId());
        $this->db->set('cost_country_currency_code', $item->getCostCountryCurrencyCode());
        $this->db->set('cost', $item->getCost());
        $this->db->set('is_commission', $item->getIsCommission());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $item->getCreatedBy());

        if( $this->db->insert('iafb_holding_account.transaction_item') )
            return true;

        return false;
    }

    public function updateAgentId(TransactionItem $item)
    {
        $updated_at = IappsDateTime::now()->getUnix();

        $this->db->set('agent_id', $item->getAgentId());
        $this->db->set('is_commission', $item->getIsCommission());
        $this->db->set('updated_at', $updated_at);
        $this->db->set('updated_by', $item->getUpdatedBy());

        $this->db->where('id', $item->getId());
        $this->db->update('iafb_holding_account.transaction_item');
        if( $this->db->affected_rows() > 0 )
        {
            $item->getUpdatedAt()->setDateTimeUnix($updated_at);
            return true;
        }

        return false;
    }


    public function findByParam(TransactionItem $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query


        $this->db->select('id ,
                           item_type_id,
                           item_id,
                           name,
                           description,
                           quantity,
                           refunded_quantity,
                           unit_price,
                           net_amount,
                           line_no,
                           transaction_id,
                           ref_transaction_item_id,
                           agent_id,
                           cost_country_currency_code,
                           cost,
                           is_commission,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_bill.transaction_item');
        $this->db->where('deleted_at', NULL);

        if($config->getTransactionID()) {
            $this->db->like('transaction_id', $config->getTransactionID());
        }

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();

        //print_r($query);

        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionItemCollection(), $total);
        }

        return false;
    }

// Rahul
    public function getTotalAmountBySystemCode( $item_type_id, $item_id, $start_timestamp, $end_timestamp ) {
        $this->db->select('SUM(abs(net_amount)) as net_amount'); 
        $this->db->from('iafb_holding_account.transaction_item');
        $this->db->where('deleted_at', NULL);
		$this->db->where('item_type_id', $item_type_id);
        $this->db->where('item_id', $item_id);
        $this->db->where('created_at >=', $start_timestamp);
        $this->db->where('created_at <=', $end_timestamp);
        
        $query = $this->db->get();
      //print_r($this->db->last_query()); die("    ff");
        if( $query->num_rows() > 0) {
            return $query->row()->net_amount;
        }

        return false;
    }


    public function findByTransactionIdArrAndParam(TransactionItem $item, $transaction_id_arr)
    {
        $this->db->select('id ,
                           item_type_id,
                           item_id,
                           name,
                           description,
                           quantity,
                           refunded_quantity,
                           unit_price,
                           net_amount,
                           line_no,
                           transaction_id,
                           ref_transaction_item_id,
                           agent_id,
                           cost_country_currency_code,
                           cost,
                           created_at,
                           created_by,
                           updated_at,
                           updated_by,
                           deleted_at,
                           deleted_by');
        $this->db->from('iafb_holding_account.transaction_item');
        $this->db->where('deleted_at', NULL);
        $this->db->where_in('transaction_id', $transaction_id_arr);
        if($item->getAgentId()) {
            $this->db->where('agent_id', $item->getAgentId());
        }
        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionItemCollection(), $query->num_rows());
        }

        return false;
    }



}
