<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->v = parent::$current_vision;
    }

    public function index(){
        echo 'welcome to PHP TASK';
    }

    public function test_change(){
        $url = 'localhost:8089/change';
        $post['task_id'] = 4;
        $post['status'] = 1;
        $ret = curl_request($url,$post);
        var_dump($ret);
    }
    /**
     * 分析报告
     */
    public function report()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $this->load->view('web/home/report.html', $data);
    }
    /**
     * 文献服务
     */
    public function service()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $this->load->view('web/home/service.html', $data);
    }
    /**
     * 成果对接
     */
    public function achievement()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $data['data'] = $this->user_model->get_achievement_num();
        $this->load->view('web/home/achievement.html', $data);
    }
    /**
     * 产业监控
     */
    public function monitor()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $data['data'] = $this->user_model->get_monitor_num();
        $this->load->view('web/home/monitor.html', $data);
    }
    /**
     * 资源分布
     */
    public function resource()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $this->load->view('web/home/resource.html', $data);
    }
    /**
     * 资源分布详情
     */
    public function resource_detail()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $this->load->view('web/resource_detail.html', $data);
    }

    public function form4()
    {

        $this->load->view('web/form4.html', ['v' => 6]);
    }


    public function chemicals()
    {

        $this->load->view('web/chemicals.html', ['v' => 6]);
    }

    public function search()
    {

        $this->load->view('web/search.html', ['v' => 2]);
    }

}
