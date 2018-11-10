<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Expert_model extends MY_Model
{
	/**
	 * 插入专家表信息
	 */
	function add_expert($data) {
		$this->db->insert('expert_consult', $data);
		$uid= $this->db->insert_id();
		if($uid){
			return $uid;
		}
		return null;
	}

	function m_query_list($ids){
        $sql = "select id,name,company,type,add_time from expert_consult where id in ($ids)";
        $query = $this->db->query($sql);
        $list = $query->result_array();
        return $list;
	}

}