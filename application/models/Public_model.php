<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Public_model extends MY_Model
{
	/**
	 * 获取所有分类列表
	 */
	function get_category() {
		$sql = "select * from pub_category";
		$query = $this->db->query($sql);
		$list = $query->result_array();
		return $list;
	}
	/**
	 * 获取所有机构列表
	 */
	function get_institution(){
		$sql = "select * from pub_institution";
		$query = $this->db->query($sql);
		$list = $query->result_array();
		return $list;
	}

}