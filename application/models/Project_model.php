<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Project_model extends MY_Model
{
	/**
	 * 插入项目合作表信息
	 */
	function add_project($data) {
		$this->db->insert('project_cooperation', $data);
		$pid = $this->db->insert_id();
		if($pid){
			return $pid;
		}
		return null;
	}

	function m_query_list($ids){
		$sql = "select id,name,type,add_time from project_cooperation where id in ($ids)";
		$query = $this->db->query($sql);
		$list = $query->result_array();
		return $list;
	}

}