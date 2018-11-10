<?php
/************************************************************
** @Description: 后台首页
** @Author: haodaquan
** @Date:   2016-05-27 13:52:48
** @Last Modified by:   haodaquan
** @Last Modified time: 2016-11-15 13:53:58
*************************************************************/

class Home extends MY_Controller 
{
	public $admin_info;

	function __construct()
	{
		parent::__construct();
		$this->load->model('public/admin_model');
		$this->admin_info = $this->admin_model->check_admin();

	}
	
	/**
	 * [index 登录首页]
	 * @Date   2016-05-27
	 * @return [type]     [登录页面]
	 */
	public function index()
	{
		$data = $this->admin_info();
		$data['version'] = time();
		$this->load->view('public/main.html',$data);
	}

	/**
	 * [start 起始页]
	 * @Date   2016-09-07
	 * @return [type]     [description]
	 */
	public function start()
	{
		$data = $this->admin_info();
		$this->load->view('public/start.html',$data);
	}


	/**
	 * [user_info 合并用户信息]
	 * @return [type] [description]
	 */
	public function admin_info()
	{
		$data	= $this->admin_info;
		if(isset($data['nickname']))
		{
			if(isset($data['role_id']) && $data['role_id']==0)
			{
				$auth = [];
			}else
			{
				#权限
				$this->load->model('auth/role_auth_model');
				$auth = $this->role_auth_model->get_auth_by_role_id($data['role_id']);
			}
			
			$data['auth'] = $auth;
		}else
		{
			$this->load->view('public/login.html',[]);
		}

		return $data;
	}

	
}