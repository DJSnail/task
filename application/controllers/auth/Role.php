<?php
/************************************************************
** @Description: 角色管理
** @Author: haodaquan
** @Date:   2017-05-17
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-05-17
*************************************************************/

class Role extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'id'     		=> '角色ID',
                                'name'   		=> '角色名称',
                                'add_time'     	=> '添加时间',
                                'edit_time'   	=> '修改时间',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '角色管理';
    public $modelName   = 'role_model';
    public $searchFile  = 'auth/role_search.html';#搜索文件
    public $pageTips    = '角色列表';
    public $checkCol    = 0;

    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/role_model');
        $this->load->model('auth/role_auth_model');
        // $this->load->helper('tmall');
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
        foreach ($listData['items'] as $key => $value) {
        	$value['action'] = getButton($value['id'],$buttons);
        	$value['add_time'] = date('Y-m-d H:i:s',$value['add_time']);
        	$value['edit_time'] = date('Y-m-d H:i:s',$value['edit_time']);
            $data['items'][$key] = $value;
        }
        return $data;
    }

    /**
     * [delete 逻辑删除]
     * @return [type] [ajax]
     */
    public function delete()
    {
    	$id = $this->input->post('id');
    	if(!$id) $this->ajaxReturn($id,300,'数据错误');
    	$data['status'] = 1;
    	$res = $this->role_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }

    /**
     * [add 角色管理界面]
     */
    public function add_role()
    {
    	$data['pageTitle'] = '增加角色';
    	$data['pageTips']  = '角色管理';
        $this->display_ztree('auth/role_add.html',$data);
    }

    /**
     * [detail 修改角色]
     * @return [type] [description]
     */
    public function detail()
    {
    	$id = $this->input->get('id');
    	if(!$id) exit('参数错误');
    	$res = $this->role_model->getConditionData('*','status=0 and id="'.$id.'"');
    	$data['role'] = isset($res[0]) ? $res[0] : ['name'=>'错误','id'=>0]; 

    	$auth_nodes = $this->role_auth_model->getConditionData('auth_id','role_id="'.$res[0]['id'].'"');
    	$auth = [];
    	if(!isset($auth_nodes[0])){
    		$auth = [];
    	}else
    	{
    		foreach ($auth_nodes as $key => $value) {
    			$auth[] = $value['auth_id'];
	    	}
    	}

    	$data['auth'] = json_encode($auth);
    	$data['pageTitle'] = '角色修改';
    	$data['pageTips']  = '角色修改';
        $this->display_ztree('auth/role_edit.html',$data);

    }

    /**
     * [save_role 保存角色]
     * @return [type] [description]
     */
    public function save_role(){
    	$role_name = htmlentities($this->input->post('name'));
    	$nodes = $this->input->post('nodes_data');

    	if(!$role_name) $this->ajaxReturn($role_name,300,'缺少角色名称');
    	$res = $this->role_model->getConditionData('*',' name="'.$role_name.'"');
    	if($res) $this->ajaxReturn($role_name,300,'角色名称已经存在');

    	$data['name'] = $role_name;
    	$id = $this->role_model->addData($data);
    	if(!$id) $this->ajaxReturn($role_name,300,'存储错误，请重新提交');

    	#存储对应关系
    	if (!$nodes) $this->ajaxReturn();
    	
    	foreach ($nodes as $key => $value) {
    		$nodes_data = [];
    		$nodes_data['role_id'] = $id;
    		$nodes_data['auth_id'] = $value['id'];
    		$this->role_auth_model->addData($nodes_data,0);
    	}

    	$this->ajaxReturn();
    }

    /**
     * [save_edit_role 保存编辑角色]
     * @return [type] [description]
     */
    public function save_edit_role(){
    	$role_name = htmlentities($this->input->post('name'));
    	$id = $this->input->post('id');

    	$nodes = $this->input->post('nodes_data');

    	if(!$role_name) $this->ajaxReturn($role_name,300,'缺少角色名称');
    	

    	$data['name'] = $role_name;
    	$res = $this->role_model->editData($data,'id="'.$id.'"');

    	if(!$res) $this->ajaxReturn($role_name,300,'存储错误，请重新提交');

    	#删除所有关系
    	$this->role_auth_model->delData(['role_id'=>$id]);

    	#存储对应关系
    	if (!$nodes) $this->ajaxReturn();
    	
    	foreach ($nodes as $key => $value) {
    		$nodes_data = [];
    		$nodes_data['role_id'] = $id;
    		$nodes_data['auth_id'] = $value['id'];
    		$this->role_auth_model->addData($nodes_data,0);
    	}

    	$this->ajaxReturn();
    }

}