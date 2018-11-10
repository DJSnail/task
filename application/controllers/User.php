<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        //$this->user = $this->user_model->check_user();
        $this->v = parent::$current_vision;
    }

    function user_login(){
        $scene_str = isset($_COOKIE["scene_id"])?$_COOKIE["scene_id"]:null;
        if(!$scene_str){
            echo -1;
        }
        $redis = $this->phpredis->redis;
        $key = "scene_str_".$scene_str;
        $user = unserialize($redis->get($key));
        if($user && isset($user["nickname"])){
            //login_key
            if(isset($user['uid'])){
                $user['id'] = $user['uid'];
            }
            $user = $this->user_model->get_user_by_uid($user['id'],false);
            //登录后处理session处理
            $this->session->set_userdata(['admin_info'=>$user]);
            $login_key = $this->user_model->calculate_cookie_key($user);
            setcookie("bio_passport",$login_key, time() + 3600 * 24,  "/", $_SERVER['HTTP_HOST']);
            $show_user['nickname'] = $user['nickname'];
            $show_user['image'] = $user['headimgurl'];
            $user['key'] = $key;
            echo json_encode($show_user);
        }else{
            echo -2;
        }
    }
    /**
     * 新增联系人
     */
    function add_connect_user(){
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'请登录']);
            return;
        }
        $username = $this->input->post("username");
        $phone = $this->input->post("phone");
        $email = $this->input->post("email");
        $company = $this->input->post("company");
        $is_main = $this->input->post("is_main");
        if(!$username || !$phone || !$email || !$company){
            echo json_encode(['status'=>-1,'error'=>'请填写主联系人信息']);
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

        $data['username'] = $username;
        $data['phone'] = $phone;
        $data['email'] = $email;
        $data['company'] = $company;
        $data['uid'] = $user['id'];
        $data['is_main'] = $is_main;
        $ret = $this->user_model->add_connect_user($data);
        if($ret){
            echo json_encode(['status'=>1,'user'=>$ret]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'添加主联系人失败']);
        }

    }

    /**
     * 查询一个联系人
     */
    function get_connect_user(){
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'请登录']);
            return;
        }
        $id = $this->input->post("id");
        $user = $this->user_model->get_connect_user($user['id'],$id);
        if(isset($user[0])){
            echo json_encode(['status'=>1,'user'=>$user[0]]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'无联系人信息']);
        }
    }

    /**
     * 更新一个联系人信息
     */
    function update_connect_user(){
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'请登录']);
            return;
        }
        $username = $this->input->post("username");
        $phone = $this->input->post("phone");
        $email = $this->input->post("email");
        $company = $this->input->post("company");
        $id = $this->input->post("id");
        if(!$username || !$phone || !$email || !$company){
            echo json_encode(['status'=>-1,'error'=>'请填写完整用户信息']);
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
        $upt['username'] = $username;
        $upt['phone'] = $phone;
        $upt['email'] = $email;
        $upt['company'] = $company;
        $upt['id'] = $id;

        $ret = $this->user_model->update_connect_user($upt);
        if($ret){
            echo json_encode(['status'=>1,'user'=>$upt]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'删除用户失败']);
        }
    }
    /**
     * 删除一个联系人信息
     */
    function del_connect_user(){
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'请登录']);
            return;
        }
        $id = $this->input->post("id");
        $upt['id'] = $id;
        $upt['uid'] = $user['id'];
        $upt['status'] = 1;
        $ret = $this->user_model->update_connect_user($upt);
        if($ret){
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'删除用户失败']);
        }
    }
    /**
     * 用户订单
     */
    public function order_list()
    {
        $user = $this->user;
        if(!$user){
            parent::go_login();
            return;
        }
        $data['user'] = $user;
        $data['v'] = $this->v;
        $type = $this->input->get('type');
        if(!$type){
            $type = '报告定制';
        }
        $query_array    = $this->input->post("query_array");
        $query_array[]  = [
            'query_field'=>'uid',
            'query_type'=>'=',
            'query_value'=>$user['id'],
        ];
        if(!in_array($type,$this->all_order_type())){
            exit('您的申请类型有误');
            return;
        }
        $result = $this->user_model->get_order_api($query_array,1,10,$type);
        $data['page_array'] = $result['page_array'];
        $data['data_list'] = $result['data'];
        $data['tab'] = "<a>$type</a>";
        $this->load->view('web/order_list.html', $data);
    }
    /**
     * 用户订单，分页
     */
    public function get_order_api_ajax() {
        $user = $this->user;
        if(!$user){
            return;
        }
        $data           = array();
        $current_page   = $this->input->post("current_page");
        $every_page     = 10;
        $query_array    = $this->input->post("query_array");
        $query_array[]  = [
            'query_field'=>'uid',
            'query_type'=>'=',
            'query_value'=>$user['id'],
        ];
        $type           = $this->input->post("type");
        if(!$type){
            $type = '报告定制';
        }
        if(!in_array($type,$this->all_order_type())){
            return;
        }
        $result = $this->user_model->get_order_api($query_array,$current_page,$every_page,$type);
        foreach($result['data'] as &$item){
            $item['add_time'] = date("Y.m.d",$item['add_time']);
            $item['status'] = $this->get_report_status($item['status']);
            $item['type'] = $type;
        }

        $data['page_array']   = $result['page_array'];
        $data['data_list']  = $result['data'];
        echo json_encode($data);

    }
    /**
     * 用户订单详情
     */
    public function order_details()
    {
        $user = $this->user;
        if(!$user){
            parent::go_login();
            return;
        }
        $data['user'] = $user;
        $data['v'] = $this->v;
        $id    = $this->input->get("id");
        $type  = $this->input->get("type");
        if(!$type){
            $type = '报告定制';
        }
        if(!in_array($type,$this->all_order_type())){
            exit("申请类型错误");
        }
        if(!preg_match("/^[0-9]*$/", $id)){
            exit("请检查订单编号");
        }
        $report = $this->user_model->get_order_detail($id,$type,$user["id"]);

        if(!$report){
            exit("请检查订单编号");
        }
        $report["add_time"] = date("Y.m.d",$report["add_time"]);
        $report["edit_time"] = $report["edit_time"]?date("Y.m.d",$report["edit_time"]):"";
        $report["status"] = $this->get_report_status($report["status"]);
        if($report["status"] != "已完成"){
            $report["code"] = "";
        }else{
            if(isset($report["att_id"])){
                $report["code"] = authcode($report["att_id"],'ENCODE');
            }
        }
        $data["report"] = $report;
        if($type == '报告定制'){
            $this->load->view('web/order/custom_report_detail.html', $data);
        }
        if($type == '购买报告'){
            $this->load->view('web/order/purchase_report_detail.html', $data);
        }
        if($type == '文献传递'){
            $this->load->view('web/order/literature_detail.html', $data);
        }
        if($type == '科技查新'){
            $this->load->view('web/order/technology_detail.html', $data);
        }
        if($type == '专题检索'){
            $this->load->view('web/order/subject_detail.html', $data);
        }
        if($type == '专利技术'){
            $this->load->view('web/order/requirement_detail.html', $data);
        }
        if($type == '项目合作'){
            $data["report"]['start_time'] = date("Y.m.d",$data["report"]['start_time']);
            $data["report"]['end_time'] = date("Y.m.d",$data["report"]['end_time']);
            $this->load->view('web/order/project_detail.html', $data);
        }
        if($type == '联系专家'){
            $this->load->view('web/order/expert_detail.html', $data);
        }
        if($type == '申请专家'){
            $this->load->view('web/order/expert_apply.html', $data);
        }
        if($type == '成果转化'){
            $this->load->view('web/order/achievement_detail.html', $data);
        }

    }

    private function get_report_status($status){
        switch($status){
            case 1:
                return '处理中';break;
            case 2:
                return '已驳回';break;
            case 3:
                return '已完成';break;
            case 0:
                return '已撤销';break;
        }
    }

    private function all_order_type(){
        return ['报告定制','购买报告','成果转化','项目合作','申请专家','联系专家','文献传递','科技查新','专题检索'];
    }
    /**
     * 用户登出
     */
    function login_out(){
        setcookie("bio_passport","", time() - 100,  "/", $_SERVER['HTTP_HOST']);
        $this->session->unset_userdata('admin_info');
        $url = 'http://'.$_SERVER['HTTP_HOST'];
        header("location:".$url);
    }
    /**
     * 定制报告申请
     */
    function add_report_order(){
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'未登录']);
            exit();
        }
        $conn_uid = $this->input->post('conn_uid');
        //$report_id = $this->input->post('rid');
        $title = $this->input->post('title');
        if(!$conn_uid || !$title){
            echo json_encode(['status'=>-1,'error'=>'数据错误']);
            exit();
        }
        $data['uid'] = $user['id'];
        $data['main_conn'] = $conn_uid;
        //$data['foreign_id'] = $report_id;
        $data['subject'] = $title;
        $data['report_no'] = reportno();
        $data['add_time'] = time();
        $data['status'] = 1;
        $data['type'] = '购买报告';
        $ret = $this->user_model->add_custom_report($data);
        if($ret){
            echo json_encode(['status'=>1]);
        }else if($ret === 0){
            echo json_encode(['status'=>-1,'error'=>'请勿重复提交申请']);
        } else{
            echo json_encode(['status'=>-1,'error'=>'联系购买失败']);
        }
    }

    /**
     * 购买报告申请
     */
    function add_purchase_report_order(){
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'未登录']);
            exit();
        }
        $conn_uid = $this->input->post('conn_uid');
        $report_id = $this->input->post('rid');
        $title = $this->input->post('title');
        if(!$conn_uid || !$title){
            echo json_encode(['status'=>-1,'error'=>'数据错误']);
            exit();
        }
        $data['uid'] = $user['id'];
        $data['conn_uid'] = $conn_uid;
        $data['foreign_id'] = $report_id;
        $data['report_no'] = reportno();
        $data['add_time'] = time();
        $data['status'] = 1;
        $data['title'] = $title;
        //$data['type'] = '购买报告';
        $ret = $this->user_model->add_purchase_report_order($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '购买报告';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['report_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            echo json_encode(['status'=>1]);
        }else if($ret === 0){
            echo json_encode(['status'=>-1,'error'=>'请勿重复提交申请']);
        } else{
            echo json_encode(['status'=>-1,'error'=>'联系购买失败']);
        }
    }

    /**
     * 联系专家申请
     */
    function add_expert_order(){
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'未登录']);
            exit();
        }
        $conn_uid = $this->input->post('conn_uid');
        $expert_id = $this->input->post('eid');
        $title = $this->input->post('title');
        $solution = $this->input->post('solution');
        if(!$conn_uid || !$expert_id || !$title){
            echo json_encode(['status'=>-1,'error'=>'数据错误']);
            exit();
        }
        $data['uid'] = $user['id'];
        $data['conn_uid'] = $conn_uid;
        $data['foreign_id'] = $expert_id;
        $data['subject'] = $title;
        $data['report_no'] = reportno();
        $data['add_time'] = time();
        $data['status'] = 1;
        $data['solution'] = $solution;
        $ret = $this->user_model->add_expert_order($data);
        if($ret){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $post_data['uid'] = $data['uid'];
            $post_data['type'] = '联系专家';
            $post_data['status'] = '处理中';
            $post_data['order_no'] = $data['report_no'];
            $post_data['add_time'] = date("Y-m-d",$data['add_time']);
            curl_request($url,$post_data);
            echo json_encode(['status'=>1]);
        }else if($ret === 0){
            echo json_encode(['status'=>-1,'error'=>'请勿重复提交申请']);
        } else{
            echo json_encode(['status'=>-1,'error'=>'联系专家失败']);
        }
    }

    /**
     * 联系专家申请
     */
    function add_project_order(){
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'未登录']);
            exit();
        }
        $conn_uid = $this->input->post('conn_uid');
        $project_id = $this->input->post('rid');
        $title = $this->input->post('title');
        if(!$conn_uid || !$project_id || !$title){
            echo json_encode(['status'=>-1,'error'=>'数据错误']);
            exit();
        }
        $data['uid'] = $user['id'];
        $data['conn_uid'] = $conn_uid;
        $data['foreign_id'] = $project_id;
        $data['subject'] = $title;
        $data['report_no'] = reportno();
        $data['add_time'] = time();
        $data['status'] = 1;
        $ret = $this->user_model->add_project_order($data);
        if($ret){
            echo json_encode(['status'=>1]);
        }else if($ret === 0){
            echo json_encode(['status'=>-1,'error'=>'请勿重复提交申请']);
        } else{
            echo json_encode(['status'=>-1,'error'=>'联系专家失败']);
        }
    }
    /**
     * 联系专家申请
     */
    function add_requirement_order(){
        $user = $this->user;
        if(!$user){
            echo json_encode(['status'=>-1,'error'=>'未登录']);
            exit();
        }
        $conn_uid = $this->input->post('conn_uid');
        $require_id = $this->input->post('rid');
        $title = $this->input->post('title');
        if(!$conn_uid || !$require_id || !$title){
            echo json_encode(['status'=>-1,'error'=>'数据错误']);
            exit();
        }
        $data['uid'] = $user['id'];
        $data['conn_uid'] = $conn_uid;
        $data['foreign_id'] = $require_id;
        $data['subject'] = $title;
        $data['report_no'] = reportno();
        $data['add_time'] = time();
        $data['status'] = 1;
        $ret = $this->user_model->add_requirement_order($data);
        if($ret){
            echo json_encode(['status'=>1]);
        }else if($ret === 0){
            echo json_encode(['status'=>-1,'error'=>'请勿重复提交申请']);
        } else{
            echo json_encode(['status'=>-1,'error'=>'联系专家失败']);
        }
    }
}
