<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Monitor extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->v = parent::$current_vision;
        $this->load->model('public/monitor_model');
    }
    /**
     * 列表页
     */
    public function index()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['subscribe'] = [];
        $data['subscribe_type'] = '产业信息';
        if($user){
            $data['subscribe'] = $this->user_model->get_user_subscribe($user['id'],'产业信息');
        }
        static $limit = 10;
        $data['feature_filter'] = explode(',', trim($this->input->post('feature')));
        $data['institute_filter'] = explode(',', trim($this->input->post('institute')));
        
        $data['filter_list'] = $this->monitor_model->getFilterList();

        $param=[
            'page' => $this->input->post('page')?(int)$this->input->post('page'):1,
            'limit' => $limit
        ];

        if($data['feature_filter']){
            $param['articlefeature|in'] = str_to_realstr(implode(',', $data['feature_filter']));
        }
        if($data['institute_filter']){
            $param['institute|in'] = str_to_realstr(implode(',', $data['institute_filter']));
        }
        if($this->input->post('search')){
            $param['title/content|or_like'] = $this->input->post('search');
        }
        $data['search_filter'] = $this->input->post('search');
        $monitor_list = $this->monitor_model->queryList($param);
        foreach($monitor_list['data']['items'] as &$item){
            $filter_institute = get_filter_institute();
            array_push($filter_institute,$item['institute']);
            $item["mdate"] = str_replace(["T","Z"]," ",$item["mdate"]);
            $item["title"] = str_replace($filter_institute,'',$item["title"]);
        }

        $data['monitor_list'] = $monitor_list;
        $data['v']=$this->v;

        $this->load->library('pagination');
        $total_rows = (int)$monitor_list['data']['totalCount'];
        $page_config = get_page_config($total_rows,$limit,$param['page']);

        $this->pagination->initialize($page_config);
        $data['pagestring']=$this->pagination->create_links();

        $this->load->view('web/monitor.html', $data);
    }

    /**
     * [detail 详情页]
     * @return [type] [description]
     */
    public function detail()
    {
        $key = $this->input->get("key");
        if(!preg_match("/^[0-9]*$/", $key)){
            exit("key值有误");
        }
        $user = $this->user;
        $data['user'] = $user;
        if($user){
            $data['uid'] = $user['id'];
            $conn_main = $this->user_model->get_main_connect_user($user['id']);
            if($conn_main){
                $data['conn_main'] = $conn_main['id'];
            }else{
                $data['conn_main'] = '';
            }
        }else{
            $data['uid'] = '';
            $data['conn_main'] = '';
        }

        $data['v']=$this->v;
        $monitors = $this->monitor_model->getConditionData("*","`key`=".$key);
        if(isset($monitors[0])){
            $monitor = $monitors[0];
            $monitor["add_time"] = date("Y-m-d",$monitor["add_time"]);
            $monitor["edit_time"] = date("Y-m-d",$monitor["edit_time"]);
            $monitor["mdate"] = str_replace(["T","Z"]," ",$monitor["mdate"]);
            $filter_institute = get_filter_institute();
            array_push($filter_institute,$monitor['institute']);
            $monitor["title"] = str_replace($filter_institute,'',$monitor["title"]);
            $data["monitor"] = $monitor;
            $this->load->view('web/monitor_detail.html', $data);
        }else{
            exit("信息不存在");
        }
    }

}