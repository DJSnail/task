<?php
/************************************************************
** @Description: 用户管理
** @Author: haodaquan
** @Date:   2017-05-17
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-05-17
*************************************************************/

class Admin extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'id'     		=> '管理员ID',
                                'nickname'   	=> '昵称',
                                'username'   	=> '登录账号',
                                'role_id'       => '角色',
                                'province'   	=> '省份',
                                'city'   	    => '城市',
                                'create_time'   => '创建时间',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '用户管理';
    public $modelName   = 'admin_model';
    public $searchFile  = 'auth/admin_search.html';#搜索文件
    public $pageTips    = '用户列表';
    public $checkCol    = 0;

    public $role = [];

    public static $super_admin_id = 91;

    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/admin_model');
        $this->load->model('auth/role_model');
        $role = $this->role_model->getConditionData('id,name','status=0');
        foreach ($role as $key => $value) {
        	$this->role[$value['id']] = $value['name'];
        }
    }

    public  function index()
    {
    	$this->data['role'] = $this->role;
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
    	$_POST['status|=']=0;
        parent::query();
    }

    /**
     * [listDataFormat 对数据进行格式化]
     * @param  [type] $listData [description]
     * @return [type]           [description]
     */
    public function listDataFormat($listData)
    {
        $buttons = [ 'delete' => '删除','detail'   => '编辑'];
        $data['totalCount'] = $listData['totalCount'];
        $role = $this->role;
        foreach ($listData['items'] as $key => $value) {
        	if($value['role_id']==0){
        		$value['role_id'] = '普通账户';
        	}else if(isset($role[$value['role_id']])){
        		$value['role_id'] = $this->role[$value['role_id']];
        	}else if($value['role_id']==-1)
        	{
        		$value['role_id'] = '<font color="red">超级管理员</font>';
        	}else{
                $value['role_id'] = '未知';
            }
        	
        	$value['action'] = getButton($value['id'],$buttons);
        	$value['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
            $data['items'][$key] = $value;
        }
        return $data;
    }

    /**
     * [detail 修改]
     * @return [type] [description]
     */
    public  function detail()
    {
        $id = $this->input->get('id');

        $admin_info = $this->admin_model->getConditionData('*','id="'.$id.'"');

        if(!isset($admin_info[0]) || !$admin_info[0]) exit('参数错误');
        $data['admin_info'] = $admin_info[0];
        
       
        $data['pageTitle'] = '编辑用户';
        $data['pageTips']  = '仅支持修改权限，其他修改请到西有云品修改';
        $data['role'] = $this->role;

        $this->display('auth/admin_edit.html',$data);
    }

    /**
     * [save_admin 保存修改]
     * @return [type] [description]
     */
    public function save_admin()
    {
        $data = format_ajax_data($this->input->post('data'));
        $res = $this->admin_model->editData($data,'id="'.$data['id'].'"',1,1);
        $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'修改失败');
    }

    /**
     * [delete 逻辑删除]
     * @return [type] [ajax]
     */
    public function delete()
    {
    	$id = $this->input->post('id');
        if(!$id) $this->ajaxReturn($id,300,'数据错误');
        if($id==self::$super_admin_id || $id==1){
            $this->ajaxReturn($id,300,'禁止删除');
        }
    	
    	$data['status'] = 1;
    	$res = $this->role_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }
}