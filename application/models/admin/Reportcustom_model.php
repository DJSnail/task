<?php
/************************************************************
** @Description: 定制报告
** @Author: haodaquan
** @Date: 2017-12-04 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:05:24 
*************************************************************/

class Reportcustom_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_report';
	}

	/**
	 * [getReportInfo 获取报告]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getReportInfo($id)
	{
		$sql = 'SELECT
				p.*,
				m.nickname,
				mc.username as main_nickname
			FROM
				uc_report AS p
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