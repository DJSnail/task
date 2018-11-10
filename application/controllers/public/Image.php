<?php
/************************************************************
** @Description: 图片访问
** @Author: haodaquan
** @Date:   2016-11-29 13:52:48
** @Last Modified by:   haodaquan
** @Last Modified time: 2016-11-29 13:39:26
*************************************************************/

class Image extends MY_Controller
{
	/**
	 * [index 访问upload文件夹中的图片,走阿里云OSS]
	 * @return [type] [description]
	 */
	public function index()
	{
		header('Content-Type: image/jpeg');
		$file_path  = $this->input->get('path');
		// $file_path = rtrim(UPLOAD_PATH,'/').'/'.$file_path;
		$file_path = IMG_URL.'/'.$file_path;
		echo file_get_contents($file_path);
		exit();
	}
}