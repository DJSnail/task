<?php
/************************************************************
** @Description: 权限管理 先做菜单级别
** @Author: haodaquan
** @Date:   2017-05-11
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-25 15:13:06
*************************************************************/

class Auth extends MY_Controller
{

	public $data = [];

    // public static $fields = ['id','name','module','controller','action','pid'];
    public static $fields = ['id','name','menu_url','pid'];
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model');
        // $this->load->helper('');
    }

	/**
     * [index 权限节点]
     * @Date   2016-10-09
     * @return [type]     [description]
     */
    public function index()
    {
    	$data['pageTitle'] = '权限因子';
    	$data['pageTips']  = '注意：点击权限树可以对权限进行修改和删除，目前仅支持菜单级权限';
        $this->display_ztree('auth/auth.html',$data);
    }

    /**
     * [get_nodes_data 获取节点数据]
     * @return [type] [description]
     */
    public function get_nodes()
    {
        $data = $this->auth_model->getConditionData('id,pid,name,sort',' status=0','sort ASC,id ASC');
        $nodes = [];
        foreach ($data as $key => $value) {
            $nodes[$key]['id'] = $value['id'];
            $nodes[$key]['pId'] = $value['pid'];
            $nodes[$key]['name'] = $value['name'];
            $nodes[$key]['open'] = true;
        }
    	$this->ajaxReturn($nodes);
    }

    /**
     * [get_one_node 获取一个节点信息]
     * @return [type] [description]
     */
    public function get_one_node()
    {
        $id = $this->input->post('id');

        $data = $this->auth_model->getConditionData('menu_url,icon,sort,is_show',' status=0 and id="'.(int)$id.'"');
        if(isset($data[0])) $this->ajaxReturn($data[0]);
        $this->ajaxReturn($id,300,'数据错误');
    }

    /**
     * [save_auth 保存权限]
     * @return [type] [description]
     */
    public function save_auth()
    {
        $nodes = format_ajax_data($this->input->post('nodes'));
        $type = $this->input->post('type');

        $msg = '数据不完整或已存在';
        $err = '存储失败';
        #新增
        if($type==1){
            #检查文件数据合法性
            if($this->checkNodes($nodes)) $this->ajaxReturn($nodes,300,$msg);
            unset($nodes['pname']);
            unset($nodes['id']);
            if (!$nodes["pid"]) {
                $this->ajaxReturn($nodes,300,"请选择上级目录");
            }
            $res = $this->auth_model->addData($nodes);
            $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,$err);
        }else{
            #修改
            if(!$this->checkNodes($nodes,2)) $this->ajaxReturn($nodes,300,$msg);
            unset($nodes['pname']);
            $res = $this->auth_model->editData($nodes,'id="'.$nodes['id'].'"');
            $res ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,$err);
        }
    }

    /**
     * [delete_auth 删除节点]
     * @return [type] [ajax]
     */
    public function delete_auth()
    {
        $id = $this->input->post('id');
        if(!$id) $this->ajaxReturn($id,300,'参数错误');

        #检查是否有子菜单
        $child_nodes = $this->auth_model->getConditionData('*','pid="'.(int)$id.'"');
        if(isset($child_nodes[0]) && $child_nodes[0])
        {
            $this->ajaxReturn($child_nodes,300,'请先删除的子菜单');
        }

        $data = [];
        $data['status'] = 1;
        $res = $this->auth_model->editData($data,'id="'.$id.'"');

        #删除角色权限表中的权限
        $this->load->model('auth/role_auth_model');
        $this->role_auth_model->delData('auth_id="'.$id.'"');

        if ($res) $this->ajaxReturn();
        $this->ajaxReturn($res,300,'删除错误');        
    }

    /**
     * [checkNodes 数据检查]
     * @param  [type] $nodes [description]
     * @param  [type] $is_add [1表示新增检查，其他表示更新检查]
     * @return [type]        [true-检查成功，false-验证失败]
     */
    private function checkNodes($nodes,$is_add=1)
    {
        #检查数据完整性
        foreach (self::$fields as $key => $value) {
            if (!$nodes[$value]) {
                return false;
            }
        }

        if ($is_add!=1) return true;
        
        #检查重复
        // $res = $this->auth_model->get_auth_by_factor($nodes['module'],$nodes['controller'],$nodes['action']);
        $res = $this->auth_model->getConditionData('*','menu_url="'.$nodes['menu_url'].'"');
        if ($res) return false;
        return true; 
        
    }

    
}