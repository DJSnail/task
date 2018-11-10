<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }

    /**
     * 新增联系人
     */
    function add_custom_report(){
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
        $domain = $this->input->post("domain");
        $subject = $this->input->post("subject");
        $keyword = $this->input->post("keyword");
        $purpose = $this->input->post("purpose");
        $solution = $this->input->post("solution");
        $attachment = $this->input->post("attachment")?$this->input->post("attachment"):[];
        if(!$domain || !$subject || !$keyword || !$purpose || !$solution){
            echo json_encode(['status'=>-1,'error'=>'请填写完整报告信息']);
            return;
        }
        $data['domain'] = $domain;
        $data['subject'] = $subject;
        $data['keyword'] = $keyword;
        $data['purpose'] = $purpose;
        $data['solution'] = $solution;
        $data['attachment'] = implode(',',$attachment);
        $data['add_time'] = time();
        $data['report_no'] = reportno();
        $data['status'] = 1;

        $ret = $this->user_model->add_custom_report($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '报告定制';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['report_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            parent::deleteCache($random_code);
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'添加定制需求表单失败']);
        }

    }

    /**
     * 报告定制
     */
    public function custom_report()
    {
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
        //随机码防止机器人注入
        $code = md5(time().rand(1,99999));
        parent::setToCache($code,'bio',3600 * 24);
        $data['random_code'] = $code;
        $this->load->view('web/custom_report.html', $data);
    }
    /**
     * 更多报告
     */
    public function more_reports()
    {
        $user = $this->user;
        $data['user'] = $user;
        static $limit = 10;
        $data['subscribe'] = [];
        $data['subscribe_type'] = '分析报告';
        if($user){
            $data['subscribe'] = $this->user_model->get_user_subscribe($user['id'],'分析报告');
        }
        $data['category_filter']=explode(',', trim($this->input->post('category')));
        $data['company_filter']=explode(',', trim($this->input->post('company')));

        $this->load->model('admin/report_model');
        $data['filter_list']=$this->report_model->getFilterList();
        
        $param_top=[
        	'limit'=>1,
        	'status|='=>3,
        	'is_top|='=>1
        ];
        $report_top = $this->report_model->queryList($param_top); 

		if($report_top['status']==200 && $report_top['data'] && !empty($report_top['data']['items']))
        	$data['report_top']=$report_top['data']['items'][0];
        
        $param=[
            'page'=>$this->input->post('page')?(int)$this->input->post('page'):1,
            'limit'=>$limit,
            'status|='=>3
        ];

        if($data['category_filter']){
            $param['category_root|in']= str_to_realstr(implode(',', $data['category_filter']));
        }

        if($data['company_filter']){
            $param['company|like']= implode(',', $data['company_filter']);
        }
        if($this->input->post('search')){
            $param['title/detail/keyword|or_like'] = $this->input->post('search');
        }
        $data['search_filter'] = $this->input->post('search');
        //var_dump($param);
        $report_list = $this->report_model->queryList($param);

        $data['report_list']=$report_list;
        $data['v']=$this->v;

        $this->load->library('pagination');
        $total_rows = (int)$report_list['data']['totalCount'];

        $page_config = get_page_config($total_rows,$limit,$param['page']);

        $this->pagination->initialize($page_config);
        $data['pagestring']=$this->pagination->create_links();

        $this->load->view('web/more_reports.html', $data);
    }
    /**
     * 报告详情
     */
    function detail(){
        $report_no = $this->input->get("report_no");
        if(!preg_match("/^[0-9]*$/", $report_no)){
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
        $this->load->model("admin/report_model");
        $report = $this->report_model->getReportInfo(0,$report_no);
        if(isset($report[0])){
            $report = $report[0];
            $report["public_time"] = date("Y-m-d",$report["public_time"]);
            $report["pdf_id"] = authcode($report["pdf_id"],"ENCODE");
            $data["report"] = $report;
            $this->load->view('web/report_detail.html', $data);
        }else{
            exit("请检查报告编号");
        }

    }
    
}
