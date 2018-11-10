<?php
/************************************************************
** @Description: 权限model
** @Author: haodaquan
** @Date: 2017-05-16 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-05-16 12:05:24
*************************************************************/

class Auth_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_auth';
	}

	public function get_auth_by_factor($module,$controller,$action)
	{
		$where  = " controller='".$controller."'";
		$where .= " and action='".$action."'";
		if ($module) {
			$where .= " and module='".$module."'";
		}
		$auth = $this->getConditionData("*",$where);

		if (isset($auth[0]) && $auth[0]) {
			return $auth[0];
		}else
		{
			return false;
		}
	}

}