<?php
/************************************************************
** @Description: 文件下载类
** @Author: haodaquan
** @Date:   2016-11-29 13:52:48
** @Last Modified by:   haodaquan
** @Last Modified time: 2016-11-29 13:39:26
*************************************************************/

class Download extends MY_Controller
{
    static public $allow_ext = ['jpg', 'png','jpeg','gif','xlsx','zip','pdf','doc','docx','rar','ppt','pptx','xls','docx','tar'];
    static private $allow_size = 10485760;

    /**
     * [index 下载文件]
     * @return [type] [description]
     */
	public function index()
	{
		$str = $this->input->get("file");
		if (!$str) {
		 	echo "参数有误";
			exit();
		}
		$id = authcode($str,'DECODE');

		
		$this->load->model('public/attachment_model');
        //保存
        $atta = $this->attachment_model->getConditionData('*','id='.(int)$id);
        if (!isset($atta[0]['id']) || $atta[0]['id']!=$id) {
        	echo "附件找不到!";
			exit();
        }
		$file_path  = $atta[0]['path'];
		$file_name  = $atta[0]['name'];

		$file_ = explode('.', $file_path);
		$file_type = $file_[1]; 
		if(!in_array(strtolower($file_type),self::$allow_ext)){
			echo "类型错误!";
			exit();
		} 
		$this->load->helper('download');
		// $file_path = rtrim(UPLOAD_PATH,'/').'/'.$file_path;
		$file_path = IMG_URL.'/uploads/'.$file_path;
	

		if(!check_remote_file_exists($file_path)) {
			echo "文件不存在!";
			exit();
		}
		// if (filesize($file_path)>self::$allow_size) {
		// 	echo "文件超过10M";
		// 	exit();
		// }
		$data = file_get_contents($file_path);
		my_force_download($file_path,$file_name,$data);
	}

	/**
	 * [image 访问upload文件夹中的图片 废弃]
	 * @return [type] [description]
	 */
	public function image()
	{
		$file_path  = $this->input->get('path');
		$file_type = end(explode('.', $file_path));
		header('Content-Type: image/jpeg');
		if(!in_array(strtolower($file_type),self::$allow_ext)){
			echo "'类型错误!'";
			exit();
		} 
		$this->load->helper('download');
		$file_path = rtrim(UPLOAD_PATH,'/').'/'.$file_path;
		if(!is_file($file_path)) {
			echo "文件不存在!";
			exit();
		}

		if (filesize($file_path)>self::$allow_size) {
			echo "文件超过10M";
			exit();
		}
		echo file_get_contents($file_path);
		exit();
	}
}