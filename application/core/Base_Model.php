<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Base_Model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
		date_default_timezone_set('UTC');

        $this->load->database('iafb_holding_account');
	}

    public function TransStart()
    {
        $this->db->trans_start();
    }

    public function TransRollback()
    {
        $this->db->trans_rollback();
    }

    public function TransComplete()
    {
        $this->db->trans_complete();
    }

    public function mapCollection(array $data, $collection, $total)
    {
        foreach($data AS $info)
        {
            $entity = $this->map($info);
            $collection->addData($entity);
        }

        if( $collection->count() > 0 )
        {
            $object = new StdClass;
            $object->result = $collection;
            $object->total = $total;
            return $object;
        }

        return false;
    }
}
