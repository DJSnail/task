<?php
/************************************************************
** @Description: 用户model
** @Author: haodaquan
** @Date:   2016-06-03 12:21:01
** @Last Modified by:   xiyou_zlg
** @Last Modified time: 2017-08-01 13:06:56
*************************************************************/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Organization_model extends MY_Model
{

    public $_table = 'pb_organization';

	public function __construct()
	{
		parent::__construct();
	}

}
