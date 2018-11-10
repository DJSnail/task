<?php
/************************************************************
** @Description: 登录处理
** @Author: haodaquan
** @Date:   2016-05-27 13:52:48
** @Last Modified by:   cuixiaona
** @Last Modified time: 2017-05-09 15:43:52
*************************************************************/

class Login extends CI_Controller 
{
	public function __construct()
	{
		parent::__construct();
        $this->load->model('public/admin_model');
	}
	
	/**
	 * [index 登录首页]
	 * @Date   2016-05-27
	 * @return [type]     [登录页面]
	 */
	public function index()
	{
        $this->load->view('public/login.html',[]);
	}

	/**
	 * [do_login 登录处理]
	 * @Date   2016-06-03
	 * @return [type]     [description]
	 */
	public function do_login()
	{
        $username = $this->input->post("username");
        $password = $this->input->post("password");

        //用户名只能是字母+数字，4位-20位
        if(!preg_match("/^[a-zA-Z0-9][a-zA-Z0-9]{3,19}$/", $username))
        {
            echo -1;
            return;
        }

        //密码要6位-32位
        if(strlen($password)<6 || strlen($password)>32)
        {
            echo -1;
            return;
        }


        // 判断登录条件
        $user = $this->admin_model->get_user_by_username($username);
        if ($user==false || $user['password']!=md5($password)){
            echo -1;
            return;
        }
        //登录后处理session处理
        $this->session->set_userdata(['admin_info'=>$user]);
        echo 1;
    }

    /**
     * [do_login_out 退出登录]
     * @Date   2016-06-03
     * @return [type]     [description]
     */
    public function do_login_out()
    {
        $this->session->unset_userdata('admin_info');
        $url = "http://".$_SERVER['HTTP_HOST'];
        header("Location:".$url);
        exit(); 
    }
}