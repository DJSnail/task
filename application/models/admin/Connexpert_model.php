<?php

/************************************************************
** @Description: file
** @Author: haodaquan
** @Date:   2018-02-09 13:32:58
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-02-09 16:06:36
*************************************************************/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Connexpert_model extends MY_Model
{

    public $_table = 'uc_expert';

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * [getReportInfo 获取报告]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getExportInfo($id,$report_no="")
	{
		$sql = 'SELECT
				p.*,
				m.nickname as uname,
				m2.nickname as conn_name,
				m3.nickname as verify_name,
				e.name as foreign_name,
				e.expert_no as expert_nos,
				e.company,
				e.type,
				e.phone,
				e.domain,
				e.email,
				e.location,
				e.job,
				e.title,
				e.resume,
				e.project,
				e.works
			FROM
				uc_expert AS p
			LEFT JOIN uc_member AS m ON p.uid=m.id
			LEFT JOIN uc_member AS m2 ON p.conn_uid=m2.id
			LEFT JOIN uc_member AS m3 ON p.verify_uid=m3.id
			LEFT JOIN expert_consult AS e ON p.foreign_id=e.id
			WHERE ';
        if($id){
            $sql .= 'p.id ='.$id;
        }else if($report_no){
            $sql .= "p.report_no = '$report_no'";
        }

		$query = $this->db->query($sql);
		return $query->result_array();
	}

	/**
	 * [getCount 获取总数]
	 * @param  [type] $where [description]
	 * @return [type]        [description]
	 */
	public function getCountNum($where)
	{
		$sql = 'SELECT
					count(p.id) as count
				FROM
					uc_expert as p'
				. $where;
		$_total = $this->db->query($sql)->result_array();
		return isset($_total[0]['count']) ? $_total[0]['count'] : 0;
	}

	/**
	 * [queryList 列表查询]
	 * @param  [type] $param [description]
	 * @return [type]        [description]
	 */
	public function queryList($param)
	{
		$map = $this->queryParam($param);
		$totalCount = $this->getCountNum($map['where']);
		$sql = 'SELECT
				p.*,
				m.nickname as uname,
				m2.nickname as conn_name
			FROM
				uc_expert AS p
			LEFT JOIN uc_member AS m ON p.uid=m.id
			LEFT JOIN uc_member AS m2 ON p.conn_uid=m2.id
			'. $map['where']. 
				$map['orderby'].' LIMIT '.($map['current_page']-1)*$map['page_size'].','.$map['page_size'];

		// echo $sql;
		// exit();
		$query = $this->db->query($sql);
		$items = $query->result_array();
		return $this->returnData(200,'success',$items,$totalCount);
	}



}