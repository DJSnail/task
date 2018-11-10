<?php
/************************************************************
** @Description: 项目相关
** @Author: haodaquan
** @Date:   2017-12-04
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:04:14
*************************************************************/

class Monitor extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'key'           => '编号',
                                'title'   		=> '名称',
                                'institute'   	=> '机构',
                                'mdate'         => '日期',
                                'status'       	=> '状态',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '产业监测';
    public $modelName   = 'monitor_model';
    public $searchFile  = 'admin/monitor_search.html';#搜索文件
    public $pageTips    = '产业监测';
    public $checkCol    = 0;

    public $role = [];

    public $admin_info;
    public static $status_text = ['删除',"审核中","驳回","发布"];
    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/monitor_model');
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
        // $_POST['sort'] = 'is_top.desc';;
        parent::query();
    }

    /**
     * [listDataFormat 对数据进行格式化]
     * @param  [type] $listData [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
        $buttons0 = [  "noAuth2"=>'编辑', 'noAuth3' => '删除'];  //没权限
        $buttons1 = [  "tabEdit"=>'编辑', 'delete' => '删除']; //操作员
        $buttons2 = [  "noAuth1"=>'编辑', 'noAuth2' => '删除'];//审核员
        $buttons3 = [  "tabEdit"=>'编辑', 'delete' => '删除']; //超级管理员

        $data['totalCount'] = $listData['totalCount'];
        $role = $this->role;
        foreach ($listData['items'] as $key => $value) {
        	$value['status'] = self::$status_text[$value['status']];

            // if($value['is_top']==1){
            //     $value['title'] .= ' <font color="red">[顶]</font> ';
            // }
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
                $value['action'] = getButton($value['id'],$buttons3);
                //.'<button type="button" onclick="return changeStatusAction('.$value['id'].')" class="btn btn-xs btn-warning">置顶</button>';
            }else{
                $value['action'] = getButton($value['id'],$buttons0);
            }

        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
            $data['items'][$key] = $value;
        }
        return $data;
    }

     /**
     * [tab_edit 修改]
     */
    public function tab_edit()
    {
        $data['pageTitle'] = '编辑产业监测';
    	$data['pageTips']  = '';
    	$id = $this->input->get("id");
    	$project = $this->monitor_model->getConditionData('*','id='.(int)$id);
        //var_dump($project[0]);exit;
    	if (!isset($project[0]['id'])) {
    		echo "数据不存在";
    		exit();
    	}
    	$data['project'] = $project[0];
    	$this->display('admin/monitor_edit.html',$data);
    }


    /**
     * [save_admin 保存修改]
     * @return [type] [description]
     */
    public function save()
    {
        $data = format_ajax_data($this->input->post('form_data'));

        foreach ($data as $key => $value) {
        	if ($key == 'price') {
        		if(!is_numeric($value)){
        			$this->ajaxReturn('',300,'请填写正确的价格');
        		}
        	}
        	if (!$value) {
        		$this->ajaxReturn('',300,'请填写完整数据');
        	}
        }

        $data['status'] = 3;

        if(isset($data['id']) && $data['id']>0){

	        $data['edit_time']   = time();
	        $res = $this->monitor_model->editData($data,'id="'.$data['id'].'"');
        	$res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'保存失败');
        }

        //查找标题是否存在
        $exist = $this->monitor_model->getConditionData("id,name","name='".addslashes($data['name'])."'");
        if(isset($exist[0]['name'])){
        	$this->ajaxReturn('',300,'项目名称重复');
        }
        $data['add_time'] = $data['edit_time'] = time();
        $res = $this->monitor_model->addData($data,1,1);
        $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'保存失败');
        
    }

    // /**
    //  * [changeStatus 修改状态]
    //  * @return [type] [description]
    //  */
    // public function changeStatus()
    // {
    //     $id = $this->input->post('id');
        
    //     if(!$id){
    //         $this->ajaxReturn("",300,'数据不完整');
    //     }
    //     //首先全部不置顶
    //     $this->project_model->editData(['is_top'=>0],'id>0');

    //     $data['id'] = $id;
    //     $data['is_top'] = 1;
    //     $data['edit_time']   = time();
    //     $res = $this->project_model->editData($data,'id="'.$id.'"');
    //     $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'置顶失败');
    // }

    /**
     * [delete 逻辑删除]
     * @return [type] [ajax]
     */
    public function delete()
    {
    	$id = $this->input->post('id');
        if(!$id) $this->ajaxReturn($id,300,'数据错误');
    	$data['status'] = 0;
    	$res = $this->monitor_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }



}