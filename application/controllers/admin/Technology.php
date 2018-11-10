<?php
/************************************************************
** @Description: 科技查新
** @Author: haodaquan
** @Date:   2018-01-25 15:18:14
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-29 10:12:04
*************************************************************/

class Technology extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'proj_name'     		=> '项目名称',
                                'check_range'   		=> '查询范围',
                                'check_goal'   			=> '查询目标',
                                'subject_category'   	=> '学科分类',
                                'industry_category'   	=> '工业分类',
                                'add_time'   			=> '申请时间',
                                'status'       			=> '状态',
                                'action'        		=> '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '科技查新';
    public $modelName   = 'technology_model';
    public $searchFile  = 'admin/technology_search.html';#搜索文件
    public $pageTips    = '科技查新';
    public $checkCol    = 0;

    public $role = [];

    public $admin_info;
    public static $status_text = ["删除",'审核中',"驳回","通过"];
    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/technology_model');
        $this->admin_info = $this->admin_model->check_admin();
        
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
        // $_POST['sort'] = 'is_top.desc';
        parent::query();
    }

    /**
     * [listDataFormat 对数据进行格式化]
     * @param  [type] $listData [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
    	$search_aim 		= get_search_aim();
        $search_range		= get_search_range();
        $subject_category 	= get_subject_category();
        $industry_category 	= get_industry_category();

        $buttons = [ 'detail'    => '审核', 'delete' => '删除']; //超级管理员

        $data['totalCount'] = $listData['totalCount'];
     
        foreach ($listData['items'] as $key => $value) {
        	$value['status'] = self::$status_text[$value['status']];
        	$value['check_goal'] = $search_aim[$value['check_goal']];
        	$value['check_range'] = $search_range[$value['check_range']];
        	$value['subject_category'] = $subject_category[$value['subject_category']];
        	$value['industry_category'] = $industry_category[$value['industry_category']];
        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        	$value['action'] = getButton($value['id'],$buttons);
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
    	$data['pageTitle'] = '科技查新';
    	
    	$id = $this->input->get("id");
    	$technology = $this->technology_model->getConditionData("*","id=".(int)$id);
    
    	if (!isset($technology[0]['id'])) {
    		echo "申请不存在";
    		exit();
    	}

    	$search_aim 		= get_search_aim();
        $search_range		= get_search_range();
        $subject_category 	= get_subject_category();
        $industry_category 	= get_industry_category();

        $technology[0]['check_goal'] = $search_aim[$technology[0]['check_goal']];
        $technology[0]['check_range'] = $search_aim[$technology[0]['check_range']];
        $technology[0]['subject_category'] = $search_aim[$technology[0]['subject_category']];
        $technology[0]['industry_category'] = $search_aim[$technology[0]['industry_category']];


    	$data['pageTips']  = $technology[0]['proj_name'];

    	//主要联系人
    	$this->load->model("admin/connection_model");
    	$main = $this->connection_model->getConditionData("username",'id IN('.$technology[0]['main_conn'].')');

        $this->load->model('public/attachment_model');
    	//附件
        // print_r($technology[0]['attachment_ids']);

    	$atta = [];
    	if($technology[0]['attachment_ids']){
    		$atta = $this->attachment_model->getConditionData("*","id IN (".$technology[0]['attachment_ids'].")");
    	}

        // print_r($atta);
        // exit();
        foreach ($atta as &$vv) {
            if (isset($vv['id'])) {
                $vv['code'] = authcode($vv['id'],'ENCODE');
            }
        }
    	//其他联系人处理
    	$other_name = '';
    	if ($technology[0]['other_conn']) {
    		$this->load->model('admin/connection_model');
    		$other = $this->connection_model->getConditionData("username",'id IN('.$technology[0]['other_conn'].')');
	    	if($other){
		    	foreach ($other as $key => $value) {
		    		if (!isset($value['username'])) continue;
		    		$other_name .= $value['username']." ";
		    	}
	    	}
    	}

    	$attas[0] = [];
    	if ($technology[0]['op_attachment']) {
    		$attas = $this->attachment_model->getConditionData("*","id IN (".(int)($technology[0]['op_attachment']).")");
    	}
    	$data['other_name'] = $other_name;//其他联系人
    	$data['technology']  = $technology[0];
    	$data['atta']    = $atta;
    	$data['op_atta'] = $attas[0];//处理结果
    	$data['main_conn'] = $main[0];//主要联系人
    	$this->display('admin/technology_detail.html',$data);
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
        $report = $this->technology_model->getConditionData("*","id=".$data['id'])[0];
        $res = $this->technology_model->editData($data,'id="'.$data['id'].'"');
        if($data['status'] == 3 && $report['status'] != 3){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $uid = $report['uid'];
            $post_data['uid'] = $uid;
            $post_data['type'] = '科技查新';
            $post_data['order_no'] = $report['report_no'];
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
    	$res = $this->technology_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }

}