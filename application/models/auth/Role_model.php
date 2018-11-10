<?php
/************************************************************
** @Description: 角色model
** @Author: haodaquan
** @Date: 2017-05-16 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-05-16 12:05:24
*************************************************************/

class Role_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_role';
	}
}