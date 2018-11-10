<?php
/************************************************************
** @Description: 文献信息
** @Author: haodaquan
** @Date: 2017-12-04 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:05:24 
*************************************************************/

class Subject_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_subject';
	}

	/**
	 * [getReportInfo 获取文献]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getSubjectInfo($id)
	{
	
		$sql = 'SELECT
				p.*,
				m.nickname,
				mc.username as main_nickname
			FROM
				uc_subject AS p
			JOIN uc_member AS m ON p.uid=m.id
			JOIN uc_connection AS mc ON p.main_conn=mc.id
			WHERE
				p.id ='.$id;

		$query = $this->db->query($sql);
		
		return $query->result_array();
	}

	// public function get

	// $main = $this->amdin_model->getConditionData("nickname",'id='.(int)$report[0]['main_conn']);
 //    	$other = $this->amdin_model->getConditionData("nickname",'id IN('.(int)$other_conn_id.')');
}