<?php
/************************************************************
** @Description: 角色权限model
** @Author: haodaquan
** @Date: 2017-05-17 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-05-17 12:05:24
*************************************************************/

class Role_auth_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_role_auth';
	}

	/**
	 * [get_auth_by_role_id 获取角色权限]
	 * @param  integer $role_id [角色Id]
	 * @return [type]           [description]
	 */
	public function get_auth_by_role_id($role_id=0)
	{
		$data = [];
		if($role_id == -1){
			// 超级管理员
			$sql = 'SELECT
					a.pid,
					a.id,
					a.menu_url,
					a.icon,
					a.sort,
					a.is_show,
					a.`name` as auth_name
				FROM
					uc_auth AS a
				WHERE
				    a.`status` = 0
				
				ORDER BY a.sort ASC,a.id ASC';
			$query = $this->db->query($sql);
			$data = $query->result_array();
		}else{
			if ($role_id==0) return false;
			$sql = 'SELECT
					ra.auth_id,
					ra.role_id,
					a.pid,
					a.id,
					a.menu_url,
					a.icon,
					a.sort,
					a.is_show,
					a.`name` as auth_name,
					r.`name` as role_name
				FROM
					uc_role_auth AS ra
				JOIN uc_auth AS a ON ra.auth_id = a.id
				JOIN uc_role AS r ON ra.role_id = r.id
				WHERE
					ra.role_id = '.$role_id.'
				AND a.`status` = 0
				AND r.`status` = 0
				ORDER BY a.sort ASC,a.id ASC';
			$query = $this->db->query($sql);
			$data = $query->result_array();
		}
	
		#二级格式化：
		$auth = [];
		foreach ($data as $key => $value) {
			if($value['pid']==0) continue;
			if($value['pid']==1){
				$auth[$value['id']] = $value;
			}
		}
		foreach ($data as $k => $v) {
			if(in_array($v['pid'], [0,1])) continue;
			if (isset($auth[$v['pid']])) {
				$auth[$v['pid']]['child'][] = $v;
			}else{
				$auth[$v['id']] = $v;
			}
		}
		return $auth;
	}
}