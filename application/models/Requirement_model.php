<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Requirement_model extends MY_Model
{
	/**
	 * 插入专家表信息
	 */
	function add_require($data) {
		$this->db->insert('tech_requirement', $data);
		$uid= $this->db->insert_id();
		if($uid){
			return $uid;
		}
		return null;
	}

	function add_achievement($data){
		$this->db->insert('uc_achievement', $data);
		$uid= $this->db->insert_id();
		if($uid){
			return $uid;
		}
		return null;
	}

    function m_query_list($ids){
        $sql = "select id,title_en,public_time,public_no from tech_requirement where id in ($ids)";
        $query = $this->db->query($sql);
        $list = $query->result_array();
        return $list;
    }

}