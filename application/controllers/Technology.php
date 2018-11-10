<?php
/************************************************************
** @Description: 科技查新
** @Author: haodaquan
** @Date:   2018-01-25 10:33:56
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-25 15:07:46
*************************************************************/

defined('BASEPATH') OR exit('No direct script access allowed');

class Technology extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }
    
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

        $data['search_aim'] 		= get_search_aim();
        $data['search_range'] 		= get_search_range();
        $data['subject_category'] 	= get_subject_category();
        $data['industry_category'] 	= get_industry_category();

        //随机码防止机器人注入
        $code = md5(time().rand(1,99999));
        parent::setToCache($code,'bio',3600 * 24);
        $data['random_code'] = $code;
        $this->load->view('web/technology.html', $data);

    }

    /**
     * 新增查新
     */
    function add_technology(){
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

        $proj_name = $this->input->post("proj_name");
        $check_range = $this->input->post("check_range");
        $check_goal = $this->input->post("check_goal");
        $proj_points = $this->input->post("proj_points");
        $subject_category = $this->input->post("subject_category");
        $novelty_point = $this->input->post("novelty_point");
        $retrieval_words = $this->input->post("retrieval_words");
        $attachment_ids = $this->input->post("attachment_ids");
        $industry_category = $this->input->post("industry_category");


        if(!$proj_name || !$proj_points || !$novelty_point || !$retrieval_words || !$attachment_ids ){
            echo json_encode(['status'=>-1,'error'=>'请填写完整查新信息']);
            return;
        }
        $data['proj_name'] = $proj_name;
        $data['check_range'] = $check_range;
        $data['check_goal'] = $check_goal;
        $data['proj_points'] = $proj_points;
        $data['subject_category'] = $subject_category;
        $data['novelty_point'] = $novelty_point;
        $data['retrieval_words'] = $retrieval_words;
        $data['attachment_ids'] = $attachment_ids;
        $data['industry_category'] = $industry_category;

        $data['add_time'] = time();
        $data['status'] = 1;
        $data['report_no'] = reportno();

        $ret = $this->user_model->add_technology($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '科技查新';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['report_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            parent::deleteCache($random_code);
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'添加查新失败']);
        }

    }
}
