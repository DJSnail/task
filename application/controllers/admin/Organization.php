<?php

class Organization extends MY_Controller {
	
	public $data = [];
	public $modelName   = 'organization_model';
	public $checkCol    = 0;
	public $pageTitle  = '机构';
	
	//列表字段，必须设置
	public $showFields = [
		'name'     	=> '机构名称',
		'logo'     	=> '机构名称',
		'add_time' => '添加时间',
		'edit_time' => '修改时间',
		'action'    	    => '操作'
	];
	
	public $columnsWidth = [
	'title'         => 200,
	'action'        => 100,
	];
	
	public $searchFile  = 'admin/organization_search.html';
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('admin/organization_model');
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
			$value['logo']='<a href="'.$value['link'].'" target="_blank"><img src="'.$value['logo'].'" width="" height=""/></a>';
			$value['action'] = getButton($value['id'],['tabEdit'=>'编辑','delete'=>'删除']);
			$data['items'][$key] = $value;
		}
		return $data;
	}
	
	public function add(){
		if(!empty($post=$this->input->post())){
			$set=format_ajax_data($post['data']);
			if(!$set['name']){
				$this->ajaxReturn([],500,'字段缺失！');
			}
			if($set['link'] && (stripos(trim($set['link']), 'http://')===false || stripos(trim($set['link']), 'https://')===false)){
				$set['link'] = 'http://'.$set['link'];
			}
			
			if($this->organization_model->addData($set)){
				$this->ajaxReturn();
			}
	
			$this->ajaxReturn([],500,'操作失败！');
		}
	
		$data['pageTitle']='新增-机构';
		$data['action']='/admin/organization/add';
		$this->display('admin/organization_edit.html',$data);
	}
	
	public function edit(){
		if(!empty($post=$this->input->post())){
			$set=format_ajax_data($post['data']);
			if(!$set['id']||!$set['name']){
				$this->ajaxReturn([],500,'字段缺失！');
			}
	
			$where = ['id'=>$set['id']];
			unset($set['id']);
			if($this->organization_model->editData($set,$where)){
				$this->ajaxReturn();
			}
	
			$this->ajaxReturn([],500,'操作失败！');
		}
	
		$id = intval($this->input->get('id'));
		if(!$id){
			exit('参数缺失');
		}
	
		$info = $this->organization_model->getConditionData('*','id='.$id,'','1',0);
		$data['info']=$info[0];
		$data['id']=$id;
		$data['pageTitle']='修改-机构';
		$data['action']='/admin/organization/edit';
		$this->display('admin/organization_edit.html',$data);
	}
	
	public function delete(){
		$id = intval($this->input->post('id'));
		if(!$id){
			$this->ajaxReturn([],500,'参数缺失！');
		}
		$set['is_del']=1;
		$where = ['id'=>$id];
		if($this->organization_model->editData($set,$where)){
			$this->ajaxReturn();
		}
	}
}

?>