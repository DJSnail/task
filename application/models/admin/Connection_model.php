<?php
/************************************************************
** @Description: 联系人model
** @Author: haodaquan
** @Date: 2017-12-04 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:05:24 
*************************************************************/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Connection_model extends MY_Model
{

    public $_table = 'uc_connection';

	public function __construct()
	{
		parent::__construct();
	}

}
