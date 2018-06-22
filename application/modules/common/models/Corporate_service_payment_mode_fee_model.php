<?php

use Iapps\Common\CorporateService\CorporateServicePaymentModeFee;
use Iapps\Common\CorporateService\ICorporateServicePaymentModeFeeMapper;
use Iapps\Common\CorporateService\CorporateServicePaymentModeFeeCollection;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\CorporateService\FeeType;

class corporate_service_payment_mode_fee_model extends Base_Model
    implements ICorporateServicePaymentModeFeeMapper{

    public function map(stdClass $data)
    {
        $entity = new CorporateServicePaymentModeFee();

        if( isset($data->corporate_service_payment_mode_fee_id) )
            $entity->setId($data->corporate_service_payment_mode_fee_id);

        if( isset($data->corporate_service_payment_mode_id) )
            $entity->setCorporateServicePaymentModeId($data->corporate_service_payment_mode_id);

        if( isset($data->is_percentage ))
            $entity->setIsPercentage($data->is_percentage);

        if( isset($data->name) )
            $entity->setName($data->name);

        if( isset($data->fee) )
            $entity->setFee($data->fee);

        if( isset($data->converted_fee) )
            $entity->setConvertedFee($data->converted_fee);

        if( isset($data->converted_fee_country_currency_code) )
            $entity->setConvertedFeeCountryCurrencyCode($data->converted_fee_country_currency_code);

        if( isset($data->service_provider_id) )
            $entity->setServiceProviderId($data->service_provider_id);

        if( isset($data->role_id) )
            $entity->setRoleId($data->role_id);

        if( isset($data->fee_type_id) )
            $entity->getFeeType()->setId($data->fee_type_id);

        if( isset($data->fee_type) )
            $entity->getFeeType()->setCode($data->fee_type);

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
        $this->db->select('pmf.id as corporate_service_payment_mode_fee_id,
                           pmf.corporate_service_payment_mode_id,
                           pmf.is_percentage,
                           pmf.name,
                           pmf.fee,
                           pmf.converted_fee,
                           pmf.converted_fee_country_currency_code,
                           pmf.service_provider_id,
                           pmf.role_id,
                           pmf.fee_type_id,
                           usf.code as fee_type,
                           pmf.created_at,
                           pmf.created_by,
                           pmf.updated_at,
                           pmf.updated_by,
                           pmf.deleted_at,
                           pmf.deleted_by');
        $this->db->from('iafb_holding_account.corporate_service_payment_mode_fee pmf');
        $this->db->join('iafb_holding_account.system_code usf', 'pmf.fee_type_id = usf.id');
        $this->db->join('iafb_holding_account.system_code_group usft', 'usf.system_code_group_id = usft.id');
        $this->db->where('pmf.id', $id);
        $this->db->where('usft.code', FeeType::getSystemGroupCode());
        if( !$deleted ) {
            $this->db->where('pmf.deleted_at', NULL);
        }
        $this->db->where('pmf.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAllByCorporateServicePaymentModeId($corporate_service_payment_mode_id)
    {
        $this->db->select('pmf.id as corporate_service_payment_mode_fee_id,
                           pmf.corporate_service_payment_mode_id,
                           pmf.is_percentage,
                           pmf.name,
                           pmf.fee,
                           pmf.converted_fee,
                           pmf.converted_fee_country_currency_code,
                           pmf.service_provider_id,
                           pmf.role_id,
                           pmf.fee_type_id,
                           usf.code as fee_type,
                           pmf.created_at,
                           pmf.created_by,
                           pmf.updated_at,
                           pmf.updated_by,
                           pmf.deleted_at,
                           pmf.deleted_by');
        $this->db->from('iafb_holding_account.corporate_service_payment_mode_fee pmf');
        $this->db->join('iafb_holding_account.system_code usf', 'pmf.fee_type_id = usf.id');
        $this->db->join('iafb_holding_account.system_code_group usft', 'usf.system_code_group_id = usft.id');
        $this->db->where('pmf.corporate_service_payment_mode_id', $corporate_service_payment_mode_id);
        $this->db->where('pmf.deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeFeeCollection(), 0);
        }

        return false;
    }

    public function findAllByCorporateServicePaymentModeIds($corporate_service_payment_mode_ids)
    {
        $this->db->select('pmf.id as corporate_service_payment_mode_fee_id,
                           pmf.corporate_service_payment_mode_id,
                           pmf.is_percentage,
                           pmf.name,
                           pmf.fee,
                           pmf.converted_fee,
                           pmf.converted_fee_country_currency_code,
                           pmf.service_provider_id,
                           pmf.role_id,
                           pmf.fee_type_id,
                           usf.code as fee_type,
                           pmf.created_at,
                           pmf.created_by,
                           pmf.updated_at,
                           pmf.updated_by,
                           pmf.deleted_at,
                           pmf.deleted_by');
        $this->db->from('iafb_holding_account.corporate_service_payment_mode_fee pmf');
        $this->db->join('iafb_holding_account.system_code usf', 'pmf.fee_type_id = usf.id');
        $this->db->join('iafb_holding_account.system_code_group usft', 'usf.system_code_group_id = usft.id');
        $this->db->where_in('pmf.corporate_service_payment_mode_id', $corporate_service_payment_mode_ids);
        $this->db->where('pmf.deleted_at', NULL);

        $query = $this->db->get();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new CorporateServicePaymentModeFeeCollection(), 0);
        }

        return false;
    }

    public function insert(CorporateServicePaymentModeFee $payment_mode_fee)
    {
        $this->db->set('id', $payment_mode_fee->getId());
        $this->db->set('corporate_service_payment_mode_id', $payment_mode_fee->getCorporateServicePaymentModeId());
        $this->db->set('is_percentage', $payment_mode_fee->getIsPercentage());
        $this->db->set('name', $payment_mode_fee->getName());
        $this->db->set('fee', $payment_mode_fee->getFee());
        $this->db->set('converted_fee', $payment_mode_fee->getConvertedFee());
        $this->db->set('converted_fee_country_currency_code', $payment_mode_fee->getConvertedFeeCountryCurrencyCode());
        $this->db->set('service_provider_id', $payment_mode_fee->getServiceProviderId());
        $this->db->set('role_id', $payment_mode_fee->getRoleId());
        $this->db->set('fee_type_id', $payment_mode_fee->getFeeType()->getId());
        $this->db->set('created_by', $payment_mode_fee->getCreatedBy());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());

        if( $this->db->insert('iafb_holding_account.corporate_service_payment_mode_fee') )
        {
            return true;
        }

        return false;
    }

    public function update(CorporateServicePaymentModeFee $payment_mode_fee)
    {
        $this->db->set('is_percentage', $payment_mode_fee->getIsPercentage());
        $this->db->set('name', $payment_mode_fee->getName());
        $this->db->set('fee', $payment_mode_fee->getFee());
        $this->db->set('converted_fee', $payment_mode_fee->getConvertedFee());
        $this->db->set('converted_fee_country_currency_code', $payment_mode_fee->getConvertedFeeCountryCurrencyCode());
        $this->db->set('service_provider_id', $payment_mode_fee->getServiceProviderId());
        $this->db->set('role_id', $payment_mode_fee->getRoleId());
        $this->db->set('fee_type_id', $payment_mode_fee->getFeeType()->getId());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());
        $this->db->set('updated_by', $payment_mode_fee->getUpdatedBy());
        $this->db->where('id', $payment_mode_fee->getId());

        if( $this->db->update('iafb_holding_account.corporate_service_payment_mode_fee') )
        {
            return true;
        }

        return false;
    }

    public function delete(CorporateServicePaymentModeFee $payment_mode_fee)
    {
        $this->db->set('deleted_at', IappsDateTime::now()->getUnix());
        $this->db->set('deleted_by', $payment_mode_fee->getDeletedBy());
        $this->db->where('id', $payment_mode_fee->getId());

        if( $this->db->update('iafb_holding_account.corporate_service_payment_mode_fee') )
        {
            return true;
        }

        return false;
    }
}