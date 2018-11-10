<?php
/************************************************************
** @Description: 用户model
** @Author: haodaquan
** @Date:   2016-06-03 12:21:01
** @Last Modified by:   xiyou_zlg
** @Last Modified time: 2017-08-01 13:06:56
*************************************************************/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_model extends MY_Model
{

    public $_table = 'uc_member';

	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * [get_user_by_username 根据用户名获取用户信息]
	 * @Date   2016-06-03
	 * @param  string     $username [用户名]
	 * @return array                [用户信息数组]
	 */
	public function get_user_by_username($username='')
	{
		$username = addslashes($username);
		if (!$username) return false;
        $info = $this->getConditionData("*",'username="'.$username.'"');
        if(isset($info[0]['username']))
        {
            return $info[0];
        }else
        {
            return false;
        }
	}


    /**
     * [check_user 检查登录]
     * @Date   2016-06-03
     * @return [type]     [description]
     */
    public function check_admin()
    {
        $user = $this->session->userdata("admin_info");
        if(!$user){
            $url = "http://".$_SERVER['HTTP_HOST']."/public/login/index";
            header("Location:".$url);
            exit();
        }
        return $user;
    }
}
