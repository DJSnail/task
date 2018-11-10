<?php
/************************************************************
** @Description: file
** @Author: haodaquan
** @Date:   2018-01-25 15:26:42
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-25 15:27:28
*************************************************************/
class Technology_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_technology';
	}
}