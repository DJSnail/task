<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Project extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }

    function add_project_api(){
        $this->load->model('project_model');
        $file_dir = '/Users/liyuhang/Documents/项目合作_20171219161409.json';
        $fp = fopen($file_dir,"r");
        $add_json = "";
        $buffer = 1024;//每次读取 1024 字节
        while(!feof($fp)){//循环读取，直至读取完整个文件
            $add_json .= fread($fp,$buffer);
        }
        $add_array = json_decode(trim($add_json,chr(239).chr(187).chr(191)),true);
        //var_dump($add_array);exit;
        foreach($add_array as $item){
            $data = [];
            $data['project_no'] = expertno();
            $data['uid'] = 2;
            $data['status'] = 3;
            $data['add_time'] = time();
            foreach($item as $k => $v){
                if($k == '项目分类'){
                    $data['type'] = $v;
                }
                if($k == '项目委托方'){
                    $data['entrust_org'] = $v;
                }
                if($k == '截止日期'){
                    $data['end_time'] = strtotime($v);
                }
                if($k == '项目预算'){
                    $data['price'] = $v;
                }
                if($k == '原文地址'){
                    $data['website'] = $v;
                }
                if($k == '项目介绍'){
                    $data['descri'] = $v;
                }
                if($k == '委托方地址'){
                    $data['conn_location'] = $v;
                }
                if($k == '开始日期'){
                    $data['start_time'] = strtotime($v);
                }
                if($k == '修改时间'){
                    $data['edit_time'] = strtotime($v);
                }
                if($k == '项目代理机构'){
                    $data['agent_org'] = $v;
                }
                if($k == '项目联系人'){
                    $data['conn_user'] = $v;
                }
                if($k == '电话'){
                    $data['conn_phone'] = $v;
                }
                if($k == '项目名称'){
                    $data['name'] = $v;
                }
            }
            $ret = $this->project_model->add_project($data);
            var_dump($ret);
        }
    }

    /**
     * 领域专家咨询
     */
    function index(){
        $user = $this->user;
        $data['user'] = $user;
        $data['subscribe'] = [];
        $data['subscribe_type'] = '项目合作';
        if($user){
            $data['subscribe'] = $this->user_model->get_user_subscribe($user['id'],'项目合作');
        }
        static $limit = 10;
        $data['type_filter'] = explode(',', trim($this->input->post('type')));
        $data['company_filter'] = explode(',', trim($this->input->post('company')));
        $data['price_filter'] = explode(',', trim($this->input->post('price')));

        $this->load->model('admin/project_model');
        $data['filter_list'] = $this->project_model->getFilterList();

        $param = [
            'page'  => $this->input->post('page')?(int)$this->input->post('page'):1,
            'limit' => $limit,
            'status|=' => 3
        ];
        if($this->input->post('search')){
            $param['name/descri|or_like'] = $this->input->post('search');
        }
        $data['search_filter'] = $this->input->post('search');
        if($data['type_filter']){
            $param['type|like'] = implode(',', $data['type_filter']);
        }
        if($data['company_filter']){
            $param['entrust_org|='] = implode(',', $data['company_filter']);
        }
        if($data['price_filter']){
            $param['price|='] = str_replace('万','',implode(',', $data['price_filter']));
        }
        $project_list = $this->project_model->queryList($param);

        $data['project_list'] = $project_list;
        $data['v']=$this->v;

        $this->load->library('pagination');
        $total_rows = (int)$project_list['data']['totalCount'];

        $page_config = get_page_config($total_rows,$limit,$param['page']);

        $this->pagination->initialize($page_config);
        $data['pagestring']=$this->pagination->create_links();

        $this->load->view('web/project.html', $data);
    }

    public function detail()
    {
        $project_no = $this->input->get("project_no");
        if(!preg_match("/^[0-9]*$/", $project_no)){
            exit("请检查报告编号");
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
        $this->load->model("admin/project_model");
        $project = $this->project_model->getProjectInfo(0,$project_no);
        if(isset($project[0])){
            $project = $project[0];
            $project["start_time"] = date("Y-m-d",$project["start_time"]);
            $project["end_time"] = date("Y-m-d",$project["end_time"]);
            if($project["price"] != '竞价'){
                $project["price"] .= '万';
            }
            $data["project"] = $project;
            $this->load->view('web/project_detail.html', $data);
        }else{
            exit("请检查报告编号");
        }
    }
	
	
	/**
     * 申请项目合作页面
     */
    public function apply(){
        $user = $this->user;
        if(!$user){
            parent::go_login();
            return;
        }
        $data['user'] = $user;
        $data['v'] = $this->v;
        if(isset($user['id'])){
            $conn_user = $this->user_model->get_connect_user($user['id']);
            foreach($conn_user as $k=>$c){
                //主联系人
                if($c['is_main'] == 1){
                    $data['main_conn'] = $c;
                    unset($conn_user[$k]);
                }
            }
            //其他联系人
            if(count($conn_user)>0){
                $data['other_conn'] = $conn_user;
            }
        }
        //项目分类
        $category = get_research_domain();
        $data['category'] = $category;
        //随机码防止机器人注入
        $code = md5(time().rand(1,99999));
        parent::setToCache($code,'bio',3600 * 24);
        $data['random_code'] = $code;
        $this->load->view('web/project_apply.html', $data);
    }
    
    /**
     * 申请项目合作接口
     */
    function add_apply_project(){
        if (!is_ajax()) {
            return;
        }
        $random_code = $this->input->post("random_code");
        if(!$random_code || parent::getFromCache($random_code) != 'bio'){
            echo json_encode(['status'=>-1,'error'=>'页面过期,请刷新']);
            return;
        }
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'请登录']);
            return;
        }
        $data['uid'] = $user['id'];
        $conn_user = $this->user_model->get_connect_user($user['id']);
        foreach($conn_user as $k=>$c){
            //主联系人
            if($c['is_main'] == 1){
                $data['main_conn'] = $c['id'];
            }else{
                //其他联系人
                $data['other_conn'][] = $c['id'];
            }
        }
        $data['other_conn'] = isset($data['other_conn'])?$data['other_conn']:[];
        $data['other_conn'] = implode(',',$data['other_conn']);
        if(!isset($data['main_conn'])){
            echo json_encode(['status'=>-1,'error'=>'请填写主要联系人']);
            return;
        }
        

        $name = $this->input->post("name");
        $type = $this->input->post("type");
        $price = $this->input->post("price");
        $start_time = strtotime($this->input->post("start_time"));
        $end_time = strtotime($this->input->post("end_time"));
        if($end_time <= $start_time){
            echo json_encode(['status'=>-1,'error'=>'项目开始时间和截至时间有误']);
            return;
        }
        $entrust_org = $this->input->post("entrust_org");
        $conn_user = $this->input->post("conn_user");
        $conn_phone = $this->input->post("conn_phone");
        if(!preg_match("/^0?1[3|4|5|8][0-9]\d{8}$/", $conn_phone)){
            echo json_encode(['status'=>-1,'error'=>'请填写正确手机号']);
            return;
        }
        $conn_location = $this->input->post("conn_location");

        $descri = $this->input->post("descri");
        if(!$name || !$type || !$price || !$start_time || !$end_time || !$entrust_org || !$conn_user|| !$conn_phone|| !$conn_location||!$descri){
            echo json_encode(['status'=>-1,'error'=>'请填写完整申请信息']);
            return;
        }
        $data['name'] = $name;
        $data['type'] = $type;
        $data['price'] = $price;
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['entrust_org'] = $entrust_org;
        $data['conn_user'] = $conn_user;
        $data['conn_phone'] = $conn_phone;
        $data['conn_location'] = $conn_location;
        $data['agent_org'] = '';
        $data['website'] = '';
        $data['descri'] = $descri;

        
        $data['add_time'] = time();
        $data['project_no'] = projectno();
        $data['status'] = 1;
        
        $this->load->model('project_model');
        $ret = $this->project_model->add_project($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '项目合作';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['project_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            parent::deleteCache($random_code);
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'申请项目合作失败']);
        }
    }
    
}
