<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
define('UM_MYKEY', '6g1Y780G290udU5seSeecSdlek4Qbia99x0hco8b7ldRbEdv430c9fdT7x8O0vb1');
define('UM_USER_CHECK_USERNAME_FAILED', '用户名不合法');
define('UM_USER_USERNAME_EXISTS', '用户名已存在');
define('UM_USER_PASSWORD_FAILED', '密码长度不合法');
define('COUPON_BY_ID', 'coupon_by_id');

const USER_KEY_EXPIRE = 4320;

class User_model extends MY_Model {

    /**
     * 根据 openId 得到用户信息
     */
    function get_user_by_openid($open_id) {
        $db = $this->db;
        $key = "openId_" . $open_id;
        $ret = $this->getFromCache($key);
        if (!$ret) {
            $open_id = addslashes($open_id);
            $sql = "select * from uc_member where openid='$open_id'";
            $query = $db->query($sql);
            $ret = $query->row_array();
            if (empty($ret)) {
                return false;
            }
            $this->setToCache($key, $ret, 3600 * 24 * 7);
        }
        return $ret;
    }

    /**
     * 根据 uid 得到用户信息
     */
    function get_user_by_uid($uid,$use_redis = true) {
        $udb = $this->db;
        $key = "uid_" . $uid;
        $ret = $this->getFromCache($key);
        if (!$ret || !$use_redis) {
            $sql = "select * from uc_member where id=$uid";
            $query = $udb->query($sql);
            $ret = $query->row_array();
            if (empty($ret)) {
                return false;
            }
            $this->setToCache($key, $ret, 3600 * 24);
        }
        return $ret;
    }

    /**
     * 计算 login_key
     */
    public function calculate_cookie_key($user) {
        if (empty($user))
            return false;
        $uid = $user['id']?$user['id']:$user['uid'];
        $username = $user['openid'];
        $key = authcode($uid . ',' . $username, 'ENCODE', UM_MYKEY);
        return $key;
    }

    /**
     * 新建一个用户
     */
    public function add_user($user) {
        $key = "openId_" . $user["openid"];
        $this->db->insert('uc_member', $user);
        $user["id"] = $this->db->insert_id();
        if ($user["id"]) {
            $this->setToCache($key, $user, 3600 * 24 * 7);
            return $user;
        }else {
            return null;
        }
    }

    /**
     * 根据cookie得到用户信息
     */
    function get_user() {
        $user = ['id'=>4,
            'role_id'=>-1,
            'open_id'=>'o6a_o0v5Lv1M3O8YB_Bhz57fIIQA',
            'nickname'=>'liyuhang',
            'headimgurl'=>'http://wx.qlogo.cn/mmopen/JicMklBtEZF4wnK9qV3STDKRcKpg8QkmxlOGeY23gVQYSOFPuOHq1BEFda7Uic8vaonjGuPibhMLLEMPMeibb5V5csPaNV0p9fbn/0'
        ];
       /* if(!$this->session->userdata("admin_info")){
            $this->session->set_userdata(['admin_info'=>$user]);
        }*/
        return $user;
        if (!array_key_exists('bio_passport', $_COOKIE)) {
            return false;
        } else {
            $login_key = $_COOKIE['bio_passport'];
        }
        if (!$login_key) {
            return false;
        }
        $login_user = $this->getFromCache($login_key);
        if (!$login_user) {
            $uid_username_str = authcode($login_key, 'DECODE', UM_MYKEY);
            $user_ary = explode(',', $uid_username_str);
            $login_user = null;
            if (count($user_ary) != 2) {
                return false;
            } else {
                $uid = $user_ary[0];
                if(!$uid){
                    return false;
                }
                $login_user = $this->get_user_by_uid($uid);
                if (!$login_user) {
                    return false;
                }
                $this->setToCache($login_key, $login_user, 3600 * 24);
            }
        }
        return $login_user;
    }

    /**
     * 查找一个用户下面的联系人
     */
    function get_connect_user($uid,$id=0) {
        if(!$uid){
            return;
        }
        $sql = "select * from uc_connection where uid=$uid and status=0";
        if($id){
            $sql .= " and id=$id";
        }
        $query = $this->db->query($sql);
        $others = $query->result_array();
        return $others;
    }
    /**
     * 查找一个用户下面的主联系人
     */
    function get_main_connect_user($uid){
        if(!$uid){
            return;
        }
        $sql = "select * from uc_connection where uid=$uid and status=0 and is_main=1";

        $query = $this->db->query($sql);
        $user = $query->row_array();
        return $user;
    }

    /**
     * 添加联系人
     */
    function add_connect_user($data) {
        $this->db->insert('uc_connection', $data);
        $uid= $this->db->insert_id();
        if($uid){
            $data['id'] = $uid;
            return $data;
        }
        return null;
    }

    /**
     * 修改联系人
     */
    function update_connect_user($data) {
        $id = $data['id'];
        unset($data['id']);
        $this->db->where('id', $id);
        $ret = $this->db->update('uc_connection', $data);
        return $ret;
    }
    /**
     * 添加定制需求表单
     */
    function add_custom_report($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_report WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }
        $this->db->insert('uc_report', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
    /**
     * 添加联系专家订单
     */
    function add_expert_order($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_expert WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }
        $this->db->insert('uc_expert', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
    /**
     * 添加项目合作订单
     */
    function add_project_order($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_project WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }
        $this->db->insert('uc_project', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
    /**
     * 添加技术需求公开订单
     */
    function add_requirement_order($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_requirement WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }
        $this->db->insert('uc_requirement', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
    /**
     * 添加文献信息
     */
    function add_literature($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_literature WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }

        $this->db->insert('uc_literature', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }

     /**
     * [add_technology 添加查新信息]
     * @param [type] $data [description]
     */
    function add_technology($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_technology WHERE proj_name = ".$data['proj_name']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }

        $this->db->insert('uc_technology', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
	
	/**
     * 添加专题信息
     */
    function add_subject($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_subject WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }
        
        $this->db->insert('uc_subject', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
    /**
     * 用户订单
     */
    function get_order_api($query_array, $current_page = 1, $every_page = 10, $type){
        $condition_str = $this->sql_condition($query_array);
        if($type == '报告定制'){
            $table = 'uc_report';
        }
        if($type == '购买报告'){
            $table = 'uc_purchase_report';
        }
        if($type == '文献传递'){
            $table = 'uc_literature';
        }
        if($type == '科技查新'){
            $table = 'uc_technology';
        }
        if($type == '专题检索'){
            $table = 'uc_subject';
        }
        if($type == '成果转化'){
            $table = 'uc_achievement';
        }
        if($type == '项目合作'){
            $table = 'project_cooperation';
        }
        if($type == '联系专家'){
            $table = 'uc_expert';
        }
        if($type == '申请专家'){
            $table = 'expert_consult';
        }
        $page_array = $this->divide_page("Select count(id) from $table " . $condition_str, "count(id)", $current_page, $every_page);
        $start_num = $page_array['start_num'];
        if($type == '报告定制'){
            $sql = "Select * from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '购买报告'){
            $sql = "Select *,title as subject from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '文献传递'){
            $sql = "Select *,doc_title as subject,doc_no as report_no from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '科技查新'){
            $sql = "Select *,proj_name as subject from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '专题检索'){
            $sql = "Select *,subject_no as report_no from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '成果转化'){
            $sql = "Select *,title as subject from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '项目合作'){
            $sql = "Select *, name as subject,project_no as report_no from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '联系专家'){
            $sql = "Select * from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        if($type == '申请专家'){
            $sql = "Select *, name as subject,expert_no as report_no from $table " . $condition_str . "ORDER BY id desc limit $start_num,$every_page";
        }
        $query = $this->db->query($sql);
        $data = $query->result_array();
        $result['data'] = $data;
        $result['page_array'] = $page_array;
        return $result;
    }
    /**
     * 用户订单详情
     */
    function get_order_detail($report_no,$type,$uid){
        if($type == '报告定制'){
            $sql = "SELECT ur.*,ur.op_attachment as att_id,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_report AS ur
            INNER JOIN uc_connection AS uc ON uc.id = ur.main_conn
            LEFT JOIN uc_member AS um ON um.id = ur.op_uid
            WHERE ur.uid = $uid AND ur.report_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        if($type == '购买报告'){
            $sql = "SELECT pr.*,
                      upr.status,upr.add_time,upr.edit_time,upr.report_no,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_purchase_report AS upr
            INNER JOIN purchase_report AS pr ON pr.id = upr.foreign_id
            INNER JOIN uc_connection AS uc ON uc.id = upr.conn_uid
            LEFT JOIN uc_member AS um ON um.id = upr.verify_uid
            WHERE upr.uid = $uid AND upr.report_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
        }
        if($type == '需求公开'){
            $sql = "SELECT ec.*,
                      ue.status,ue.add_time,ue.edit_time,ue.report_no,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_requirement AS ue
            INNER JOIN tech_requirement AS ec ON ec.id = ue.foreign_id
            INNER JOIN uc_connection AS uc ON uc.id = ue.conn_uid
            LEFT JOIN uc_member AS um ON um.id = ue.verify_uid
            WHERE ue.uid = $uid AND ue.report_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
        }
        if($type == '项目合作'){
            $sql = "SELECT ue.*,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM project_cooperation AS ue
            INNER JOIN uc_connection AS uc ON uc.id = ue.main_conn
            LEFT JOIN uc_member AS um ON um.id = ue.verify_uid
            WHERE ue.uid = $uid AND ue.project_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        if($type == '联系专家'){
            $sql = "SELECT ec.*,
                      ue.status,ue.add_time,ue.edit_time,ue.solution,ue.report_no,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_expert AS ue
            INNER JOIN expert_consult AS ec ON ec.id = ue.foreign_id
            INNER JOIN uc_connection AS uc ON uc.id = ue.conn_uid
            LEFT JOIN uc_member AS um ON um.id = ue.verify_uid
            WHERE ue.uid = $uid AND ue.report_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
        }
        if($type == '申请专家'){
            $sql = "SELECT
                      ue.*,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM expert_consult AS ue
            INNER JOIN uc_connection AS uc ON uc.id = ue.main_conn
            LEFT JOIN uc_member AS um ON um.id = ue.verify_uid
            WHERE ue.uid = $uid AND ue.expert_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        if($type == "文献传递"){
            $sql = "SELECT ur.*,ur.doc_title as subject,ur.doc_no as report_no,ur.op_attachment as att_id,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_literature AS ur
            INNER JOIN uc_connection AS uc ON uc.id = ur.main_conn
            LEFT JOIN uc_member AS um ON um.id = ur.op_uid
            WHERE ur.uid = $uid AND ur.doc_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        if($type == "科技查新"){
            $sql = "SELECT ur.*,ur.op_attachment as att_id,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_technology AS ur
            INNER JOIN uc_connection AS uc ON uc.id = ur.main_conn
            LEFT JOIN uc_member AS um ON um.id = ur.op_uid
            WHERE ur.uid = $uid AND ur.report_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            $data['check_goal'] = get_search_aim()[$data['check_goal']];
            $data['check_range'] = get_search_range()[$data['check_range']];
            $data['subject_category'] = get_subject_category()[$data['subject_category']];
            $data['industry_category'] = get_industry_category()[$data['industry_category']];
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        if($type == "专题检索"){
            $sql = "SELECT ur.*,ur.op_attachment as att_id,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_subject AS ur
            INNER JOIN uc_connection AS uc ON uc.id = ur.main_conn
            LEFT JOIN uc_member AS um ON um.id = ur.op_uid
            WHERE ur.uid = $uid AND ur.subject_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        if($type == '成果转化'){
            $sql = "SELECT ur.*,ur.op_attachment as att_id,
                      uc.username,uc.email,uc.phone,uc.company,
                      um.nickname AS op_username
            FROM uc_achievement AS ur
            INNER JOIN uc_connection AS uc ON uc.id = ur.main_conn
            LEFT JOIN uc_member AS um ON um.id = ur.op_uid
            WHERE ur.uid = $uid AND ur.report_no = '$report_no'";
            $query = $this->db->query($sql);
            $data = $query->row_array();
            if($data['other_conn']){
                $sql = "select * from uc_connection where id in (".$data['other_conn'].") and is_main=0 and status=0";
                $query = $this->db->query($sql);
                $other_conn = $query->result_array();
                $data['other_conn'] = $other_conn;
            }else{
                $data['other_conn'] = [];
            }
        }
        return $data;
    }
    /**
     * 添加购买报告订单
     */
    function add_purchase_report_order($data){
        if(isset($data['foreign_id']) && $data['foreign_id'] > 0){
            $sql = "select id from uc_purchase_report WHERE foreign_id = ".$data['foreign_id']. " and uid = ".$data['uid'] . " and status = 1";
            $query = $this->db->query($sql);
            $ret = $query->row_array();
            if($ret){
                return 0;
            }
        }
        $this->db->insert('uc_purchase_report', $data);
        $id= $this->db->insert_id();
        if($id){
            return $id;
        }
        return null;
    }
    /**
     * 科技成果数据
     */
    function get_achievement_num(){
        $key = "achievement_num";
        $ret = $this->getFromCache($key);
        if($ret && count($ret)){
            return $ret;
        }
        $sql = "select count(id) as cnt from expert_consult where status=3";
        $query = $this->db->query($sql);
        $data = $query->row_array();
        $expert_cnt = $data['cnt'];
        $sql = "select count(id) as cnt from tech_requirement where status=3";
        $query = $this->db->query($sql);
        $data = $query->row_array();
        $tech_cnt = $data['cnt'];
        $sql = "select count(id) as cnt from project_cooperation where status=3";
        $query = $this->db->query($sql);
        $data = $query->row_array();
        $project_cnt = $data['cnt'];
        $ret["expert_num"] = $expert_cnt;
        $ret["project_num"] = $tech_cnt + $project_cnt;
        $this->setToCache($key, $ret, 3600 * 24);
        return $ret;
    }
    /**
     * 产业监测数据
     */
    function get_monitor_num(){
        $key = "monitor_data3";
        $ret = $this->getFromCache($key);
        if($ret && count($ret)){
            return $ret;
        }
        $sql = "select count(id) as cnt from st_monitor";
        $query = $this->db->query($sql);
        $data = $query->row_array();
        $monitor_cnt = $data['cnt'];
        $sql = "select mdate,title,`key`,institute from st_monitor order by id desc limit 6";
        $query = $this->db->query($sql);
        $res = $query->result_array();
        $monitor_title = [];
        foreach($res as $item){
            $filter_institute = get_filter_institute();
            array_push($filter_institute,$item['institute']);
            $item["title"] = str_replace($filter_institute,'',$item["title"]);
            $monitor_title[] = "<a href='/monitor/detail?key=".$item['key']."' target='_blank'>".$item["title"]."</a>";
        }
        $date = $res[0]['mdate'];
        $date = str_replace(["T","Z"]," ",$date);
        $ret["monitor_num"] = $monitor_cnt;
        $ret["update_time"] = $date;
        $ret["monitor_title"] = $monitor_title;
        $this->setToCache($key, $ret, 3600 * 24);
        return $ret;
    }
    /**
     * 用户添加/更新订阅关键词
     */
    function add_user_subscribe($data){
        $uid = $data['uid'];
        $type = $data['type'];
        $sql = "select id from uc_subscribe where uid=$uid and type='$type'";
        $query = $this->db->query($sql);
        $ret = $query->row_array();
        if($ret){
            //更新
            $this->db->where('id', $ret['id']);
            $this->db->update('uc_subscribe', $data);
        }else{
            //添加
            $this->db->insert('uc_subscribe', $data);
            $id= $this->db->insert_id();
            if($id){
                return $id;
            }
            return null;
        }
    }
    /**
     * 获得一个用户在一个类目下的订阅词
     */
    function get_user_subscribe($uid,$type){
        if(!$uid){
            return [];
        }
        if(!in_array($type,['专利技术','项目合作','专家咨询','产业信息','分析报告'])){
            return [];
        }
        $sql = "select * from uc_subscribe where uid=$uid and type='$type' limit 1";
        $query = $this->db->query($sql);
        $data = $query->row_array();
        if($data){
            $subscribe = $data['subscribe'];
            return explode(',',$subscribe);
        }
        return [];
    }
    /**
     * 获得一个用户在所有类目下的订阅词
     */
    function get_my_subscribe($uid){
        if(!$uid){
            return [];
        }
        $sql = "select * from uc_subscribe where uid=$uid";
        $query = $this->db->query($sql);
        $data = $query->result_array();
        $type = ['项目合作','产业信息','专利技术','专家咨询','分析报告'];
        $ret = [];
        foreach($data as $item){
            if(!$item['subscribe']){
                $ret[$item['type']] = ['所有更新'];
            }else{
                $ret[$item['type']] = explode(',',$item['subscribe']);
            }
        }
        foreach($type as $item){
            if(!isset($ret[$item])){
                $ret[$item] = [];
            }
        }
        return $ret;
    }
    /**
     * 定时任务,检查用户订阅内容是否更新,一天一次
     */
    function check_subscribe_update($type){
        if(!in_array($type,['专利技术','项目合作','专家咨询','产业信息','分析报告'])){
            return;
        }
        $sql = "select * from uc_subscribe where type='$type'";
        $query = $this->db->query($sql);
        $data = $query->result_array();
        //这里定义拉取时间段, 2天前到1天前
        $begin_time = time() - 24 * 3600 * 2;
        $end_time = time() - 24 * 3600 * 1;
        $where_time = " add_time > $begin_time and add_time <= $end_time ";
        $limit = 100;
        foreach($data as $item){
            //每一个订阅用户
            $subscribe = $item['subscribe'];
            $subscribe_arr = explode(",",$subscribe);
            $uid = $item['uid'];
            $openid = $item['openid'];
            $id_list = [];
            if(!$subscribe){
                //每一个订阅的关键词
                if($type == '产业信息'){
                    $sql = "SELECT id FROM st_monitor WHERE
                       $where_time order by id desc limit $limit";
                    $query = $this->db->query($sql);
                    $list = $query->result_array();
                    foreach($list as $ret){
                        $id_list[] = $ret['id'];
                    }
                }
                if($type == '专利技术'){
                    $sql = "SELECT id FROM tech_requirement WHERE
                       $where_time order by id desc limit $limit";
                    $query = $this->db->query($sql);
                    $list = $query->result_array();
                    foreach($list as $ret){
                        $id_list[] = $ret['id'];
                    }
                }
                if($type == '项目合作'){
                    $sql = "SELECT id FROM project_cooperation WHERE
                       $where_time order by id desc limit $limit";
                    $query = $this->db->query($sql);
                    $list = $query->result_array();
                    foreach($list as $ret){
                        $id_list[] = $ret['id'];
                    }
                }
                if($type == '专家咨询'){
                    $sql = "SELECT id FROM expert_consult WHERE
                      $where_time order by id desc limit $limit";
                    $query = $this->db->query($sql);
                    $list = $query->result_array();
                    foreach($list as $ret){
                        $id_list[] = $ret['id'];
                    }
                }
                if($type == '分析报告'){
                    $sql = "SELECT id FROM purchase_report WHERE
                       $where_time order by id desc limit $limit";
                    $query = $this->db->query($sql);
                    $list = $query->result_array();
                    foreach($list as $ret){
                        $id_list[] = $ret['id'];
                    }
                }
            }else{
                foreach($subscribe_arr as $item){
                    if(!$item){
                        continue;
                    }
                    //每一个订阅的关键词
                    if($type == '产业信息'){
                        $sql = "SELECT id FROM st_monitor WHERE
                       $where_time AND
                      (title LIKE '%$item%' OR content LIKE '%$item%' OR institute LIKE '%$item%') order by id desc limit $limit";
                        $query = $this->db->query($sql);
                        $list = $query->result_array();
                        foreach($list as $ret){
                            $id_list[] = $ret['id'];
                        }
                    }
                    if($type == '专利技术'){
                        $sql = "SELECT id FROM tech_requirement WHERE
                       $where_time AND
                      (title_cn LIKE '%$item%' OR abstract_cn LIKE '%$item%') order by id desc limit $limit";
                        $query = $this->db->query($sql);
                        $list = $query->result_array();
                        foreach($list as $ret){
                            $id_list[] = $ret['id'];
                        }
                    }
                    if($type == '项目合作'){
                        $sql = "SELECT id FROM project_cooperation WHERE
                       $where_time AND
                      (`name` LIKE '%$item%' OR descri LIKE '%$item%' OR `type` LIKE '%$item%') order by id desc limit $limit";
                        $query = $this->db->query($sql);
                        $list = $query->result_array();
                        foreach($list as $ret){
                            $id_list[] = $ret['id'];
                        }
                    }
                    if($type == '专家咨询'){
                        $sql = "SELECT id FROM expert_consult WHERE
                       $where_time AND
                      (`domain` LIKE '%$item%' OR `name` LIKE '%$item%' OR `resume` LIKE '%$item%' OR `works` LIKE '%$item%') order by id desc limit $limit";
                        $query = $this->db->query($sql);
                        $list = $query->result_array();
                        foreach($list as $ret){
                            $id_list[] = $ret['id'];
                        }
                    }
                    if($type == '分析报告'){
                        $sql = "SELECT id FROM purchase_report WHERE
                       $where_time AND
                      (`title` LIKE '%$item%' OR `keyword` LIKE '%$item%' OR `category` LIKE '%$item%' OR `detail` LIKE '%$item%') order by id desc limit $limit";
                        $query = $this->db->query($sql);
                        $list = $query->result_array();
                        foreach($list as $ret){
                            $id_list[] = $ret['id'];
                        }
                    }

                }
            }
            $id_list = array_unique($id_list);
            //这个用户订阅内容没有更新
            if(!count($id_list)){
                continue;
            }
            $sub["uid"] = $uid;
            $sub["openid"] = $openid;
            $sub["id_list"] = implode(",",$id_list);
            $sub["type"] = $type;
            $sub["status"] = 0;
            $sub["add_time"] = time();
            $ret = $this->db->insert('uc_subscribe_record', $sub);
        }
    }
    /**
     * 定时任务,给用户发送订阅
     */
    function get_subscribe_update($type){
        if(!in_array($type,['专利技术','项目合作','专家咨询','产业信息','分析报告'])){
            return;
        }
        $sql = "select * from uc_subscribe_record where status=0 and type='$type'";
        $query = $this->db->query($sql);
        $list = $query->result_array();
        //不重复推送数据
        $sql = "update uc_subscribe_record set status=1 where status=0 and type='$type'";
        $this->db->query($sql);
        $ret = [];
        foreach($list as $item){
            if(array_key_exists($item['uid'],$ret)){
                $ret[$item['uid']]['id_list'] .= ','.$item['id_list'];
            }else{
                $ret[$item['uid']] = $item;
            }
        }
        //对每一个用户
        $users = [];
        foreach($ret as $item){
            $user = [];
            $id_list = array_unique(explode(",",$item['id_list']));
            $id_top_list = array_slice($id_list,0,3,true);
            $user['openid'] = $item["openid"];
            $id_str = implode(",",$id_list);
            $id_top_str = implode(",",$id_top_list);
            $title_text = "";
            //每一个订阅的关键词
            if($type == "产业信息"){
                $sql = "select title from st_monitor where id in ($id_top_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $k=>$item){
                    $title_text .= ($k+1).".".$item["title"]."\r\n";
                }
                if(count($id_top_list) == 3){
                    $title_text .= "……";
                }
                $title_text .= "(共".count($id_list)."条)";
                $url = 'http://'.$_SERVER['HTTP_HOST']."/m/monitor?ids=".urlencode($id_str);
            }
            if($type == '专利技术'){
                $sql = "select title_cn from tech_requirement where id in ($id_top_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $k=>$item){
                    $title_text .= ($k+1).".".$item["title_cn"]."\r\n";
                }
                if(count($id_top_list) == 3){
                    $title_text .= "……";
                }
                $title_text .= "(共".count($id_list)."条)";
                $url = 'http://'.$_SERVER['HTTP_HOST']."/m/tech?ids=".urlencode($id_str);
            }
            if($type == '项目合作'){
                $sql = "select name from project_cooperation where id in ($id_top_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $k=>$item){
                    $title_text .= ($k+1).".".$item["name"]."\r\n";
                }
                if(count($id_top_list) == 3){
                    $title_text .= "……";
                }
                $title_text .= "(共".count($id_list)."条)";
                $url = 'http://'.$_SERVER['HTTP_HOST']."/m/project?ids=".urlencode($id_str);
            }
            if($type == '专家咨询'){
                $sql = "select name,job from expert_consult where id in ($id_top_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $k=>$item){
                    $title_text .= ($k+1).".".$item["name"].':'.$item["job"]."\r\n";
                }
                if(count($id_top_list) == 3){
                    $title_text .= "……";
                }
                $title_text .= "(共".count($id_list)."条)";
                $url = 'http://'.$_SERVER['HTTP_HOST']."/m/expert?ids=".urlencode($id_str);
            }
            if($type == '分析报告'){
                $sql = "select title from purchase_report where id in ($id_top_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $k=>$item){
                    $title_text .= ($k+1).".".$item["title"]."\r\n";
                }
                if(count($id_top_list) == 3){
                    $title_text .= "……";
                }
                $title_text .= "(共".count($id_list)."条)";
                $url = 'http://'.$_SERVER['HTTP_HOST']."/m/report?ids=".urlencode($id_str);
            }
            $user['url'] = $url;
            $user['remark'] = $title_text;
            $user['type'] = $type;
            $users[] = $user;
        }
        return $users;
    }

    function crontab(){
        $data['text'] = time();
        $this->db->insert('tx_crontab', $data);
    }
    /**
     * 用户历史推送
     */
    function get_history_push($uid,$page){
        $num = 10;
        $page = $page * $num;
        $sql = "select * from uc_subscribe_record where uid=$uid and status=1 order by id desc limit $page,$num";
        $query = $this->db->query($sql);
        $list = $query->result_array();
        if(!$list){
            return [];
        }
        $data =[];
        foreach($list as $item){
            if($item['type'] == '产业信息'){
                $id_str = $item['id_list'];
                $sql = "select id,title,mdate,institute from st_monitor where id in ($id_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $t){
                    $ret = [];
                    $ret['title'] = $t['title'];
                    $ret['add_time'] = str_replace(["T","Z"]," ",$t['mdate']);
                    $ret['sub'] = $t['institute'];
                    $ret['type'] = '产业信息';
                    $ret['url'] = 'http://'.$_SERVER['HTTP_HOST']."/m/monitor_detail?id=".$t['id'];
                    $data[] = $ret;
                }
            }
            if($item['type'] == '专利技术'){
                $id_str = $item['id_list'];
                $sql = "select id,title_en,public_time,public_no from tech_requirement where id in ($id_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $t){
                    $ret = [];
                    $ret['title'] = $t['title_en'];
                    $ret['add_time'] = date("Y-m-d",$t['public_time']);
                    $ret['sub'] = $t['public_no'];
                    $ret['type'] = '专利技术';
                    $ret['url'] = 'http://'.$_SERVER['HTTP_HOST']."/m/tech_detail?id=".$t['id'];
                    $data[] = $ret;
                }
            }
            if($item['type'] == '项目合作'){
                $id_str = $item['id_list'];
                $sql = "select id,name,add_time,type from project_cooperation where id in ($id_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $t){
                    $ret = [];
                    $ret['title'] = $t['name'];
                    $ret['add_time'] = date("Y-m-d",$t['add_time']);
                    $ret['sub'] = $t['type'];
                    $ret['type'] = '项目合作';
                    $ret['url'] = 'http://'.$_SERVER['HTTP_HOST']."/m/project_detail?id=".$t['id'];
                    $data[] = $ret;
                }
            }
            if($item['type'] == '专家咨询'){
                $id_str = $item['id_list'];
                $sql = "select id,name,add_time,job from expert_consult where id in ($id_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $t){
                    $ret = [];
                    $ret['title'] = $t['name'];
                    $ret['add_time'] = date("Y-m-d",$t['add_time']);
                    $ret['sub'] = $t['job']!='无'?$t['job']:'';
                    $ret['type'] = '专家咨询';
                    $ret['url'] = 'http://'.$_SERVER['HTTP_HOST']."/m/expert_detail?id=".$t['id'];
                    $data[] = $ret;
                }
            }
            if($item['type'] == '分析报告'){
                $id_str = $item['id_list'];
                $sql = "select id,title,add_time,category from purchase_report where id in ($id_str)";
                $query = $this->db->query($sql);
                $t_arr = $query->result_array();
                foreach($t_arr as $t){
                    $ret = [];
                    $ret['title'] = $t['title'];
                    $ret['add_time'] = date("Y-m-d",$t['add_time']);
                    $ret['sub'] = $t['category'];
                    $ret['type'] = '分析报告';
                    $ret['url'] = 'http://'.$_SERVER['HTTP_HOST']."/m/report_detail?id=".$t['id'];
                    $data[] = $ret;
                }
            }
        }
        return $data;
    }

}
