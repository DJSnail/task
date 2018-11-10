<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Expert  extends MY_Controller {

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
        foreach($add_array as $item){
            $data = [];
            $data['expert_no'] = expertno();
            $data['uid'] = 0;
            $data['status'] = 3;
            foreach($item as $k => $v){
                if($k == '类型'){
                    $data['type'] = $v;
                }
                if($k == '研究领域'){
                    $data['domain'] = $v;
                }
                if($k == '手机'){
                    $data['phone'] = $v;
                }
                if($k == '代表论著'){
                    $data['works'] = $v;
                }
                if($k == '邮箱'){
                    $data['email'] = $v;
                }
                if($k == '照片'){
                    $v = str_replace("&download","",$v);
                    $time = date('Y-m-d',time());
                    $file = 'uploads/Report/expert/'.$time.'/'.time() . rand(10000, 99999).'.jpg';

                    $data['pic'] = "/public/image/index?path=".upload_image_to_oss($v,$file);
                }
                if($k == '地址'){
                    $data['location'] = $v;
                }
                if($k == '科研项目'){
                    $data['project'] = $v;
                }
                if($k == '姓名'){
                    $data['name'] = $v;
                }
                if($k == '办公电话'){
                    $data['fixed_phone'] = $v;
                }
                if($k == '简历'){
                    $data['resume'] = $v;
                }
                if($k == '职务'){
                    $data['job'] = $v;
                }
                if($k == '单位'){
                    $data['company'] = $v;
                }
                if($k == '提交时间'){
                    $data['add_time'] = strtotime($v);
                }
            }
            $ret = $this->expert_model->add_expert($data);
            echo $k;
            echo ($ret).'<br>';
        }
    }

    /**
     * 领域专家咨询
     */
    function index(){
        $user = $this->user;
        $data['user'] = $user;
        $data['subscribe'] = [];
        $data['subscribe_type'] = '专家咨询';
        if($user){
            $data['subscribe'] = $this->user_model->get_user_subscribe($user['id'],'专家咨询');
        }
        static $limit = 10;
        $data['domain_filter'] = explode(',', trim($this->input->post('domain')));
        $data['company_filter'] = explode(',', trim($this->input->post('company')));
        $data['job_filter'] = explode(',', trim($this->input->post('job')));

        $this->load->model('admin/expert_model');
        $data['filter_list'] = $this->expert_model->getFilterList();

        $param=[
            'page' => $this->input->post('page')?(int)$this->input->post('page'):1,
            'limit' => $limit,
            'status|=' => 3
        ];

        if($data['domain_filter']){
            $param['domain|like'] = implode(',', $data['domain_filter']);
        }
        if($data['company_filter']){
            $param['company|='] = implode(',', $data['company_filter']);
        }
        if($data['job_filter']){
            $param['job|like'] = implode(',', $data['job_filter']);
        }
        if($this->input->post('search')){
            $param['name/company/resume/project|or_like'] = $this->input->post('search');
        }
        $data['search_filter'] = $this->input->post('search');
        $expert_list = $this->expert_model->queryList($param);

        $data['expert_list'] = $expert_list;
        $data['v']=$this->v;

        $this->load->library('pagination');
        $total_rows = (int)$expert_list['data']['totalCount'];

        $page_config = get_page_config($total_rows,$limit,$param['page']);

        $this->pagination->initialize($page_config);
        $data['pagestring']=$this->pagination->create_links();

        $this->load->view('web/expert.html', $data);
    }

    public function detail()
    {
        $expert_no = $this->input->get("expert_no");
        if(!preg_match("/^[0-9]*$/", $expert_no)){
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
        $this->load->model("admin/expert_model");
        $expert = $this->expert_model->getEepertInfo(0,$expert_no);
        if(isset($expert[0])){
            $expert = $expert[0];
            $expert["add_time"] = date("Y-m-d",$expert["add_time"]);
            $expert["edit_time"] = date("Y-m-d",$expert["edit_time"]);
            if($expert['project'] == '无'){
                $expert['project'] = '';
            }
            $data["expert"] = $expert;
            $this->load->view('web/expert_detail.html', $data);
        }else{
            exit("请检查报告编号");
        }
    }
    /**
     * 申请成为专家页面
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
        //研究领域
        $data['domain'] = get_research_domain();
        //专家类型
        $data['type'] = get_expert_type();
        //随机码防止机器人注入
        $code = md5(time().rand(1,99999));
        parent::setToCache($code,'bio',3600 * 24);
        $data['random_code'] = $code;
        $this->load->view('web/expert_apply.html', $data);
    }

    /**
     * 申请成为专家接口
     */
    function add_apply_expert(){
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
        $company = $this->input->post("company");
        $phone = $this->input->post("phone");
        $email = $this->input->post("email");
        $domain = $this->input->post("domain");
        $location = $this->input->post("location");
        $job = $this->input->post("job");
        $type = $this->input->post("type");
        $resume = $this->input->post("resume");
        $project = $this->input->post("project");
        $works = $this->input->post("works");
        $pic = $this->input->post("attachment");
        if(!$name || !$company || !$phone || !$email || !$domain || !$job || !$type || !$resume || !$project || !$works || !$pic || !$location){
            echo json_encode(['status'=>-1,'error'=>'请填写完整申请信息']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status'=>-1,'error'=>'请填写正确邮箱']);
            return;
        }
        if(!preg_match("/^0?1[3|4|5|8][0-9]\d{8}$/", $phone)){
            echo json_encode(['status'=>-1,'error'=>'请填写正确手机号']);
            return;
        }
        $data['name'] = $name;
        $data['company'] = $company;
        $data['phone'] = $phone;
        $data['email'] = $email;
        $data['domain'] = $domain;
        $data['location'] = $location;
        $data['job'] = $job;
        $data['type'] = $type;
        $data['resume'] = $resume;
        $data['project'] = $project;
        $data['works'] = $works;
        $data['pic'] = $pic;
        $data['add_time'] = time();
        $data['expert_no'] = reportno();
        $data['status'] = 1;
        $this->load->model('expert_model');
        $ret = $this->expert_model->add_expert($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '申请专家';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['expert_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            parent::deleteCache($random_code);
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'申请专家失败']);
        }

    }
    
}
