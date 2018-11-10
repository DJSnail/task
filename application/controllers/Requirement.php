<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Requirement  extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }

    function add_requirement_api(){
        $this->load->model('requirement_model');
        $files_dir = '/Users/liyuhang/Documents/拆分15个文件';
        $files_arr = scandir($files_dir);
        foreach($files_arr as $k=>$file_name){
            if($file_name == '.' || $file_name == '..'){
                continue;
            }
            if($k < 16){
                continue;
            }
            echo $k.'<br>';
            $file_dir = $files_dir.'/'.$file_name;
            echo $file_dir.'<br>';
            $fp = fopen($file_dir,"r");
            $add_json = "";
            $buffer = 1024;//每次读取 1024 字节
            while(!feof($fp)){//循环读取，直至读取完整个文件
                $add_json .= fread($fp,$buffer);
            }
            //$add_json = json_replace($add_json);
            $add_array = json_decode(trim($add_json,chr(239).chr(187).chr(191)),true);
            foreach($add_array as $item) {
                $data = [];
                $data['uid'] = 2;
                $data['status'] = 3;
                $data['add_time'] = time();
                foreach ($item as $k => $v) {
                    if ($k == '申请号') {
                        $data['apply_no'] = $v;
                    }
                    if ($k == '标题') {
                        $data['title_en'] = $v;
                    }
                    if ($k == '申请人国别代码') {
                        $data['apply_country'] = $v;
                    }
                    if ($k == '主分类号') {
                        $data['category_no'] = $v;
                    }
                    if ($k == 'IPC') {
                        $data['ipc'] = $v;
                    }
                    if ($k == '申请日') {
                        $data['apply_time'] = strtotime($v);
                    }
                    if ($k == '公开（公告）号') {
                        $data['public_no'] = $v;
                    }
                    if ($k == '摘要') {
                        $data['abstract_en'] = $v;
                    }
                    if ($k == '摘要（翻译）') {
                        $data['abstract_cn'] = $v;
                    }
                    if ($k == '申请人') {
                        $data['apply_user'] = $v;
                    }
                    if ($k == '标题（翻译）') {
                        $data['title_cn'] = $v;
                    }
                    if ($k == '公开（公告）日') {
                        $data['public_time'] = strtotime($v);
                    }
                }
                $ret = $this->requirement_model->add_require($data);
                var_dump($ret);
            }
        }
    }

    /**
     * 专利技术
     */
    function index(){
        $user = $this->user;
        $data['user'] = $user;
        $data['subscribe'] = [];
        $data['subscribe_type'] = '专利技术';
        if($user){
            $data['subscribe'] = $this->user_model->get_user_subscribe($user['id'],'专利技术');
        }
        static $limit = 10;
        $data['category_filter'] = explode(',', trim($this->input->post('category_no')));
        $data['user_filter'] = explode(',', trim($this->input->post('apply_user')));
        if($this->input->post('apply_time')){
            $data['time_filter'] = explode(',', trim(strtotime($this->input->post('apply_time').'-01-01')));
        }else{
            $data['time_filter'] = '';
        }

        $this->load->model('admin/requirement_model');

        $data['filter_list'] = $this->requirement_model->getFilterList();

        $param=[
            'page' => $this->input->post('page')?(int)$this->input->post('page'):1,
            'limit' => $limit,
            'status|=' => 3
        ];

        if($data['category_filter']){
            $param['category_no|='] = implode(',', $data['category_filter']);
        }
        if($data['user_filter']){
            $param['apply_user|='] = implode(',', $data['user_filter']);
        }
        if($data['time_filter']){
            $param['apply_time|time'] = implode(',', $data['time_filter']);
        }
        if($this->input->post('search')){
            $param['title_en/abstract_en|or_like'] = $this->input->post('search');
        }
        $data['search_filter'] = $this->input->post('search');
        $requirement_list = $this->requirement_model->queryList($param);

        //time_filter
        $data['time_filter'] = explode(',', trim($this->input->post('apply_time')));
        $data['requirement_list'] = $requirement_list;
        $data['v']=$this->v;

        $this->load->library('pagination');
        $total_rows = (int)$requirement_list['data']['totalCount'];

        $page_config = get_page_config($total_rows,$limit,$param['page']);

        $this->pagination->initialize($page_config);
        $data['pagestring']=$this->pagination->create_links();

        $this->load->view('web/tech.html', $data);
    }

    public function detail()
    {
        $apply_no = $this->input->get("apply_no");
        if(!preg_match("/^[0-9A-Za-z.]*$/", $apply_no)){
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
        $this->load->model("admin/requirement_model");
        $tech = $this->requirement_model->getTechInfo(0,$apply_no);
        if(isset($tech[0])){
            $tech = $tech[0];
            $tech["apply_time"] = date("Y-m-d",$tech["apply_time"]);
            $tech["public_time"] = date("Y-m-d",$tech["public_time"]);

            $data["tech"] = $tech;
            $this->load->view('web/tech_detail.html', $data);
        }else{
            exit("请检查报告编号");
        }
    }
    
	/**
     * 申请技术成果转化页面
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
        $data['domain'] = $category;
        //随机码防止机器人注入
        $code = md5(time().rand(1,99999));
        parent::setToCache($code,'bio',3600 * 24);
        $data['random_code'] = $code;
        $this->load->view('web/tech_apply.html', $data);
    }
    
    /**
     * 申请技术成果转化
     */
    function add_apply_tech(){
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

        $title = $this->input->post("title");
        $domain = $this->input->post("domain");
        $co_price = $this->input->post("co_price");
        $sale_price = $this->input->post("sale_price");
        $co_type = $this->input->post("co_type");
        $co_type = implode(',',$co_type);
        $descri = $this->input->post("descri");
        $detail = $this->input->post("detail");
        $goal = $this->input->post("goal");
        $attachment = $this->input->post("attachment")?$this->input->post("attachment"):[];
        if(!$title || !$domain || !$co_type|| !$descri|| !$detail ||!$goal ){
            echo json_encode(['status'=>-1,'error'=>'请填写完整申请信息']);
            return;
        }

        if(($co_price && !preg_match("/^[0-9].*$/", $co_price)) || ($sale_price && !preg_match("/^[0-9].*$/", $sale_price))){
            echo json_encode(['status'=>-1,'error'=>'费用请填写数字']);
            return;
        }
        $data['title'] = $title;
        $data['report_no'] = reportno();
        $data['domain'] = $domain;
        $data['co_price'] = $co_price;
        $data['sale_price'] = $sale_price;
        $data['co_type'] = $co_type;
        $data['descri'] = $descri;
        $data['detail'] = $detail;
        $data['goal'] = $goal;
        $data['attachment'] = implode(',',$attachment);;
        $data['add_time'] = time();
        $data['status'] = 1;
        $this->load->model('requirement_model');
        $ret = $this->requirement_model->add_achievement($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '成果转化';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['report_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            parent::deleteCache($random_code);
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'申请技术成果转化失败']);
        }
    }
	
}
