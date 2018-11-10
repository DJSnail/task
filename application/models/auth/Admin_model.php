<?php
/************************************************************
** @Description: 用户model
** @Author: haodaquan
** @Date: 2017-05-17 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-05-17 12:05:24
*************************************************************/

class Admin_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_member';
	}
}