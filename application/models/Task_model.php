<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Task_model extends MY_Model
{
	/**
	 * 新建任务信息
	 */
	function add_task($data) {
		$this->db->insert('task_info', $data);
		$task_id= $this->db->insert_id();
		if($task_id){
			return $task_id;
		}
		return null;
	}
	/**
	 * 获取一条任务
	 */
	function get_one_task_info($task_id){
        $sql = "select * from task_info where task_id = $task_id";
        $query = $this->db->query($sql);
        $list = $query->row_array();
        return $list;
	}
    /**
     * 更新一条任务
     */
	function update_task($update, $task_id){
        $this->db->where('task_id', $task_id);
        $ret =$this->db->update('task_info', $update);
        return $ret;
	}
}