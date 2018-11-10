<?php
/************************************************************
** @Description: 报告相关
** @Author: haodaquan
** @Date:   2017-12-04
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-03-05 17:01:17
*************************************************************/

class Report extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'report_no'     => '编号',
                                'title'   		=> '报告名称',
                                'category'   	=> '主题分类',
                                'author'   		=> '作者',
                                'price'   		=> '价格',
                                'add_time'   	=> '创建时间',
                                'status'       	=> '状态',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '报告管理';
    public $modelName   = 'report_model';
    public $searchFile  = 'admin/report_search.html';#搜索文件
    public $pageTips    = '报告管理';
    public $checkCol    = 0;

    public $role = [];

    public $admin_info;
    public static $status_text = ["删除",'审核中',"驳回","通过"];
    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/report_model');
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
        $_POST['sort'] = 'is_top.desc';
        parent::query();
    }

    /**
     * [listDataFormat 对数据进行格式化]
     * @param  [type] $listData [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
        $buttons0 = [ 'noAuth1'   => '审核', "noAuth2"=>'编辑', 'noAuth3' => '删除'];  //没权限
        $buttons1 = [ 'noAuth'    => '审核', "tabEdit"=>'编辑', 'delete' => '删除']; //操作员
        $buttons2 = [ 'detail'    => '审核', "noAuth1"=>'编辑', 'noAuth2' => '删除'];//审核员
        $buttons3 = [ 'detail'    => '审核', "tabEdit"=>'编辑', 'delete' => '删除']; //超级管理员

        $data['totalCount'] = $listData['totalCount'];
        $role = $this->role;
        foreach ($listData['items'] as $key => $value) {
        	$value['status'] = self::$status_text[$value['status']];

            if($value['is_top']==1){
                $value['title'] .= ' <font color="red">[顶]</font> ';
            }
            //如果是操作员
            if ($this->admin_info['role_id']==3) {
                //并且是自己发布的
                if($value['uid']==$this->admin_info['id']){
                    $value['action'] = getButton($value['id'],$buttons1);
                }else{
                    $value['action'] = getButton($value['id'],$buttons0);
                }
            }else if($this->admin_info['role_id']==2){
                //如果是审核员
                $value['action'] = getButton($value['id'],$buttons2);
            }else if($this->admin_info['role_id']==1 || $this->admin_info['role_id']==-1){
                //如果是超级管理员或者系统管理员
                $value['action'] = getButton($value['id'],$buttons3).'<button type="button" onclick="return changeStatusAction('.$value['id'].')" class="btn btn-xs btn-warning">置顶</button>';
            }else{
                $value['action'] = getButton($value['id'],$buttons0);
            }

        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data['items'][$key] = $value;
        }
        return $data;
    }

    /**
     * [add description]
     */
    public function add()
    {
    	$data['pageTitle'] = '新增报告';
    	$data['category'] = get_category_array();
    	$this->display('admin/report_add.html',$data);
    }

     /**
     * [tab_edit 修改]
     */
    public function tab_edit()
    {
    	$data['pageTitle'] = '编辑报告';
    	$data['pageTips']  = '注意：编辑完成后状态为审核中';
    	$data['category'] = get_category_array();
    	$id = $this->input->get("id");
    	$report = $this->report_model->getReportInfo((int)$id);
    	if (!isset($report[0]['id'])) {
    		echo "数据不存在";
    		exit();
    	}

        if (isset($report[0]['pdf_id'])) {
            $report[0]['pdf_id'] = authcode($report[0]['pdf_id'],'ENCODE');
        }
    	$data['report'] = $report[0];
    	$this->display('admin/report_edit.html',$data);
    }

    /**
     * [detail 查看&修改状态]
     * @return [type] [description]
     */
    public function detail()
    {
    	$data['pageTitle'] = '审核报告';
    	$data['pageTips']  = '报告状态:';

    	$id = $this->input->get("id");
    	$report = $this->report_model->getReportInfo((int)$id);
    	if (!isset($report[0]['id'])) {
    		echo "数据不存在";
    		exit();
    	}
        if (isset($report[0]['pdf_id'])) {
            $report[0]['pdf_id'] = authcode($report[0]['pdf_id'],'ENCODE');
        }
    	$data['report'] = $report[0];
    	$this->display('admin/report_detail.html',$data);
    }


    /**
     * [save_admin 保存修改]
     * @return [type] [description]
     */
    public function save()
    {
        $data = format_ajax_data($this->input->post('form_data'));

        $data['keyword'] 	 = implode(",",splitString($data['keyword']));
        $data['author']  	 = implode(",",splitString($data['author']));
        $data['company']  	 = trim($data['company']);
        $data['pdf_id']  	 = (int)$data['upload_file_id'];

        if ($data['public_time']) {
            $data['public_time'] = strtotime($data['public_time']);
        }else{
            $this->ajaxReturn('',300,'请选择发布时间');
        }
        
        unset($data['upload_file_id']);
        $data['uid'] = $this->admin_info['id'];
        $data['status'] = 1;
        if(isset($data['id']) && $data['id']>0){
	        $data['edit_time']   = time();
	        $res = $this->report_model->editData($data,'id="'.$data['id'].'"');
        	$res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'保存失败');
        }

        //新增逻辑
        if(!$data['category']) {
        	$this->ajaxReturn('',300,'请选择主题分类');
        }
        $data['category_root'] = get_category_root($data['category']);
        $data['report_no'] = reportno();
        //查找标题是否存在
        $exist = $this->report_model->getConditionData("id,title","title='".addslashes($data['title'])."'");
        if(isset($exist[0]['title'])){
        	$this->ajaxReturn('',300,'报告名称重复');
        }
        $data['add_time'] = $data['edit_time'] = time();
        $res = $this->report_model->addData($data);
        $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'保存失败');
        
    }

    /**
     * [change_verify 审核]
     * @return [type] [description]
     */
    public function change_verify()
    {
    	$data = format_ajax_data($this->input->post('form_data'));
    	if(!isset($data['id']) || !$data['id']){
    		$this->ajaxReturn("",300,'数据不完整');
    	}
    	// if($data['status']==3){
    	// 	$data['public_time']   = time();
    	// }else{
    	// 	$data['public_time']   = 0;
    	// }
    	$data['verify_uid'] = $this->admin_info['id'];
    	$data['edit_time']  = time();
        $report = $this->report_model->getReportInfo($data['id'])[0];
        $res = $this->report_model->editData($data,'id="'.$data['id'].'"');
        if($data['status'] == 3 && $report['status'] != 3){
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/sns/verify_notice';
            $uid = $report['uid'];
            $post_data['uid'] = $uid;
            $post_data['type'] = '购买报告';
            $post_data['order_no'] = $report['report_no'];
            $post_data['add_time'] = date("Y-m-d",$report['add_time']);
            curl_request($url,$post_data);

        }
        //return;
    	$res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'审核失败');
    }

    /**
     * [changeStatus 修改状态]
     * @return [type] [description]
     */
    public function changeStatus()
    {
        $id = $this->input->post('id');
        
        if(!$id){
            $this->ajaxReturn("",300,'数据不完整');
        }
        //首先全部不置顶
        $this->report_model->editData(['is_top'=>0],'id>0');

        $data['id'] = $id;
        $data['is_top'] = 1;
        $data['edit_time']   = time();
        $res = $this->report_model->editData($data,'id="'.$id.'"');
        $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'置顶失败');
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
    	$res = $this->report_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }



}