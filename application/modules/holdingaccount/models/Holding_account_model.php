<?php

use Iapps\HoldingAccountService\HoldingAccount\IHoldingAccountDataMapper;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccount;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\HoldingAccount\HoldingAccountCollection;

class Holding_account_model extends Base_Model
                    implements IHoldingAccountDataMapper{

    private $table_name = 'iafb_holding_account.holding_account';

    private $table_fields = 'ha.id as holding_account_id,
                           ha.holdingAccountID,
                           ha.user_profile_id,
                           ha.reference_id,
                           ha.holding_account_type_id,
                           ts.code as holding_account_type_code,
                           ts.description as holding_account_type_desc,
                           tsg.id as holding_account_type_group_id,
                           tsg.code as holding_account_type_group_code,
                           tsg.description as holding_account_type_group_desc,
                           ha.country_currency_code,
                           ha.is_active,
                           ha.balance,       
                           ha.created_at,
                           ha.created_by,
                           ha.updated_at,
                           ha.updated_by,
                           ha.deleted_at,
                           ha.deleted_by';


    public function map(\stdClass $data)
    {
        $entity = new HoldingAccount();

        if( isset($data->holding_account_id) )
            $entity->setId($data->holding_account_id);

        if( isset($data->holdingAccountID) )
            $entity->setHoldingAccountID($data->holdingAccountID);

        if( isset($data->user_profile_id) )
            $entity->setUserProfileId($data->user_profile_id);

        if( isset($data->reference_id) )
            $entity->setReferenceId($data->reference_id);

        if( isset($data->holding_account_type_id) )
            $entity->getHoldingAccountType()->setId($data->holding_account_type_id);

        if( isset($data->holding_account_type_code) )
            $entity->getHoldingAccountType()->setCode($data->holding_account_type_code);

        if( isset($data->holding_account_type_desc) )
            $entity->getHoldingAccountType()->setDescription($data->holding_account_type_desc);

        if( isset($data->holding_account_type_group_id) )
            $entity->getHoldingAccountType()->getGroup()->setId($data->holding_account_type_group_id);

        if( isset($data->holding_account_type_group_code) )
            $entity->getHoldingAccountType()->getGroup()->setCode($data->holding_account_type_group_code);

        if( isset($data->holding_account_type_group_desc) )
            $entity->getHoldingAccountType()->getGroup()->setDescription($data->holding_account_type_group_desc);

        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);

        if( isset($data->is_active) )
            $entity->setIsActive($data->is_active);

        if( isset($data->balance) )
            $entity->getBalance()->setOriginalValue($data->balance);

        if( isset($data->created_at) )
            $entity->getCreatedAt()->setDateTimeUnix($data->created_at);

        if( isset($data->created_by) )
            $entity->setCreatedBy($data->created_by);

        if( isset($data->updated_at) )
            $entity->getUpdatedAt()->setDateTimeUnix($data->updated_at);

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
        $this->db->from($this->table_name .' ha');
        $this->db->join('iafb_holding_account.system_code ts', 'ha.holding_account_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        if( !$deleted )
        {
            $this->db->where('ha.deleted_at', NULL);
        }
        $this->db->where('ha.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByReferenceIdArr(array $reference_id_arr, $limit=null, $page=null)
    {
        if ($limit && $page) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache();
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name .' ha');
        $this->db->join('iafb_holding_account.system_code ts', 'ha.holding_account_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('ha.deleted_at', NULL);
        $this->db->where_in('ha.reference_id', $reference_id_arr);


        $this ->db->stop_cache();

        $total = $this->db->count_all_results();
        if ($limit && $page) {
            $this->db->limit($limit,$offset);
        }

        $query = $this ->db->get();
        $this->db->flush_cache();

        if( $query -> num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountCollection(), $total);
        }

        return false;
    }

    public function findByUserProfileId($user_profile_id)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name .' ha');
        $this->db->join('iafb_holding_account.system_code ts', 'ha.holding_account_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('ha.deleted_at', NULL);
        $this->db->where('ha.user_profile_id', $user_profile_id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountCollection(), $query->num_rows());
        }

        return false;
    }


    public function findByParam(HoldingAccount $holding_account)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name .' ha');
        $this->db->join('iafb_holding_account.system_code ts', 'ha.holding_account_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('ha.deleted_at', NULL);
        if ($holding_account->getHoldingAccountID()) {
            $this->db->where('ha.holdingAccountID', $holding_account->getHoldingAccountID());
        }
        if ($holding_account->getReferenceId()) {
            $this->db->where('ha.reference_id', $holding_account->getReferenceId());
        }
        if ($holding_account->getId()) {
            $this->db->where('ha.id', $holding_account->getId());
        }
        if ($holding_account->getUserProfileId()) {
            $this->db->where('ha.user_profile_id', $holding_account->getUserProfileId());
        }
        if ($holding_account->getCountryCurrencyCode()) {
            $this->db->where('ha.country_currency_code', $holding_account->getCountryCurrencyCode());
        }
        if($holding_account->getHoldingAccountType()) {
            if($holding_account->getHoldingAccountType()->getId()) {
                $this->db->where('ha.holding_account_type_id', $holding_account->getHoldingAccountType()->getId());
            }
        }
        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountCollection(), $query->num_rows());
        }

        return false;
    }



    public function insertHoldingAccount(HoldingAccount $holding_account)
    {
        $this->db->set('id', $holding_account->getId());
        $this->db->set('holdingAccountID', $holding_account->getHoldingAccountID());
        $this->db->set('user_profile_id', $holding_account->getUserProfileId());
        $this->db->set('reference_id', $holding_account->getReferenceId());
        $this->db->set('holding_account_type_id', $holding_account->getHoldingAccountType()->getId());
        $this->db->set('country_currency_code', $holding_account->getCountryCurrencyCode());
        $this->db->set('is_active', $holding_account->getIsActive());
        $this->db->set('balance', $holding_account->getBalance()->getValue());
        $this->db->set('created_by', $holding_account->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if( $this->db->insert($this->table_name) )
        {
            return true;
        }

        return false;
    }

    public function updateHoldingAccountValue(HoldingAccount $holding_account)
    {
        $this->db->set('balance', $holding_account->getBalance()->getValue());
        $this->db->set('updated_by', $holding_account->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());

        $this->db->where('id', $holding_account->getId());
        $this->db->where('balance', $holding_account->getBalance()->getOriginalValue());

        $this->db->update($this->table_name);
        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }

    public function updateHoldingAccount(HoldingAccount $holding_account)
    {
        if( $holding_account->getIsActive() !== NULL )
            $this->db->set('is_active', $holding_account->getIsActive());

        $this->db->set('updated_by', $holding_account->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());

        $this->db->where('id', $holding_account->getId());

        $this->db->update($this->table_name);
        if( $this->db->affected_rows() > 0 )
        {
            return true;
        }

        return false;
    }

    
}
