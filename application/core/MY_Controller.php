<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
defined('APP_KEY')     OR define('APP_KEY', '1001');#接口调用KEY
defined('APP_SECRET')  OR define('APP_SECRET', 'ed7f8b373db9b733dc0dee62a6d184e8');#接口调用密码
class MY_Controller extends CI_Controller
{

	public $checkCol  = 1;#显示多选框 1-显示，0-不显示
    public $indexCol  = 0;#不显示多选框 1-显示，0-不显示
    public $searchFile = false;
    public $pageTips   = '';
    public $pageTitle  = '正在开发中……';
    static $current_vision = "2017083195581615";

	public function __construct()
	{
		parent::__construct();

        $this->user = $this->user_model->get_user();
        $this->load->model("public/admin_model");
        switch (ENVIRONMENT)
        {
            case 'development':
                #根据开发环境修改
                define("BASE_URL", 'chanxiyou.com');
                define("ERP_URL", 'xiyouerp.com');
                break;
            case 'production':
                define("BASE_URL", 'ch.shoplinq.cn');
                define("ERPURL", 'erp.shoplinq.cn');
                break;
            default:
                define("BASEURL", 'test.tmall.xiyou.com');
                define("ERPURL", 'xiyouerp.com');
        }
	}

    function setToCache($key, $value, $expire = 0) {
        $redis = $this->phpredis->redis;
        $redis->set($key, serialize($value));
        $redis->expire($key, $expire);
    }

    function getFromCache($key) {
        $redis = $this->phpredis->redis;
        $cache = $redis->get($key);
        return $cache ? unserialize($cache) : [];
    }

    function deleteCache($key) {
        $redis = $this->phpredis->redis;
        return $redis->delete($key);
    }

    /**
     * 未注册用户强制跳转到登录页面
     */
    public function go_login(){
        $this_url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $login_url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/login?continue_url='.urlencode($this_url);
        header ( 'Location:' . $login_url );
    }

	/**
	* [ajaxReturn 返回ajax数据]
	* @Date   2016-06-06
	* @param  array      $data     [返回数据]
	* @param  integer    $status   [状态码]
	* @param  string     $messages [提示信息]
	* @return [type]               [description]
	*/
	public function ajaxReturn($data=[],$status=200,$messages="操作成功")
	{
		echo  json_encode(
			['status'=>$status,'message'=>$messages,'data'=>$data]
		);
		exit();
	}

	/**
	 * [display 加载模板]
	 * @param  string $content_file [加载主要部分]
	 * @param  array $data [页面数据]
	 * @return [type]              [description]
	 */
	public function display($content_file='',$data=[])
	{
        $public['version'] = $data['version'] = time();
		$this->load->view('public/header.html',$public);
        $this->load->view($content_file,$data);
        $this->load->view('public/footer.html',$public);
	}

    /**
     * [display_ztree 显示权限树]
     * @Author haodaquan
     * @Date   2016-04-04
     * @return [type]     [description]
     */
    protected function display_ztree($content_file='',$data=[])
    {
        $this->data['version'] = $public['version'] = time();
        $this->load->view('public/header.html',$public);
        $this->load->view($content_file,$data);
        $this->load->view('zTree/ztree_edit.html',$this->data);
        $this->load->view('public/footer.html',$public);
    }

    /**
     * [write_log 日志]
     * @param  string $file_name [文件名称]
     * @param  string $content   [内容]
     * @return [type]            [description]
     */
    public  function write_log($file_name="",$content="\n\r",$model='monitor')
    {
        $file_path  = LOG_PATH.$model.'/log/'.date('Y-m-d',time());
        make_dir($file_path);
        $file_path .= '/'.$file_name;
        $content   = '【'.date('Y-m-d H:i:s',time()).'】 '.$content.PHP_EOL;
        return file_put_contents($file_path, $content,FILE_APPEND);
    }


	################################ mmGrid start ######################
	

	 /**
     * [index 默认首页]
     * @Author haodaquan
     * @Date   2016-04-05
     * @return [type]     [description]
     */
    public function index()
    {
        $this->getPageTitle();
        $this->showSearch();
        $this->getShowFields();
        $this->getColumnsWidth();
        $this->showCheckCol();
        $this->showIndexCol();
        $this->getQueryUrl();
        $this->_displayMmgrids();
    }
    
    protected function getPageTitle($id=1)
    {
    	$this->data['pageTitle'] = $this->pageTitle ? $this->pageTitle : '标题未命名';
        $this->data['pageTips']  = $this->pageTips ? $this->pageTips : '';
    }

    /**
     * [_displayMmgrids 显示列表模板]
     * @Author haodaquan
     * @Date   2016-04-04
     * @return [type]     [description]
     */
    protected function _displayMmgrids()
    {
        $this->data['version'] = $public['version'] = time();
    	$this->load->view('public/header.html',$public);
        $this->load->view('mmGrid/mmgrid.html',$this->data);
        $this->load->view('public/footer.html',$public);
    }

    /**
     * [showSearch 是否增加自定义搜索条件，true or false]
     * @Author haodaquan
     * @Date   2016-04-05
     * @return [type]     [description]
     */
    protected function showSearch()
    {
       $this->data['showSearch'] = $this->searchFile ? $this->searchFile : false;
    }



    /**
     * [getShowFields 获取列表头]
     * @Author haodaquan
     * @Date   2016-04-05
     * @return [type]     [description]
     */
    protected function getShowFields()
    {
        if (!empty($this->showFields)) {
            $this->data['showFields'] = json_encode($this->showFields);
        }
    }

    /**
     * [getColumnsWidth 获取列宽度，用于手工设定]
     * @Author haodaquan
     * @Date   2016-04-05
     * @return [type]     [description]
     */
    protected function getColumnsWidth()
    {
        $columnsWidth = !empty($this->columnsWidth) ? $this->columnsWidth : array('');
        $this->data['columnsWidth'] = json_encode($columnsWidth);      
    }

    /**
     * [getQueryUrl 获取列表查询地址]
     * @Author haodaquan
     * @Date   2016-04-06
     * @return [type]     [description]
     */
    protected function getQueryUrl()
    {
        $queryUrl = '/'.$this->router->uri->segments[1].
        			"/".$this->router->uri->segments[2].'/query';
        if (!empty($this->queryUrl))  $queryUrl = $this->queryUrl;
        $this->data['queryUrl'] = $queryUrl;
    }

    /**
     * [showIndexCol 是否显示表前索引]
     * @return [type] [description]
     */
    public function showIndexCol()
    {
    	$this->data['showIndexCol'] = $this->indexCol;
    }

    /**
     * [showCheckCol 是否显示全选框 ]
     * @return [type] [description]
     */
    public function showCheckCol()
    {
    	$this->data['showCheckCol'] = $this->checkCol;
    }


    /**
     * [query 查询列表数据]
     * @Author haodaquan
     * @Date   2016-04-07
     * @return [type]     [description]
     */
    public function query()
    {
    	$model = $this->modelName;
        $data  = $this->$model->queryList($_POST);
      	$listData  =  $this->listDataFormat($data['data']);
        if($data['status']==200) echo json_encode($listData);
    }

    /**
     * [listDataFormat 列表数据格式化,子类一般需要重写]
     * @Author haodaquan
     * @Date   2016-04-07
     * @param  array      $data [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
            if(empty($listData)) return [];
        return $listData;
    }

    ################################ mmGrid end ######################
    
    /**
     * [get_user 获取登录信息]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function get_user()
    {
        $user_info = $this->user_model->check_user();
        if(!isset($user_info['id'])){
            return ['id'=>0,'message'=>'无权操作'];
        }else
        {
            return $user_info;
        }
    }


}
