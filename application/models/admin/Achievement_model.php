<?php

/************************************************************
** @Description: file
** @Author: haodaquan
** @Date:   2018-02-09 10:53:09
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-02-09 10:54:14
*************************************************************/
class Achievement_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'uc_achievement';
	}
}