<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auto_post  extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }

    function add_expert_api(){
        $this->load->model('expert_model');
        $file_dir = '/Users/liyuhang/Documents/专家2.json';
        $fp = fopen($file_dir,"r");
        $add_json = "";
        $buffer = 1024;//每次读取 1024 字节
        while(!feof($fp)){//循环读取，直至读取完整个文件
            $add_json .= fread($fp,$buffer);
        }
        $add_array = json_decode(trim($add_json,chr(239).chr(187).chr(191)),true);

    }

    /**
     * 任务列表
     */
    function index(){
        $user = $this->user;
        $data = [];
        $this->load->view('web/auto_post/task_list.html', $data);
    }

    function add(){
        $data = [];
        $this->load->view('web/auto_post/task_add.html', $data);
    }



    
}
