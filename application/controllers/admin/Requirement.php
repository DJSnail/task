<?php
/************************************************************
** @Description: 文献信息
** @Author: haodaquan
** @Date:   2017-12-04
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-02-09 09:54:43
*************************************************************/

class Requirement extends MY_Controller
{
    public $data = [];
    //列表字段，必须设置
    public $showFields = array(
                                'apply_no'     => '编号',
                                'title_en'   		=> '原文标题',
                                'category_no'   		=> '主分类号',
                                'add_time'   	=> '创建时间',
                                'status'       	=> '状态',
                                'action'        => '操作'
                            );
    public $columnsWidth = array(
                                'action'        => 150,
                            );
    public $pageTitle 	= '专利技术信息';
    public $modelName   = 'requirement_model';
    public $searchFile  = 'admin/tech_search.html';#搜索文件
    public $pageTips    = '专利技术信息管理';
    public $checkCol    = 0;

    public $role = [];
    public static $status_text = ["删除",'审核中',"驳回","通过"];
    // private $queue_stock_name;#库存更新
    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin/requirement_model');
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
    	$_POST['status|>=']=0;
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
     * [detail 查看&修改状态]
     * @return [type] [description]
     */
    public function detail()
    {
    	$data['pageTitle'] = '审核文献';
    	$data['pageTips']  = '文献详情';


    	$id = $this->input->get("id");
    	$tech = $this->requirement_model->getTechInfo((int)$id);
    
    	if (!isset($tech[0]['id'])) {
    		echo "数据不完整，创建者不存在或者主要联系人不存在";
    		exit();
    	}
        $this->load->model('public/attachment_model');

        //主要联系人处理
        $this->load->model('admin/connection_model');
        
        $main[0]=[];
        if($tech[0]['main_conn']){
            $main = $this->connection_model->getConditionData("username,email,phone,company",'id = '.$tech[0]['main_conn'],1);
        }
        //其他联系人处理
        $other = [];
        if ($tech[0]['other_conn']) {
            $other = $this->connection_model->getConditionData("username,email,phone,company",'id IN('.$tech[0]['other_conn'].')',1);
            if($other){
                foreach ($other as $key => $value) {
                    if (!isset($value['username'])){
                        $other = $value;
                    }
                }
                
            }
            
        }
        
        $data['main_name'] = $main[0];//主要联系人
        $data['other_name'] = $other;//其他联系人
        
    	$data['tech']  = $tech[0];
    	//$data['atta']    = $atta;
    	// $data['op_atta'] = $attas[0];//处理结果    	
    	$this->display('admin/tech_detail.html',$data);
    }

    /**
     * [addJson 新增页面]
     */
    public function add()
    {
        $data['pageTitle'] = '新增信息';
        $data['pageTips']  = '注意是json格式的文件';
        $this->display('admin/tech_add.html',$data);
    }

    /**
     * [save 保存数据]
     * @return [type] [description]
     */
    public function save()
    {
        $data = format_ajax_data($this->input->post('form_data'));
        $id = isset($data['upload_file_id']) ? $data['upload_file_id'] : 0;

        if ($id==0) {
            $this->ajaxReturn($data,300,'上传错误');
        }

        $this->load->model('public/attachment_model');
        $atta = $this->attachment_model->getConditionData('*','id='.(int)$id);
        if (!isset($atta[0]['id']) || $atta[0]['id']!=$id) {
            echo "附件找不到!";
            exit();
        }
        $file_path  = $atta[0]['path'];
        $file_path = IMG_URL.'/'.rtrim(UPLOAD_PATH,'/').'/'.$file_path;
        $add_json = file_get_contents($file_path);
        $add_array = json_decode(trim($add_json,chr(239).chr(187).chr(191)),true);
        $this->load->model('requirement_model');
        $res = [];
        foreach($add_array as $key=>$item) {
            // if ($key>5) {
            //     break;
            // }
            $data = [];
            $data['uid'] = $this->admin_info['id'];
            $data['status'] = 3;
            $data['add_time'] = time();
            foreach ($item as $k => $v) {
                if ($k == '申请号') {
                    $data['apply_no'] = $v;
                }
                if ($k == '标题') {
                    $data['title_en'] = $v;
                }
                if ($k == '申请人国别代码') {
                    $data['apply_country'] = $v;
                }
                if ($k == '主分类号') {
                    $data['category_no'] = $v;
                }
                if ($k == 'IPC') {
                    $data['ipc'] = $v;
                }
                if ($k == '申请日') {
                    $data['apply_time'] = strtotime($v);
                }
                if ($k == '公开（公告）号') {
                    $data['public_no'] = $v;
                }
                if ($k == '摘要') {
                    $data['abstract_en'] = $v;
                }
                if ($k == '摘要（翻译）') {
                    $data['abstract_cn'] = $v;
                }
                if ($k == '申请人') {
                    $data['apply_user'] = $v;
                }
                if ($k == '标题（翻译）') {
                    $data['title_cn'] = $v;
                }
                if ($k == '公开（公告）日') {
                    $data['public_time'] = strtotime($v);
                }
            }
            $ret = $this->requirement_model->saveData($data,"apply_no='".$data['apply_no']."'");
            $res[] = $ret;
            if ($ret < 1) {
                $this->ajaxReturn($ret,300,'处理失败，请重新上传');
            }
            // else{
            //     $sql = $this->requirement_model->saveData($data,"apply_no='".$data['apply_no']."'",1,1);

            //     $this->write_log('sql.log',$sql.PHP_EOL,'tmp');
            // }
        }
        $this->ajaxReturn($res);
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
    	//$data['op_uid'] = $this->admin_info['id'];
    	$data['edit_time']   = time();
    	$data['verify_uid'] =  $this->admin_info['id'];
    	//$data['op_attachment'] = (int)$data['upload_file_id'];
    	//unset($data['upload_file_id']);
    	$res = $this->requirement_model->editData($data,'id="'.$data['id'].'"');
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
    	$data['status'] = -1;
    	$res = $this->requirement_model->editData($data,'id="'.(int)$id.'"');
    	($res!=-1 && $res!=false) ? $this->ajaxReturn($res) : $this->ajaxReturn($res,300,'操作失败');
    }

}