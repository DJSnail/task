<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subject extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }

    /**
     * 新增联系人
     */
    function add_subject(){

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
        $subject = $this->input->post("subject");
        $limit = $this->input->post("limit");
        $zh_key = $this->input->post("zhKey");
        $en_key = $this->input->post("enKey");
        $goal = $this->input->post("goal");
        $pool = $this->input->post("pool");
        $summary = $this->input->post("summary");
        if(!$subject || !$limit || !$zh_key || !$en_key || !$goal || !$pool || !$summary){
            echo json_encode(['status'=>-1,'error'=>'请填写完整文献信息']);
            return;
        }
        $data['subject'] = $subject;
        $data['limit'] = $limit;
        $data['zh_key'] = $zh_key;
        $data['en_key'] = $en_key;
        $data['goal'] = $goal;
        $data['pool'] = $pool;
        $data['summary'] = $summary;
        $data['add_time'] = time();
        $data['subject_no'] = subjectno();
        $data['status'] = 1;
        
        $ret = $this->user_model->add_subject($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '专题检索';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['subject_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            parent::deleteCache($random_code);
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'添加文献信息失败']);
        }

    }

    /**
     * 文献传递
     */
    public function index()
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
        $this->load->view('web/subject.html', $data);
    }

}
