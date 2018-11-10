<?php
/************************************************************
** @Description: 成果转化
** @Author: haodaquan
** @Date:   2018-02-09 10:44:14
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-02-09 11:18:06
*************************************************************/

class Achievement extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'report_no'     => '编号',
                                'title'   	    => '技术成果名称',
                                'add_time'   	=> '创建时间',
                                'status'       	=> '状态',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '成果转化';
    public $modelName   = 'achievement_model';
    public $searchFile  = 'admin/achievement_search.html';#搜索文件
    public $pageTips    = '成果转化管理';
    public $checkCol    = 0;

    public $role = [];
    public $admin_info;
    public static $status_text = ["删除",'审核中',"驳回","通过"];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/achievement_model');
        // dump($this->amdin_model);
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
        parent::query();
    }

    /**
     * [listDataFormat 对数据进行格式化]
     * @param  [type] $listData [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
        $buttons = [ 'detail'   => '审核','delete' => '删除'];
        $data['totalCount'] = $listData['totalCount'];
        $role = $this->role;
        foreach ($listData['items'] as $key => $value) {
        	$value['status'] = self::$status_text[$value['status']];
        	$value['action'] = getButton($value['id'],$buttons);
        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data['items'][$key] = $value;
        }
        return $data;
    }

    /**
     * [detail 审核状态页面]
     * @return [type] [description]
     */
    public function detail()
    {
    	$data['pageTitle'] = '审核成果';
    	$data['pageTips']  = '审核详情';


    	$id = $this->input->get("id");
    	$report = $this->achievement_model->getConditionData('*','id='.(int)$id);
    
    	if (!isset($report[0]['id'])) {
    		echo "数据不完整，创建者不存在或者主要联系人不存在";
    		exit();
    	}
        $this->load->model('public/attachment_model');
    	//获取报告附件
    	$atta = [];
    	if($report[0]['attachment']){
    		
    		$atta = $this->attachment_model->getConditionData("*","id IN (".$report[0]['attachment'].")",1);
    	}

        foreach ($atta as &$vv) {
            if (isset($vv['id'])) {
                $vv['code'] = authcode($vv['id'],'ENCODE');
            }
        }

        $this->load->model('admin/connection_model');

        //用户名称
        $nickname = '无';
        $nicknames = $this->admin_model->getConditionData('nickname','id='.$report[0]['uid']);
        if (isset($nicknames[0]['nickname'])) {
        	$nickname = $nicknames[0]['nickname'];
        }

        //主要联系人
        $main_name = "无";
        $main_names = $this->connection_model->getConditionData("username",'id ='.$report[0]['main_conn']);
        if (isset($main_names[0]['username'])) {
        	$main_name = $main_names[0]['username'];
        }
        
    	//其他联系人处理
    	$other_name = '';
    	if ($report[0]['other_conn']) {
    		
    		$other = $this->connection_model->getConditionData("username",'id IN('.$report[0]['other_conn'].')',1);
	    	if($other){
		    	foreach ($other as $key => $value) {
		    		if (!isset($value['username'])) continue;
		    		$other_name .= $value['username']." ";
		    	}
	    	}
    	}
    	$attas[0] = [];
    	if ($report[0]['op_attachment']) {
    		$attas = $this->attachment_model->getConditionData("*","id IN (".(int)($report[0]['op_attachment']).")");
    	}
    	$data['nickname'] = $nickname;
    	$data['other_name'] = $other_name;//其他联系人
    	$data['main_name'] = $main_name;//其他联系人
    	$data['report']  = $report[0];
    	$data['atta']    = $atta;
    	$data['op_atta'] = $attas[0];//处理结果

    	$this->display('admin/achievement_detail.html',$data);
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
        $report = $this->achievement_model->getConditionData('*','id='.$data['id'])[0];
        $res = $this->achievement_model->editData($data,'id="'.$data['id'].'"');
        if($data['status'] == 3 && $report['status'] != 3){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $uid = $report['uid'];
            $post_data['uid'] = $uid;
            $post_data['type'] = '成果转化';
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
    	$res = $this->achievement_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }
}