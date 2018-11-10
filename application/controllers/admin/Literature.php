<?php
/************************************************************
** @Description: 文献信息
** @Author: haodaquan
** @Date:   2017-12-04
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:04:14
*************************************************************/

class Literature extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'doc_no'     => '编号',
                                'doc_name'   		=> '书刊名称',
                                'doc_title'   		=> '文章标题',
                                'add_time'   	=> '创建时间',
                                'status'       	=> '状态',
                                'relay_status'      => '转发状态',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '文献信息';
    public $modelName   = 'literature_model';
    public $searchFile  = 'admin/literature_search.html';#搜索文件
    public $pageTips    = '文献信息管理';
    public $checkCol    = 0;

    public $role = [];
    public $admin_info;
    public static $status_text = ["删除",'审核中',"驳回","通过"];
    public static $relay_status_text = ['未转发','已转发'];
    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/literature_model');
        // dump($this->amdin_model);
        // $this->admin_info = $this->admin_model->check_admin();

        
    }

    public  function index()
    {
    	parent::index();
    }

    /**
     * [query 查询配置 这里继承父类方法，也可以这里配置查询条件]
     * @Author haodaquan
     * @Date   2016-08-07
     * @return [type]     [description]
     */
    public function query()
    {
    	$_POST['status|>']=0;
        parent::query();
    }

    /**
     * [listDataFormat 对数据进行格式化]
     * @param  [type] $listData [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
        $buttons = [ 'detail'   => '查看','delete' => '删除'];
        $data['totalCount'] = $listData['totalCount'];
        $role = $this->role;
        foreach ($listData['items'] as $key => $value) {
        	$value['status'] = self::$status_text[$value['status']];
            $value['relay_status'] = self::$relay_status_text[$value['relay_status']];
        	$value['action'] = getButton($value['id'],$buttons).'<button type="button" onclick="return relayAction('.$value['id'].')" class="btn btn-xs btn-warning">转发</button>';;
        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data['items'][$key] = $value;
        }
        return $data;
    }

    /**
     * [detail 查看&修改状态]
     * @return [type] [description]
     */
    public function detail()
    {
    	$data['pageTitle'] = '审核文献';
    	$data['pageTips']  = '文献详情';


    	$id = $this->input->get("id");
    	$literature = $this->literature_model->getLiteratureInfo((int)$id);
    
    	if (!isset($literature[0]['id'])) {
    		echo "数据不完整，创建者不存在或者主要联系人不存在";
    		exit();
    	}
        $this->load->model('public/attachment_model');

    	//其他联系人处理
    	$other_name = '';
    	if ($literature[0]['other_conn']) {
    		$this->load->model('admin/connection_model');
    		$other = $this->connection_model->getConditionData("username",'id IN('.$literature[0]['other_conn'].')',1);
	    	if($other){
		    	foreach ($other as $key => $value) {
		    		if (!isset($value['username'])) continue;
		    		$other_name .= $value['username']." ";
		    	}
	    	}
    	}

    	$attas[0] = [];
    	if ($literature[0]['op_attachment']) {
    	    $attas = $this->attachment_model->getConditionData("*","id IN (".(int)($literature[0]['op_attachment']).")");
    	}

    	$data['other_name'] = $other_name;//其他联系人
    	$data['literature']  = $literature[0];
    	//$data['atta']    = $atta;
    	$data['op_atta'] = $attas[0];//处理结果    	
    	$this->display('admin/literature_detail.html',$data);
    }

    /**
     * [changeStatus 审核]
     * @return [type] [description]
     */
    public function changeStatus()
    {
    	$data = format_ajax_data($this->input->post('form_data'));
    	if(!isset($data['id']) || !$data['id']){
    		$this->ajaxReturn("",300,'数据不完整');
    	}
    	//组织数据
    	$data['op_uid'] = $this->admin_info['id'];
    	$data['edit_time']   = time();
    	$data['op_attachment'] = (int)$data['upload_file_id'];
    	unset($data['upload_file_id']);
        $report = $this->literature_model->getLiteratureInfo($data['id'])[0];
        $res = $this->literature_model->editData($data,'id="'.$data['id'].'"');
        if($data['status'] == 3 && $report['status'] != 3){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $uid = $report['uid'];
            $post_data['uid'] = $uid;
            $post_data['type'] = '文献传递';
            $post_data['order_no'] = $report['doc_no'];
            $post_data['add_time'] = date("Y-m-d",$report['add_time']);
            curl_request($url,$post_data);

        }
    	$res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'审核失败');
    }



    /**
     * [delete 逻辑删除]
     * @return [type] [ajax]
     */
    public function delete()
    {
    	$id = $this->input->post('id');
        if(!$id) $this->ajaxReturn($id,300,'数据错误');
    	$data['status'] = 0;
    	$res = $this->literature_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }

    /**
     * [relay 将订单信息直接转发至NSTL平台]
     * @return [type] [ajax]
     */
    public function relay(){
        $id = $this->input->post('id');
        if(!$id) $this->ajaxReturn($id,300,'数据错误');
        $message = $this->literature_model->relay($id);
        ($message!=false) ? $this->ajaxReturn([],200,$message) : $this->ajaxReturn([],300,'操作失败');
    }

    public function get_doc(){
        $OrderID = 'I1806111855';
        $AgencyOrderID = '201801291009087351';
        $message = $this->literature_model->get_doc($OrderID, $AgencyOrderID);
        ($message!=false) ? $this->ajaxReturn([],200,$message) : $this->ajaxReturn([],300,'操作失败');
    }

}