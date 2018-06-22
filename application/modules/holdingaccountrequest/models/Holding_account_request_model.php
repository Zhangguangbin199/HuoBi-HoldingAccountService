<?php

use Iapps\HoldingAccountService\HoldingAccountRequest\IHoldingAccountRequestDataMapper;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequest;
use Iapps\Common\Core\IappsDateTime;
use Iapps\HoldingAccountService\HoldingAccountRequest\RequestStatus;
use Iapps\HoldingAccountService\HoldingAccountRequest\HoldingAccountRequestCollection;

class Holding_account_request_model extends Base_Model
                            implements IHoldingAccountRequestDataMapper{

    private $table_name = 'iafb_holding_account.holding_account_request';

    private $table_fields = 'har.id as holding_account_request_id,
                           har.module_code,
                           har.transactionID,
                           har.request_token,
                           har.request_type_id,
                           ts.code as request_type_code,
                           ts.description as request_type_description,
                           tsg.id as request_type_group_id,
                           tsg.code as request_type_group_code,
                           tsg.description as request_type_group_description,
                           har.holding_account_id,
                           har.status,
                           har.amount,
                           har.to_amount,
                           har.display_rate,
                           har.payment_code,
                           har.payment_request_id,
                           har.reference_no,
                           har.expired_at,
                           har.created_at,
                           har.created_by,
                           har.updated_at,
                           har.updated_by,
                           har.deleted_at,
                           har.deleted_by';

    public function map(\stdClass $data)
    {
        $entity = new  HoldingAccountRequest();

        if( isset($data->holding_account_request_id) )
            $entity->setId($data->holding_account_request_id);

        if( isset($data->module_code) )
            $entity->setModuleCode($data->module_code);

        if( isset($data->transactionID) )
            $entity->setTransactionID($data->transactionID);

        if( isset($data->request_token) )
            $entity->setRequestToken($data->request_token);

        if( isset($data->request_type_id) )
            $entity->getRequestType()->setId($data->request_type_id);

        if( isset($data->request_type_code) )
            $entity->getRequestType()->setCode($data->request_type_code);

        if( isset($data->request_type_description) )
            $entity->getRequestType()->setDescription($data->request_type_description);

        if( isset($data->request_type_group_id) )
            $entity->getRequestType()->getGroup()->setId($data->request_type_group_id);

        if( isset($data->request_type_group_code) )
            $entity->getRequestType()->getGroup()->setCode($data->request_type_group_code);

        if( isset($data->request_type_group_description) )
            $entity->getRequestType()->getGroup()->setDescription($data->request_type_group_description);

        if( isset($data->holding_account_id) )
            $entity->getHoldingAccount()->setId($data->holding_account_id);

        if( isset($data->status) )
            $entity->setStatus($data->status);

        if( isset($data->amount) )
            $entity->setAmount($data->amount);

        if( isset($data->to_amount) )
            $entity->setToAmount($data->to_amount);

        if( isset($data->display_rate) )
            $entity->setDisplayRate($data->display_rate);

        if( isset($data->payment_code) )
            $entity->setPaymentCode($data->payment_code);

        if( isset($data->payment_request_id) )
            $entity->setPaymentRequestId($data->payment_request_id);

        if( isset($data->reference_no) )
            $entity->setReferenceNo($data->reference_no);

        if( isset($data->expired_at) )
            $entity->getExpiredAt()->setDateTimeUnix($data->expired_at);

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
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        if( !$deleted )
            $this->db->where('har.deleted_at', NULL);
        $this->db->where('har.id', $id);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findByToken($token)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('har.deleted_at', NULL);
        $this->db->where('har.request_token', $token);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findActiveByHoldingAccountId($holding_account_id)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('har.deleted_at', NULL);
        $this->db->where('har.holding_account_id', $holding_account_id);
        $this->db->where('har.status', RequestStatus::PENDING);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountRequestCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByHoldingAccountId($holding_account_id, $from_date, $to_date, $requestType = array(), $status = array())
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('har.deleted_at', NULL);
        $this->db->where('har.holding_account_id', $holding_account_id);
        if( isset($from_date) )
            $this->db->where('har.created_at >', $from_date);
        if( isset($to_date) )
            $this->db->where('har.created_at <=', $to_date);
        if( count($status) > 0)
            $this->db->where_in('har.status', $status);
        if( count($requestType) > 0)
            $this->db->where_in('ts.code', $requestType);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountRequestCollection(), $query->num_rows());
        }

        return false;
    }

    public function findActiveByUser($user_profile_id)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->join('iafb_holding_account.holding_account ha', 'har.holding_account_id = ha.id');
        $this->db->where('har.deleted_at', NULL);
        $this->db->where('ha.user_profile_id', $user_profile_id);
        $this->db->where('har.status', RequestStatus::PENDING);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountRequestCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByParam(HoldingAccountRequest $request)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->join('iafb_holding_account.holding_account ha', 'har.holding_account_id = ha.id');
        $this->db->where('har.deleted_at', NULL);


        if($request->getHoldingAccount()->getId()) {
            $this->db->where('har.holding_account_id', $request->getHoldingAccount()->getId());
        }

        if($request->getHoldingAccountIdArr()) {
            $this->db->where_in('har.holding_account_id', $request->getHoldingAccountIdArr());
        }

        if($request->getRequestType()) {
            if($request->getRequestType()->getId()) {
                $this->db->where('har.request_type_id', $request->getRequestType()->getId());
            }
        }


        if($request->getDateFrom()){
            $this->db->where('har.created_at >=', $request->getDateFrom()->getUnix());
        }

        if($request->getDateTo()){
            $this->db->where('har.created_at <=', $request->getDateTo()->getUnix());
        }

        if($request->getStatus()) {
            $this->db->where('har.status', $request->getStatus());
        }
        $this->db->order_by('har.created_at DESC');

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountRequestCollection(), $query->num_rows());
        }

        return false;
    }

    public function findHoldingRequest(HoldingAccountRequest $request, $limit=null, $page=null)
    {
        if ($limit && $page) {
            $offset = ($page - 1) * $limit;
        }

        $this->db->start_cache();
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->join('iafb_holding_account.holding_account ha', 'har.holding_account_id = ha.id');
        $this->db->where('har.deleted_at', NULL);


        if($request->getHoldingAccountIdArr() && $request->getCreatedBy()) {
            $this->db->where_in('(har.holding_account_id', $request->getHoldingAccountIdArr(), FALSE);
            $this->db->or_where("har.created_by ='" . $request->getCreatedBy() . "')", NULL, FALSE);
        }else{
            if($request->getHoldingAccountIdArr()) {
                $this->db->where_in('har.holding_account_id', $request->getHoldingAccountIdArr());
            }
            if($request->getCreatedBy()) {
                $this->db->where('har.created_by', $request->getCreatedBy());
            }
        }
        if($request->getRequestType()) {
            if($request->getRequestType()->getId()) {
                $this->db->where('har.request_type_id', $request->getRequestType()->getId());
            }
        }


        if($request->getDateFrom()){
            $this->db->where('har.created_at >=', $request->getDateFrom()->getUnix());
        }

        if($request->getDateTo()){
            $this->db->where('har.created_at <=', $request->getDateTo()->getUnix());
        }

        if($request->getStatus()) {
            $this->db->where('har.status', $request->getStatus());
        }

        $this->db->order_by('har.created_at DESC');
        $this ->db->stop_cache();

        $total = $this->db->count_all_results();
        if ($limit && $page) {
            $this->db->limit($limit,$offset);
        }

        $query = $this->db->get();
        $this->db->flush_cache();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountRequestCollection(), $total);
        }

        return false;
    }

    public function findByTransactionID($module_code, $transactionID)
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('har.deleted_at', NULL);
        $this->db->where('har.module_code', $module_code);
        $this->db->where('har.transactionID', $transactionID);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findExpiredRequest()
    {
        $this->db->select($this->table_fields);
        $this->db->from($this->table_name . ' har');
        $this->db->join('iafb_holding_account.system_code ts', 'har.request_type_id = ts.id');
        $this->db->join('iafb_holding_account.system_code_group tsg', 'ts.system_code_group_id = tsg.id');
        $this->db->where('har.deleted_at', NULL);
        $this->db->where('har.expired_at <', IappsDateTime::now()->getUnix());
        $this->db->where('har.status', RequestStatus::PENDING);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountRequestCollection(), $query->num_rows());
        }

        return false;
    }

    public function updateRequestStatus(HoldingAccountRequest $request)
    {
        $this->db->set('status', $request->getStatus());
        $this->db->set('reference_no', $request->getReferenceNo());
        if( $request->getPaymentRequestId() != NULL )
            $this->db->set('payment_request_id', $request->getPaymentRequestId());
        $this->db->set('updated_by', $request->getUpdatedBy());
        $this->db->set('updated_at', IappsDateTime::now()->getUnix());

        $this->db->where('id', $request->getId());
        $this->db->where('status <>', $request->getStatus());

        $this->db->update($this->table_name);
        if( $this->db->affected_rows() > 0)
            return true;

        return false;
    }

    public function insertRequest(HoldingAccountRequest $request)
    {
        $this->db->set('id', $request->getId());
        $this->db->set('module_code', $request->getModuleCode());
        $this->db->set('transactionID', $request->getTransactionID());
        $this->db->set('request_token', $request->getRequestToken());
        $this->db->set('request_type_id', $request->getRequestType()->getId());
        $this->db->set('holding_account_id', $request->getHoldingAccount()->getId());
        $this->db->set('status', $request->getStatus());
        $this->db->set('amount', $request->getAmount());
        $this->db->set('to_amount', $request->getToAmount());
        $this->db->set('display_rate', $request->getDisplayRate());
        $this->db->set('to_country_currency_code', $request->getToCountryCurrencyCode());
        $this->db->set('payment_code', $request->getPaymentCode());
        $this->db->set('payment_request_id', $request->getPaymentRequestId());
        $this->db->set('reference_no', $request->getReferenceNo());
        $this->db->set('expired_at', $request->getExpiredAt()->getUnix());
        $this->db->set('created_at', IappsDateTime::now()->getUnix());
        $this->db->set('created_by', $request->getCreatedBy());

        if( $this->db->insert($this->table_name) )
            return true;

        return false;
    }
}