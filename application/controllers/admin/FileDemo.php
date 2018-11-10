<?php
/************************************************************
** @Description: 文件相关操作Demo
** @Author: haodaquan
** @Date:   2017-11-29
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-11-29 12:04:14
*************************************************************/

class FileDemo extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model');
    }

	/**
     * [index 文件相关操作]
     * @Date   2016-11-29
     * @return [type]     [description]
     */
    public function index()
    {
    	$data['pageTitle'] = '文件相关操作Demo';
    	$data['pageTips']  = 'demo tip';
        $this->display('admin/demo_upload.html',$data);
    }

    /**
     * [download 下载文件]
     * @return [type] [description]
     */
    public function download()
    {
        $data['pageTitle'] = '文件相关Demo';
        $data['pageTips']  = 'demo tip';
        $this->display('admin/demo_download.html',$data);
    }

    /**
     * [form_demo form表单]
     * @return [type] [description]
     */
    public function form_demo()
    {
        $data['pageTitle'] = '表单demo';
        $data['pageTips']  = 'demo tip';
        $data['category'] = ['分类1','分类2'];
        $this->display('admin/demo_form.html',$data);
    }

    public function message_demo()
    {
        $data['pageTitle'] = '提示信息demo';
        $data['pageTips']  = 'demo tip';
        $this->display('admin/demo_message.html',$data);
    }
}