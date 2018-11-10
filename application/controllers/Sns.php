<?php
defined('BASEPATH') OR exit('No direct script access allowed');

define ( 'CALLBACK','http://www.haimao.cn/sns/verify');
define ( 'UC_USER_CHECK_USERNAME_FAILED', - 1 );
define ( 'UC_USER_USERNAME_BADWORD', - 2 );
define ( 'UC_USER_USERNAME_EXISTS', - 3 );
define ( 'UC_USER_EMAIL_FORMAT_ILLEGAL', - 4 );
define ( 'UC_USER_EMAIL_ACCESS_ILLEGAL', - 5 );
define ( 'UC_USER_EMAIL_EXISTS', - 6 );
define ( 'UC_USER_IDENTIFY_EXISTS', - 7 );
define ( "TOKEN_EXPIRES", 24 * 3600 );
define ( "UC_TOKEN_EXPIRES", 24 * 3600 * 30 );
define ( 'UC_MYKEY', '6g1Y780G290udU5seSeecSdlek4Qbia99x0hco8b7ldRbEdv430c9fdT7x8O0vb1' );

define ( 'WX_TOKEN', 'DajiangdongquLangtaojin');
define ( 'WX_APP_ID', 'wx353b7f66a55f34b5');
define ( 'WX_SECRET', '0887385fdc23cf43492289ba86978792');

class Sns extends MY_Controller{
	function __construct(){
        parent::__construct();
        $this->v = parent::$current_vision;
        error_reporting(E_ALL ^ E_DEPRECATED);
		ini_set('display_errors', 1);
    }
    /**
     * sns登录拼出一个请求路径
     */
    public function login() {

		if ($this->input->get ('continue_url')) {
			$data['continue'] = $this->input->get ('continue_url');
		} else {
			$data['continue'] = 'http://'.$_SERVER['HTTP_HOST'];
		}
        $data['url'] = $this->get_wx_pic("page");
        $data['v'] = $this->v;
        $this->load->view('web/home/login.html', $data);
	}

	/**
	 * [show_qrcode 测试 显示订阅二维码]
	 * @return [type] [description]
	 */
	public function show_qrcode()
	{
		//参数 str = "场景类型|参数1,参数2,参数3|类型|156662221" 时间戳
        if(!is_ajax()){
            return;
        }
        $type = $this->input->post("type");
        $subscribe = $this->input->post("subscribe");
        //订阅全部
        if(!$subscribe){
            $subscribe_text = '';
        }else{
            //为了防止逗号和特殊字符
            foreach($subscribe as &$item) {
                $item = str_replace([",","，","="],"",$item);
                $item = addslashes($item);
                $item = strip_tags($item);
                $item = substr($item,0,50);
            }
            $subscribe_text = implode(',',$subscribe);
        }
		$str = "subscribe_text|$subscribe_text|$type|".time();
		$qrcode_url = $this->get_wx_qrcode($str);
		if ($qrcode_url==false) {
			echo "ERROR";
		}else{
			echo '<img src="'.$qrcode_url.'"/>';
		}
	}

    /**
     * 微信公众号创建菜单
     */
    public function wx_menu(){
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".WX_APP_ID."&secret=".WX_SECRET;
        $ret = curl_request($url,null);
        $ret = json_decode($ret,true);
        $access_token = $ret["access_token"];
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$access_token";
        $data = array(
            "button"=>array(
                array("type"=>"view","name"=>"平台介绍","url"=>"http://www.biomassinfo.cn/m/subscribe"),
                array("type"=>"view","name"=>"订阅管理","url"=>"http://www.biomassinfo.cn/sns/my_subscribe"),
                array("type"=>"view","name"=>"推送历史","url"=>"http://www.biomassinfo.cn/sns/history_push")
                /*array("name"=>"个人中心",
                    "sub_button"=>array(
                        array("type"=>"view","name"=>"我的订单","url"=>"http://www.haimao.cn/m/my_order"),
                        array("type"=>"view","name"=>"我的喜欢","url"=>"http://www.haimao.cn/m/my_favor"),
                        //array("type"=>"click","name"=>"联系客服","key"=>"HMJ_CONTACT_KEFU")
                        //array("type"=>"view","name"=>"联系客服","url"=>"http://mp.weixin.qq.com/s?__biz=MzA4MjA4NjcyNg==&mid=313369630&idx=1&sn=8d5cb898bb7e8dd627cbb9eaacfacbb7#rd")
                    )
                )*/
            )
        );
        $data = json_encode($data);
        $data = preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2','UTF-8', pack('H4', '\\1'))", $data);
        $data = str_replace("\/",'/',$data);
        $tmpInfo = curl_request($url,$data);
        var_dump($tmpInfo);
    }

	/**
	 * [get_wx_qrcode 创建二维码]
	 * @param  array  $param [二维码携带的参数]
	 * @return [type]        [description]
	 */
    public function get_wx_qrcode($param=""){
    	
    	$access_token = $this->get_wx_token();
        if(!$access_token){
            return false;
        }

    	if (!$param) {
	    	//先设置一个场景id,用来标识用户前端的身份
	        $scene_id = time().rand(100000,999999);
	        $scene_str = md5("biomassinfo.cn".$scene_id);
			
    	}

    	$scene_str = urlencode($param);
        
        //场景id给浏览器前端记录用户身份
        //setcookie("scene_id",$scene_str, time()+3600,  "/", "39.106.27.22");
        setcookie("scene_wx_id",$scene_str, time() + 3600,  "/", $_SERVER['HTTP_HOST']);
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
        //$data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id":'.$scene_id.'}}}';
		$data = '{"expire_seconds": 604800, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene_str.'"}}}';
		$tmpInfo = curl_request($url,$data);
		$ticket = json_decode($tmpInfo,true);
		// print_r($ticket);
		// exit();
        if(isset($ticket['errcode'])){
            $this->deleteCache('bio_wx_token');
            $access_token = $this->get_wx_token();
            $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
            //$data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id":'.$scene_id.'}}}';
            $data = '{"expire_seconds": 604800, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene_str.'"}}}';
            $tmpInfo = curl_request($url,$data);
            $ticket = json_decode($tmpInfo,true);
        }
		$ticket = $ticket["ticket"];
        if(!$ticket){
            var_dump($tmpInfo);exit;
        }
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        return $url;
	}

	/**
	 * 微信创建带参数的二维码图片
	 */
    public function get_wx_pic($type = 'ajax'){
        //先设置一个场景id,用来标识用户前端的身份
        $scene_id = time().rand(100000,999999);
        $scene_str = md5("biomassinfo.cn".$scene_id);
		$access_token = $this->get_wx_token();
        if(!$access_token){
            return;
        }
        //场景id给浏览器前端记录用户身份
        //setcookie("scene_id",$scene_str, time()+3600,  "/", "39.106.27.22");
        setcookie("scene_id",$scene_str, time() + 3600,  "/", $_SERVER['HTTP_HOST']);
		$url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
        //$data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id":'.$scene_id.'}}}';
		$data = '{"expire_seconds": 604800, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene_str.'"}}}';
		$tmpInfo = curl_request($url,$data);
		$ticket = json_decode($tmpInfo,true);
        if(isset($ticket['errcode'])){
            $this->deleteCache('bio_wx_token');
            $access_token = $this->get_wx_token();
            $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
            //$data = '{"expire_seconds": 1800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id":'.$scene_id.'}}}';
            $data = '{"expire_seconds": 604800, "action_name": "QR_STR_SCENE", "action_info": {"scene": {"scene_str": "'.$scene_str.'"}}}';
            $tmpInfo = curl_request($url,$data);
            $ticket = json_decode($tmpInfo,true);
        }
		$ticket = $ticket["ticket"];
        if(!$ticket){
            var_dump($tmpInfo);exit;
        }
		$url = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".urlencode($ticket);
        if($type == 'ajax'){
            echo json_encode(["url"=>$url]);
        }else{
            return $url;
        }
	}
	
	/**
	 * 微信公众账号配置验证路径
	 */
	public function weixin_valid(){
		/*$signature = $this->input->get("signature");
		$timestamp = $this->input->get("timestamp");
		$token = "DajiangdongquLangtaojin";
		$nonce = $this->input->get("nonce");
        $echostr = $this->input->get("echostr");
		$tmpArr = array($timestamp, $nonce, $token);
        sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		if( $signature == $tmpStr){
			echo $echostr;
		}else{
			echo $echostr;
		}*/
        if($this->input->get('code')){
            $this->wx_verify();
            return;
        }
        $this->response_msg();
	}

    /**
	 * 微信公众账号用户所有操作对话请求，服务器回应信息
     * $postStr = "<xml>\n
     * <ToUserName><![CDATA[gh_97bf4801c8e4]]></ToUserName>\n
     * <FromUserName><![CDATA[o6a_o0v5Lv1M3O8YB_Bhz57fIIQA]]></FromUserName>\n
     * <CreateTime>1511694338</CreateTime>\n
     * <MsgType><![CDATA[event]]></MsgType>\n
     * <Event><![CDATA[SCAN]]></Event>\n
     * <EventKey><![CDATA[29270add91e1e50b1c96b74c1020004e]]></EventKey>\n
     * <Ticket><![CDATA[gQFP8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyU0U3eElnRkxmRWkxZFlxemhxY1YAAgT8nxpaAwSAOgkA]]></Ticket>\n
     * <Encrypt><![CDATA[qahEZRhTWRrutxoCQyPDrzA4LVaHVl2/pastnCz/XHnwg3mMQyQ7rcbJZWvjPY/WcibVYSCbqwRj5tLbaABC0sKEYpPpaoHzrF2VhRuqKmPJuj
     * fp1A76dUn6nc7HVtciTf6ECboxIN5X9aEoQgyAQtzOTQBxEUysROgwN8ZE19fzaFov3XRu0uQvWEjunNrcgGq6bMgOEyfvrg4YIU1r9oC/
     * NOJJiux4/vIpxYAYYw7lC0DA21QTGtObtWlX51VzFrBpc4c48tkYf/eR3QfgQ6sftf9sCkeiqT8jB4xzAshMUlGNvx6mfab+oJ6PwgF+mjYQX6
     * J1Y6JHpOWysUBXR0iZBDPWOR+G1v6RYtOUs6WwoFquCKT9+GPVB]]>
     * </Encrypt>\n
     *      </xml>"
	 */
	private function response_msg(){
        //get post data, May be due to the different environments
        $postStr = isset($GLOBALS["HTTP_RAW_POST_DATA"])?$GLOBALS["HTTP_RAW_POST_DATA"]:null;
        $redis = $this->phpredis->redis;
        //extract post data
        if ($postStr){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $postObj = object_to_array($postObj);
            $fromUsername = $postObj["FromUserName"];
            $toUsername = $postObj["ToUserName"];
            //$keyword = trim($postObj->Content);
            $msgType = $postObj["MsgType"];
            $event = $postObj["Event"];
            $eventKey = $postObj["EventKey"];
            $time = time();
            //关注后发消息的事件
            /*if($event=="subscribe" && $msgType=="event"){
                $textTpl = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[%s]]></MsgType>
				<Content><![CDATA[%s]]></Content>
				<FuncFlag>0</FuncFlag>
				</xml>";
                $msgType = "text";
                $contentStr = "您好,欢迎关注中科院生物质产业信息资源平台!";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                //$wx_user = $this->register_wx_user($fromUsername);
                echo $resultStr;
            }*/
            if($eventKey || $event=="subscribe"){
                if (strpos(urldecode($postObj['EventKey']),'subscribe_text')!==false) {
                    if(true){
                        #############################
                        ## 二、订阅成功，仅仅发送提示信息
                        #############################
                        #
                        $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
                        $msgType = "text";
                        $eventKey = urldecode($postObj['EventKey']);
                        $eventArr = explode("|",$eventKey);
                        if($eventArr[0] == "subscribe_text" && (time() - $eventArr[3] < 3600)){
                            $subscribe_text = $eventArr[1];
                            $subscribe_type = $eventArr[2];
                            //$subscribe_arr = explode(",",$subscribe_text);
                            $user = $this->user_model->get_user_by_openid($fromUsername);
                            if(isset($user['id'])){
                                $uid = $user['id'];
                                $data['uid'] = $uid;
                                $data['subscribe'] = $subscribe_text;
                                $data['openid'] = $fromUsername;
                                $data['type'] = $subscribe_type;
                                $data['sub_time'] = time();
                                $this->user_model->add_user_subscribe($data);
                                if(!$subscribe_text){
                                    $contentStr = '已为您全部订阅:'.$subscribe_type;
                                }else{
                                    $contentStr = '已为您添加订阅关键词:"'.$subscribe_text.'".';
                                }

                                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                                echo $resultStr;
                            }else{
                                $contentStr = '请您注册生物质产品信息资源平台';
                                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                                echo $resultStr;
                            }

                        }
                    }else{

                    }
                }else{
                    $eventKey = str_replace("qrscene_","",$eventKey);
                    $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    <FuncFlag>0</FuncFlag>
                    </xml>";
                    $msgType = "text";
                    $contentStr = "您好, 欢迎关注中科院生物质产业信息资源平台!";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                    //获得注册微信用户的信息
                    $user = $this->register_wx_user($fromUsername);
                    //放入redis里面,前端根据 scene_id 调用
                    $key = "scene_str_".$eventKey;
                    $redis->set($key, serialize($user));
                    $redis->expire($key, 3600);
                } 
            }
        }
	}

    /**
     * 获得 access_token
     */
    private function get_wx_token(){
        //先从redis里取token
        $key = "bio_wx_token";
        $redis = $this->phpredis->redis;
        $redis_token = unserialize($redis->get($key));
        if($redis_token && isset($redis_token["access_token"]) && ($redis_token["expires_in"] - time() > 600) ){
            return $redis_token['access_token'];
        }
        //redis里token过期则重新请求
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s";
        $url = sprintf($url,WX_APP_ID,WX_SECRET);
        $ret = curl_request($url);
        $ret = json_decode($ret,true);
        if(isset($ret["access_token"])){
            $expire_time = time() + $ret["expires_in"];
            $arr = [
                "access_token" => $ret["access_token"],
                "expires_in" => $expire_time
            ];
            $redis->set($key, serialize($arr));
            $redis->expire($key, 7200);
            return $ret['access_token'];
        }else{
            return null;
        }
    }

    /**
     * 用户关注公众号,自动注册用户
     */
    private function register_wx_user($open_id){
        //检查是否有用户
        $exist_user = $this->user_model->get_user_by_openid($open_id);
        if($exist_user){
            return $exist_user;
        }else{
            //请求微信用户信息
            $wx_user = $this->get_wx_user($open_id);
            if(!$wx_user){
                return null;
            }
            //添加到数据库
            $new_user["openid"] = $wx_user["openid"];
            $new_user["unionid"] = $wx_user["unionid"];
            $new_user["nickname"] = $wx_user["nickname"];
            $new_user["sex"] = $wx_user["sex"];
            $new_user["city"] = $wx_user["city"];
            $new_user["province"] = $wx_user["province"];
            $new_user["country"] = $wx_user["country"];
            $new_user["headimgurl"] = $wx_user["headimgurl"];
            $new_user["create_time"] = time();
            $user = $this->user_model->add_user($new_user);
            return $user;
        }
    }

    /**
     * 用户关注公众号,自动注册用户
     * "subscribe": 1,
     * "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M",
     * "nickname": "Band",
     * "sex": 1,
     * "language": "zh_CN",
     * "city": "广州",
     * "province": "广东",
     * "country": "中国",
     * "headimgurl":  "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0",
     * "subscribe_time": 1382694957,
     * "unionid": " o6_bmasdasdsad6_2sgVt7hMZOPfL"
     * "remark": "",
     * "groupid": 0,
     * "tagid_list":[128,2]
     */
    private function get_wx_user($open_id){
        $access_token = $this->get_wx_token();
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN";
        $url = sprintf($url,$access_token,$open_id);
        $ret = curl_request($url);
        $ret = json_decode($ret,true);
        if(isset($ret["openid"])){
            if(!$ret["unionid"]){
                $ret["unionid"] = $ret["openid"];
            }
            return $ret;
        }
        return null;
    }
    /**
     * 订单审核通过微信通知
     */
    public function verify_notice(){
        $uid = $this->input->post('uid');
        $type = $this->input->post('type');
        $status = $this->input->post('status');
        $order_no = $this->input->post('order_no');
        $add_time = $this->input->post('add_time');
        if(!$uid || !$type || !$order_no || !$add_time){
            return;
        }
        if(!$status){
            $status = "已完成";
        }
        $user = $this->user_model->get_user_by_uid($uid);
        if(!$user || !$user["openid"]){
            return;
        }
        $data_scan['touser'] = $user["openid"];
        $data_scan['template_id'] = "lMW-o-us-JCI3_JVst3mnuMhhc3ZOp2iOOcXKHDmlM8";
        //标题
        $data_scan['data']["first"]['value'] = "您申请的业务进度已更新，申请信息如下：";
        $data_scan['data']["first"]['color'] = "#173177";
        //受理单位
        $data_scan['data']["keyword1"]['value'] = "中国科学院文献情报中心";
        //业务种类
        $data_scan['data']["keyword2"]['value'] = $type;
        //受理编号
        $data_scan['data']["keyword3"]['value'] = $order_no;
        //申请日期
        $data_scan['data']["keyword4"]['value'] = $add_time;
        //业务状态
        $data_scan['data']["keyword5"]['value'] = $status;

        $data_scan['data']["remark"]['value'] = "请登录生物质产业信息资源平台，http://www.biomassinfo.cn \"我的订单\"下载附件。";

        $access_token = $this->get_wx_token();
        $info_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $res = curl_request($info_url, json_encode($data_scan));
        $result = json_decode($res,true);
        //var_dump($result);
        if ($result['errcode']==0 && $result['errmsg']=="ok") {
            # 成功
            # 客户：$fromUsername;
            # 关键字str ：urldecode($postObj['EventKey']，需要筛选
        }else{
            #订阅失败，但是也可以假装成功。
            #关键字详见
        }
    }

	/**
	 * 向一个用户发送订阅信息
	 */
	private function wx_subscribe($openid,$type,$time,$remark,$url){
        #####################################
        ## 一、订阅成功，直接发送一次订阅信息列表
        #####################################

        #跳转地址
        //$url = 'http://'.$_SERVER['HTTP_HOST']."/sns/use_qrcode?param=";
        //{{first.DATA}}
        //科研方向：{{keyword1.DATA}}
        //成果入库时间：{{keyword2.DATA}}
        //{{remark.DATA}}

        $data_scan['touser'] = $openid;
        $data_scan['template_id'] = "MP4lQ9c6EHozRA_CO9rXL9XZLqBYzXeTAu_yza5iIvc";
        $data_scan['url'] = $url;
        $data_scan['data']["first"]['value'] = "您好，您有新的订阅信息!";
        $data_scan['data']["first"]['color'] = "#173177";
        $data_scan['data']["keyword1"]['value'] = $type;
        $data_scan['data']["keyword2"]['value'] = $time;
        $data_scan['data']["remark"]['value'] = $remark;

        $access_token = $this->get_wx_token();
        $info_url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $res = curl_request($info_url, json_encode($data_scan));
        $result = json_decode($res,true);

        if ($result['errcode']==0 && $result['errmsg']=="ok") {
            # 成功
            # 客户：$fromUsername;
            # 关键字str ：urldecode($postObj['EventKey']，需要筛选
        }else{
            #订阅失败，但是也可以假装成功。
            #关键字详见
        }
    }

    /**
     * 定时任务,检查用户订阅内容是否更新
     * 0 2 * * * curl http://www.biomassinfo.cn/sns/check_subscribe_update/专利技术
     * 0 3 * * * curl http://www.biomassinfo.cn/sns/check_subscribe_update/项目合作
     * 0 4 * * * curl http://www.biomassinfo.cn/sns/check_subscribe_update/专家咨询
     * 0 5 * * * curl http://www.biomassinfo.cn/sns/check_subscribe_update/产业信息
     * 0 6 * * * curl http://www.biomassinfo.cn/sns/check_subscribe_update/分析报告
     */
    function check_subscribe_update($type){
        $type = urldecode($type);
        if(!$type){
            $type = $this->input->get('type');
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        if($ip != '39.106.27.22' ){
            return;
        }
        $this->user_model->check_subscribe_update($type);
    }
    /**
     * 定时任务,给用户发送订阅
     * 10 10 * * 1 curl http://www.biomassinfo.cn/sns/send_subscribe/专利技术
     * 20 12 * * 2 curl http://www.biomassinfo.cn/sns/send_subscribe/项目合作
     * 30 14 * * 3 curl http://www.biomassinfo.cn/sns/send_subscribe/专家咨询
     * 40 16 * * 4 curl http://www.biomassinfo.cn/sns/send_subscribe/产业信息
     * 50 18 * * 5 curl http://www.biomassinfo.cn/sns/send_subscribe/分析报告
     */
    function send_subscribe($type){
        $type = urldecode($type);
        if(!$type){
            $type = $this->input->get('type');
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        if($ip != '39.106.27.22' ){
            return;
        }
        $subs = $this->user_model->get_subscribe_update($type);
        $time = date("Y-m-d");
        foreach($subs as $sub){
            $this->wx_subscribe($sub['openid'],$sub['type'],$time,$sub['remark'],$sub['url']);
        }
        echo 'success';
    }
    function test(){
        $ip = $_SERVER['REMOTE_ADDR'];
        if($ip != '39.106.27.22' ){
            return;
        }
        $type = '产业信息';
        $this->check_subscribe_update($type);
        $this->send_subscribe($type);
    }
    /**
     * 订阅管理
     */
    public function my_subscribe(){
        $user = $this->user;
        if(!$user){
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $this->wx_authorize($url);
            return;
        }
        $data['user'] = $user;
        $from_type = $this->input->get('type');
        $subscribe = $this->user_model->get_my_subscribe($user['id']);
        $data['subscribe'] = [];
        foreach($subscribe as $type => $word){
            if($type == $from_type){
                $data['active_type'] = $type;
                $data['subscribe'] = $word;
                break;
            }
        }
        if(!isset($data['active_type'])){
            foreach($subscribe as $type => $word){
                if(count($word)){
                    $data['active_type'] = $type;
                    $data['subscribe'] = $word;
                    break;
                }
            }
        }
        $this->load->view('web/mobile/subscribe_detail.html', $data);
    }
    /**
     * 推送历史
     */
    public function history_push(){
        $user = $this->user;
        if(!$user){
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $this->wx_authorize($url);
            return;
        }
        $data['user'] = $user;
        $page = $this->input->get('page');
        if(!$page){
            $page = 0;
        }
        $history = $this->user_model->get_history_push($user['id'],$page);
        if(is_ajax()){
            echo json_encode($history);
            return;
        }
        $data['list'] = $history;
        $this->load->view('web/mobile/history_push.html', $data);
    }
    /**
     * 微信网页授权
     */
    private function wx_authorize($from_url){
        $redirect_url = urlencode('http://www.biomassinfo.cn/sns/wx_verify');
        $from_url = urlencode($from_url);
        $authorize_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".WX_APP_ID."&redirect_uri=".$redirect_url."&response_type=code&scope=snsapi_userinfo&state=".$from_url."#wechat_redirect";
        header("Location:$authorize_url");
    }
    /**
     * 微信授权回调
     */
    public function wx_verify(){
        $code = $this->input->get("code");
        if(!$code){
            return;
        }
        $access_url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".WX_APP_ID."&secret=".WX_SECRET."&code=$code&grant_type=authorization_code";
        $ret = curl_request($access_url);
        $ret = json_decode($ret,true);
        $wx_user = null;
        if(isset($ret['access_token'])){
            $access_token = $ret['access_token'];
            $openid = $ret['openid'];
            //先检查一次有没有存在用户
            $wx_user = $this->user_model->get_user_by_openid($openid);
            if(!$wx_user){
                $usr_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
                $user_info = curl_request($usr_url);
                $user_info = json_decode($user_info,true);
                if(isset($user_info['openid'])){
                    //添加到数据库
                    $new_user["openid"] = $user_info["openid"];
                    $new_user["unionid"] = $user_info["unionid"];
                    $new_user["nickname"] = $user_info["nickname"];
                    $new_user["sex"] = $user_info["sex"];
                    $new_user["city"] = $user_info["city"];
                    $new_user["province"] = $user_info["province"];
                    $new_user["country"] = $user_info["country"];
                    $new_user["headimgurl"] = $user_info["headimgurl"];
                    $new_user["create_time"] = time();
                    $wx_user = $this->user_model->add_user($new_user);
                }
            }
            if($wx_user){
                //登录后处理session处理
                $this->session->set_userdata(['admin_info'=>$wx_user]);
                $login_key = $this->user_model->calculate_cookie_key($wx_user);
                setcookie("bio_passport",$login_key, time() + 3600 * 24,  "/", $_SERVER['HTTP_HOST']);
            }
        }
        if(!$wx_user){
            echo "登录失败";
        }else{
            $from_url = urldecode($this->input->get("state"));
            header("Location:$from_url");
        }
    }
}