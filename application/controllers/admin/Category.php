<?php

class Category extends MY_Controller {
	
	public $data = [];
	public $modelName   = 'category_model';
	public $checkCol    = 0;
	public $pageTitle  = '主题分类';
	
	//列表字段，必须设置
	public $showFields = [
		'category_name'     	=> '分类名称',
		'sort' => '排序',
		'add_time' => '添加时间',
		'edit_time' => '修改时间',
		'action'    	    => '操作'
	];
	
	public $columnsWidth = [
		'title'         => 200,
		'action'        => 100,
	];
	
	public $searchFile  = 'admin/category_search.html';
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('admin/category_model');
	}
	
	public function index(){
		parent::index();
	}
	
	/**
	 * [query 查询]
	 * @return [type] [description]
	 */
	public function query()
	{
		$_POST['is_del|<>'] = 1;
		$_POST['sort'] = "id.desc";
		$_POST['limit'] = 1000;
		parent::query();
	}
	
	/**
	 * [listDataFormat 格式化列表数据]
	 * @return [type] [description]
	 */
	public function listDataFormat($listData)
	{
		$data['totalCount'] = $listData['totalCount'];
		$buttons = ['update'=>'编辑','delete'=>'删除'];
		
		foreach ($listData['items'] as $key => $value) {
			$value['add_time'] = date('Y-m-d',$value['add_time']);
			$value['edit_time'] = $value['edit_time'] ? date('Y-m-d',$value['edit_time']) : '';
			$value['category_name']='<div style="width:100%;text-align:left;">'.$value['category_name'].'</div>';
			$value['action'] = $value['allow_del'] ? getButton($value['id'],['tabEdit'=>'编辑','delete'=>'删除']) : getButton($value['id'],['tabEdit'=>'编辑']);
			$data['items'][$key] = $value;
		}
		return $data;
	}
	
	public function add(){
		if(!empty($post=$this->input->post())){
			$set=format_ajax_data($post['data']);
			if(!$set['category_name']){
				$this->ajaxReturn([],500,'字段缺失！');
			}
			$set['level']=intval($set['level'])+1;
			if($this->category_model->addData($set)){
				$this->ajaxReturn();
			}

			$this->ajaxReturn([],500,'操作失败！');
		}
		
		$list = $this->category_model->getCategoryTree();
		$data['category']=$list;
		$data['pageTitle']='新增-主题分类';
		$data['action']='/admin/category/add';
		$this->display('admin/category_edit.html',$data);
	}
	
	public function edit(){
		if(!empty($post=$this->input->post())){
			$set=format_ajax_data($post['data']);
			if(!$set['id']||!$set['category_name']){
				$this->ajaxReturn([],500,'字段缺失！');
			}

			$where = ['id'=>$set['id']];
			unset($set['id']);
			if($this->category_model->editData($set,$where)){
				$this->ajaxReturn();
			}

			$this->ajaxReturn([],500,'操作失败！');
		}
		
		$id = intval($this->input->get('id'));
		if(!$id){
			exit('参数缺失');
		}
	
		$info = $this->category_model->getConditionData('*','id='.$id,'','1',0);
		$data['info']=$info[0];
		$list = $this->category_model->getCategoryTree();
		$data['category']=$list;
		$data['id']=$id;
		$data['pageTitle']='修改-主题分类';
		$data['action']='/admin/category/edit';
		$this->display('admin/category_edit.html',$data);
	}
	
	public function delete(){
		$id = intval($this->input->post('id'));
		if(!$id){
			$this->ajaxReturn([],500,'参数缺失！');
		}
		$set['is_del']=1;
		$where = ['id'=>$id];
		if($this->category_model->editData($set,$where)){
			$this->ajaxReturn();
		}
	}
}