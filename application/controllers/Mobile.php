<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mobile extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->v = parent::$current_vision;
    }
    /**
     * 产业信息(监测)
     */
    public function monitor()
    {
        $user = $this->user;
        $data['user'] = $user;
        $ids = urldecode($this->input->get("ids"));
        if(!$ids){
            return;
        }
        $ids_arr = explode(",",$ids);
        foreach($ids_arr as $item){
            if(!preg_match("/^[0-9]*$/", $item)){
                exit("id错误");
            }
        }
        $this->load->model('public/monitor_model');
        $monitor_list = $this->monitor_model->m_query_list($ids);
        foreach($monitor_list as &$item){
            $item["mdate"] = str_replace(["T","Z"]," ",$item["mdate"]);
        }
        $data['list'] = $monitor_list;
        $data['v']=$this->v;
        $this->load->view('web/mobile/monitor.html', $data);
    }

    /**
     * H5 监测详情页
     */
    public function monitor_detail()
    {
        $id = $this->input->get("id");
        if(!preg_match("/^[0-9]*$/", $id)){
            exit("id值有误");
        }
        $this->load->model('public/monitor_model');
        $data['v']=$this->v;
        $monitors = $this->monitor_model->getConditionData("*","`id`=".$id);
        if(isset($monitors[0])){
            $monitor = $monitors[0];
            $monitor["add_time"] = date("Y-m-d",$monitor["add_time"]);
            $monitor["edit_time"] = date("Y-m-d",$monitor["edit_time"]);
            $monitor["mdate"] = str_replace(["T","Z"]," ",$monitor["mdate"]);
            $data["data"] = $monitor;
            $this->load->view('web/mobile/monitor_detail.html', $data);
        }else{
            exit("信息不存在");
        }
    }

    /**
     * 专利技术
     */
    public function tech()
    {
        $user = $this->user;
        $data['user'] = $user;
        $ids = urldecode($this->input->get("ids"));
        if(!$ids){
            return;
        }
        $ids_arr = explode(",",$ids);
        foreach($ids_arr as $item){
            if(!preg_match("/^[0-9]*$/", $item)){
                exit("id错误");
            }
        }
        $this->load->model('requirement_model');
        $list = $this->requirement_model->m_query_list($ids);
        foreach($list as &$item){
            $item["public_time"] = date("Y-m-d",$item["public_time"]);
        }
        $data['list'] = $list;
        $data['v']=$this->v;
        $this->load->view('web/mobile/tech.html', $data);
    }

    /**
     * H5 专利技术详情页
     */
    public function tech_detail()
    {
        $id = $this->input->get("id");
        if(!preg_match("/^[0-9]*$/", $id)){
            exit("id值有误");
        }
        $this->load->model('admin/requirement_model');
        $data['v']=$this->v;
        $ret = $this->requirement_model->getConditionData("*","`id`=".$id);
        if(isset($ret[0])){
            $tech = $ret[0];
            $tech["apply_time"] = date("Y-m-d",$tech["apply_time"]);
            $tech["public_time"] = date("Y-m-d",$tech["public_time"]);
            $data["data"] = $tech;
            $this->load->view('web/mobile/tech_detail.html', $data);
        }else{
            exit("信息不存在");
        }
    }

    /**
     * 专家
     */
    public function expert()
    {
        $user = $this->user;
        $data['user'] = $user;
        $ids = urldecode($this->input->get("ids"));
        if(!$ids){
            return;
        }
        $ids_arr = explode(",",$ids);
        foreach($ids_arr as $item){
            if(!preg_match("/^[0-9]*$/", $item)){
                exit("id错误");
            }
        }
        $this->load->model('expert_model');
        $list = $this->expert_model->m_query_list($ids);
        foreach($list as &$item){
            $item["add_time"] = date("Y-m-d",$item["add_time"]);
        }
        $data['list'] = $list;
        $data['v']=$this->v;
        $this->load->view('web/mobile/expert.html', $data);
    }

    /**
     * H5 专利技术详情页
     */
    public function expert_detail()
    {
        $id = $this->input->get("id");
        if(!preg_match("/^[0-9]*$/", $id)){
            exit("id值有误");
        }
        $this->load->model('admin/expert_model');
        $data['v']=$this->v;
        $ret = $this->expert_model->getConditionData("*","`id`=".$id);
        if(isset($ret[0])){
            $res = $ret[0];
            $res["add_time"] = date("Y-m-d",$res["add_time"]);
            $data["data"] = $res;
            $this->load->view('web/mobile/expert_detail.html', $data);
        }else{
            exit("信息不存在");
        }
    }

    /**
     * 项目合作
     */
    public function project()
    {
        $user = $this->user;
        $data['user'] = $user;
        $ids = urldecode($this->input->get("ids"));
        if(!$ids){
            return;
        }
        $ids_arr = explode(",",$ids);
        foreach($ids_arr as $item){
            if(!preg_match("/^[0-9]*$/", $item)){
                exit("id错误");
            }
        }
        $this->load->model('project_model');
        $list = $this->project_model->m_query_list($ids);
        foreach($list as &$item){
            $item["add_time"] = date("Y-m-d",$item["add_time"]);
        }
        $data['list'] = $list;
        $data['v']=$this->v;
        $this->load->view('web/mobile/project.html', $data);
    }

    /**
     * H5 项目合作详情页
     */
    public function project_detail()
    {
        $id = $this->input->get("id");
        if(!preg_match("/^[0-9]*$/", $id)){
            exit("id值有误");
        }
        $this->load->model('admin/project_model');
        $data['v']=$this->v;
        $ret = $this->project_model->getConditionData("*","`id`=".$id);
        if(isset($ret[0])){
            $res = $ret[0];
            $res["add_time"] = date("Y-m-d",$res["add_time"]);
            $data["data"] = $res;
            $this->load->view('web/mobile/project_detail.html', $data);
        }else{
            exit("信息不存在");
        }
    }

    /**
     * 分析报告
     */
    public function report()
    {
        $user = $this->user;
        $data['user'] = $user;
        $ids = urldecode($this->input->get("ids"));
        if(!$ids){
            return;
        }
        $ids_arr = explode(",",$ids);
        foreach($ids_arr as $item){
            if(!preg_match("/^[0-9]*$/", $item)){
                exit("id错误");
            }
        }
        $this->load->model('admin/report_model');
        $list = $this->report_model->m_query_list($ids);
        foreach($list as &$item){
            $item["add_time"] = date("Y-m-d",$item["add_time"]);
        }
        $data['list'] = $list;
        $data['v']=$this->v;
        $this->load->view('web/mobile/report.html', $data);
    }

    /**
     * H5 分析报告详情页
     */
    public function report_detail()
    {
        $id = $this->input->get("id");
        if(!preg_match("/^[0-9]*$/", $id)){
            exit("id值有误");
        }
        $this->load->model('admin/report_model');
        $data['v']=$this->v;
        $ret = $this->report_model->getConditionData("*","`id`=".$id);
        if(isset($ret[0])){
            $res = $ret[0];
            $res["add_time"] = date("Y-m-d",$res["add_time"]);
            $data["data"] = $res;
            $this->load->view('web/mobile/report_detail.html', $data);
        }else{
            exit("信息不存在");
        }
    }

    public function subscribe(){
        $this->load->view('web/mobile/subscribe.html', []);
    }

}
