<?php

use Iapps\HoldingAccountService\HoldingAccountTransaction\IHoldingAccountTransactionDataMapper;
use Iapps\Common\Transaction\Transaction;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransaction;
use Iapps\Common\Core\IappsDateTime;
use Iapps\Common\Transaction\TransactionStatus;
use Iapps\HoldingAccountService\HoldingAccountTransaction\HoldingAccountTransactionCollection;

class Holding_account_transaction_model extends Base_Model implements IHoldingAccountTransactionDataMapper{

    public function map(\stdClass $data)
    {
        $entity = new HoldingAccountTransaction();

        if( isset($data->id) )
            $entity->setId($data->id);
        if( isset($data->transactionID) )
            $entity->setTransactionID($data->transactionID);
        if( isset($data->transaction_type_id) )
            $entity->getTransactionType()->setId($data->transaction_type_id);
        if( isset($data->transaction_type_code))
            $entity->getTransactionType()->setCode($data->transaction_type_code);
        if( isset($data->transaction_type_name))
            $entity->getTransactionType()->setDisplayName($data->transaction_type_name);
        if( isset($data->transaction_type_desc))
            $entity->getTransactionType()->setDescription($data->transaction_type_desc);
        if( isset($data->user_profile_id) )
            $entity->setUserProfileId($data->user_profile_id);
        if( isset($data->status_id) )
            $entity->getStatus()->setId($data->status_id);
        if( isset($data->status_code))
            $entity->getStatus()->setCode($data->status_code);

        if( isset($data->description))
            $entity->setDescription($data->description);

        if( isset($data->country_currency_code) )
            $entity->setCountryCurrencyCode($data->country_currency_code);
        if( isset($data->remark) )
            $entity->setRemark($data->remark);

        if( isset($data->ref_transaction_id) )
            $entity->setRefTransactionId($data->ref_transaction_id);
        if( isset($data->confirm_payment_mode_code) )
            $entity->setConfirmPaymentCode($data->confirm_payment_mode_code);

        if( isset($data->passcode) )
            $entity->getPasscode()->setEncodedCode($data->passcode);

        if( isset($data->channel_id) )
            $entity->getChannel()->setId($data->channel_id);

        if( isset($data->channel_code) )
            $entity->getChannel()->setCode($data->channel_code);

        if( isset($data->expired_date) )
            $entity->setExpiredDate(IappsDateTime::fromUnix ($data->expired_date));

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
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');
        if(!$deleted)
        {
            $this->db->where('transaction.deleted_at', NULL);
            $this->db->where('transaction_code.deleted_at', NULL);
            $this->db->where('status_code.deleted_at', NULL);
            $this->db->where('channel_code.deleted_at', NULL);
        }
        $this->db->where('transaction.id', $id);

        $query = $this->db->get();

        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findActiveExpiredTransaction()
    {
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);
        $this->db->where('status_code.code <=', TransactionStatus::CONFIRMED);
        $this->db->where('transaction.expired_at <=', IappsDateTime::now()->getUnix());

        $query = $this->db->get();

        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function findAll()
    {
        return false;
    }

    public function findByUserProfileId($user_profile_id)
    {
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);
        $this->db->where('transaction.user_profile_id', $user_profile_id);

        $query = $this->db->get();

        if( $query->num_rows() > 0 )
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionCollection(), $query->num_rows());
        }

        return false;
    }

    public function findByTransactionID($transactionID)
    {
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);
        $this->db->where('transaction.transactionID', $transactionID);

        $query = $this->db->get();
        if( $query->num_rows() > 0 )
        {
            return $this->map($query->row());
        }

        return false;
    }

    public function insert(Transaction $transaction)
    {
        $transaction->setCreatedAt(IappsDateTime::now());

        $this->db->set('id', $transaction->getId());
        $this->db->set('transactionID', $transaction->getTransactionID());
        $this->db->set('transaction_type_id', $transaction->getTransactionType()->getId());
        $this->db->set('user_profile_id', $transaction->getUserProfileId());
        $this->db->set('status_id', $transaction->getStatus()->getId());
        $this->db->set('description', $transaction->getDescription());
        $this->db->set('country_currency_code', $transaction->getCountryCurrencyCode());
        $this->db->set('remark', $transaction->getRemark());
        $this->db->set('ref_transaction_id', $transaction->getRefTransactionId());
        $this->db->set('confirm_payment_code', $transaction->getConfirmPaymentCode());
        $this->db->set('passcode', $transaction->getPasscode()->getEncodedCode());
        $this->db->set('channel_id', $transaction->getChannel()->getId());
        $this->db->set('expired_date', $transaction->getExpiredDate()->getUnix());
        $this->db->set('created_at', $transaction->getCreatedAt()->getUnix());
        $this->db->set('created_by', $transaction->getCreatedBy());

        if( $this->db->insert('iafb_holding_account.transaction') )
        {
            return true;
        }
        return false;
    }

    public function updateStatus(Transaction $transaction)
    {
        $transaction->setUpdatedAt(IappsDateTime::now());
        $this->db->set('status_id', $transaction->getStatus()->getId());
        $this->db->set('updated_at', $transaction->getUpdatedAt()->getUnix());
        $this->db->set('updated_by', $transaction->getUpdatedBy());
        $this->db->where('id', $transaction->getId());

        if( $this->db->update('iafb_holding_account.transaction') )
        {
            return true;
        }

        return false;
    }


    //@-------------------

    public function findByParam(Transaction $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query

        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);

        if($config->getId()) {
            $this->db->where('transaction.id', $config->getId());
        }

        if($config->getUserProfileId()) {
            $this->db->like('transaction.user_profile_id', $config->getUserProfileId());
        }
        if($config->getTransactionID()) {
            $this->db->where('transaction.transactionID', $config->getTransactionID());
        }

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache();
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionCollection(), $total);
        }

        return false;
    }


    public function findByDate(Transaction $config, $limit, $page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query

        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');

        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);


        if($config->getDateFrom()){
            $this->db->where('transaction.created_at >=', $config->getDateFrom()->getUnix());
        }

        if($config->getDateTo()){
            $this->db->where('transaction.created_at <=', $config->getDateTo()->getUnix());
        }

        if($config->getTransactionID()) {
            $this->db->where('tr.transactionID', $config->getTransactionID());
        }

        $this->db->where('transaction.user_profile_id', $config->getUserProfileId() );

        $this->db->order_by("transaction.created_at", "desc");

        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        $this->db->flush_cache(); //print_r($query);
        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionCollection(), $total);
        }

        return false;
    }


    public function findByTransactionIDArrByDate(Transaction $config, $transactionID_arr , $limit ,$page)
    {
        $init_predicate = true;
        $offset = ($page - 1) * $limit;
        $this->db->start_cache(); //to cache active record query

        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');

        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');

        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where('transaction_code.deleted_at', NULL);
        $this->db->where('status_code.deleted_at', NULL);
        $this->db->where('channel_code.deleted_at', NULL);



        $this->db->where_in('transaction.transactionID', $transactionID_arr);
        if($config->getDateFrom()){
            $this->db->where('transaction.created_at >=', $config->getDateFrom()->getUnix());
        }
        if($config->getDateTo()){
            $this->db->where('transaction.created_at <=', $config->getDateTo()->getUnix());
        }
        $this->db->order_by("transaction.created_at", "desc");
        $this->db->stop_cache();
        $total = $this->db->count_all_results(); //to get total num of result w/o limit
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        $this->db->flush_cache();


        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionCollection(), $total);

        }

        return false;
    }



    public function findByTransactionIDArr($transactionID_arr)
    {
        $this->db->select('`transaction`.`id`,
                            `transaction`.`transactionID`,
                            `transaction`.`transaction_type_id`,
                            transaction_code.code as transaction_type_code,
                            transaction_code.display_name as transaction_type_name,
                            transaction_code.description as transaction_type_desc,
                            `transaction`.`user_profile_id`,
                            `transaction`.`status_id`,
                            status_code.code as status_code,
                            `transaction`.`description`,
                            `transaction`.`country_currency_code`,
                            `transaction`.`remark`,
                            `transaction`.`ref_transaction_id`,
                            `transaction`.`confirm_payment_code`,
                            `transaction`.`passcode`,
                            `transaction`.`channel_id`,
                            channel_code.code as channel_code,
                            `transaction`.`expired_date`,
                            `transaction`.`created_at`,
                            `transaction`.`created_by`,
                            `transaction`.`updated_at`,
                            `transaction`.`updated_by`,
                            `transaction`.`deleted_at`,
                            `transaction`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction`');
        $this->db->join('`iafb_holding_account`.`system_code` as transaction_code','transaction_code.id = transaction.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as status_code','status_code.id = transaction.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as channel_code','channel_code.id = transaction.channel_id','LEFT');
        $this->db->where('transaction.deleted_at', NULL);
        $this->db->where_in('transaction.transactionID', $transactionID_arr);
        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionCollection(), $query->num_rows());
        }

        return false;
    }


    public function findByIdArr(array $id_arr)
    {
        $this->db->select('`tr`.`id`,
                            `tr`.`transactionID`,
                            `tr`.`transaction_type_id`,
                            tsc.code as transaction_type_code,
                            tsc.display_name as transaction_type_name,
                            tsc.description as transaction_type_desc,
                            `tr`.`user_profile_id`,
                            `tr`.`status_id`,
                            ssc.code as status_code,
                            `tr`.`country_currency_code`,
                            `tr`.`remark`,
                            `tr`.`ref_transaction_id`,
                            `tr`.`confirm_payment_code`,
                            `tr`.`passcode`,
                            `tr`.`channel_id`,
                            csc.code as channel_code,
                            `tr`.`expired_date`,
                            `tr`.`created_at`,
                            `tr`.`created_by`,
                            `tr`.`updated_at`,
                            `tr`.`updated_by`,
                            `tr`.`deleted_at`,
                            `tr`.`deleted_by`
                          ');
        $this->db->from('`iafb_holding_account`.`transaction` as tr');
        $this->db->join('`iafb_holding_account`.`system_code` as tsc','tsc.id = tr.transaction_type_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as ssc','ssc.id = tr.status_id','LEFT');
        $this->db->join('`iafb_holding_account`.`system_code` as csc','csc.id = tr.channel_id','LEFT');
        $this->db->where('tr.deleted_at', NULL);
        $this->db->where_in('tr.id', $id_arr);
        $query = $this->db->get();

        if( $query->num_rows() > 0)
        {
            return $this->mapCollection($query->result(), new HoldingAccountTransactionCollection(), $query->num_rows());
        }

        return false;
    }


}