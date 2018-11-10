<?php
/************************************************************
** @Description: 文件访问类
** @Author: haodaquan
** @Date:   2016-12-21 10:52:48
** @Last Modified by:   haodaquan
** @Last Modified time: 2016-12-21 10:39:26
*************************************************************/

class File extends MY_Controller
{
    static public $allow_ext = ['jpg', 'png','jpeg','gif','xlsx','zip','pdf','doc','docx','rar','ppt','pptx','xls','docx','tar'];
    static private $allow_size = 10485760;

	/**
	 * [file 显示文件]
	 * @return [type] [description]
	 */
	public function index()
	{
		$str = $this->input->get("id");
		if (!$str) {
		 	echo "参数有误";
			exit();
		}
		$id = authcode($str,'DECODE');

		$this->load->model('public/attachment_model');
        $atta = $this->attachment_model->getConditionData('*','id='.(int)$id);
        if (!isset($atta[0]['id']) || $atta[0]['id']!=$id) {
        	echo "附件找不到!";
			exit();
        }
		$file_path  = $atta[0]['path'];
		$file_ext = explode('.', $file_path);
		$file_type = end($file_ext); 
		if(!in_array(strtolower($file_type),self::$allow_ext)){
			echo "类型错误!";
			exit();
		} 
		$file_path = IMG_URL.'/'.rtrim(UPLOAD_PATH,'/').'/'.$file_path;
		if (strtolower($file_type)=='pdf') {
			header('Content-type: application/pdf');
		}else{
			header('Content-type: text/plain'); 
		}
		
		echo file_get_contents($file_path);
		exit();
	}

	public function test()
	{
		$id = authcode("67",'ENCODE');

		echo "/public/download/index?file=".$id;
	}

}