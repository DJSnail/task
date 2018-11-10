<?php
/************************************************************
** @Description: 文件图片上传基础类
** @Author: haodaquan
** @Date:   2016-11-28 13:52:48
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-02-08 18:01:43
*************************************************************/

class Upload extends MY_Controller
{
    public $file_path    = UPLOAD_PATH;
    public $allow_type   = ['jpg','txt','log','json','png','jpeg','gif','xlsx','zip','pdf','doc','docx','rar','ppt','pptx','xls','docx','tar'];//允许上传文件格式
    public $allow_size   = 10485760;//上传文件大小 2M2*1024*1024*5 10M

    public function __construct()
    {
        parent::__construct();
        $this->admin_info = $this->admin_model->check_admin();
        
    }
    /**
     * [file 文件上传]
     * @return [type] [description]
     */
    public function file()
    {
        $file = $this->input->get('file');
        $size = $this->input->get('size');

        if(!$file) $this->return_error('上传文件配置有误');
        if(!isset($_POST)) $this->return_error('上传有误！');

        $name     = $_FILES['file']['name']; 
        $size     = $_FILES['file']['size']; 
        $name_tmp = $_FILES['file']['tmp_name']; 
        if (empty($name)) $this->return_error('您还未选择文件');
        #判断文件类型
        $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型 
        if (!in_array($type, $this->allow_type)) $this->return_error('请上传正确类型的文件！');
        if ($size > $this->allow_type)  $this->return_error('文件大小已超过10M限制！');
        
        #上传地址 

        $path = $this->file_path.$file.'/'.date('Y-m-d',time()).'/';
        // if(!is_dir($path)) make_dir($path);
        $file_name = time() . rand(10000, 99999) . "." . $type;//名称 
        $file_path = $path .  $file_name; //上传后路径+名称
        $return_file_path = $file.'/'.date('Y-m-d',time()).'/'. $file_name;//返回后的地址

        // if (move_uploaded_file($name_tmp, $file_path)) 
        if (upload_file_to_oss($name_tmp, $file_path)) 
        { 
            $this->load->model('public/attachment_model');
            $data['name'] = $name;
            $data['path'] = $return_file_path;
            $data['uid'] = $this->admin_info['id'];
            $data['status'] = 0;
            $data['add_time'] = time();
            //保存
            $id = $this->attachment_model->addData($data);
            //临时文件转移到目标文件夹 
            echo json_encode(array("error"=>"0","path"=>$return_file_path,"name"=>$name,'id'=>$id)); 
            exit();
        } 
        $this->return_error('上传有误，请检查服务器配置！');
        
    }

    /**
     * [return_error 返回错误]
     * @param  [type] $msg [错误信息]
     * @return [type]      [description]
     */
    public function return_error($msg)
    {
    	echo json_encode(["error"=>$msg]);
    	exit();
    }


    /**
     * [image 图片上传]
     * @return [type] [description]
     */
    public function image()
    {
        $file   = $this->input->get('file');
        $width  = $this->input->get('w');
        $height = $this->input->get('h');


        if (!$file) $this->return_error('上传文件配置有误');
        if (isset($_POST)) 
        { 
            $name     = $_FILES['file']['name']; 
            $size     = $_FILES['file']['size']; 
            $name_tmp = $_FILES['file']['tmp_name']; 
           
            if (empty($name)) $this->return_error('您还未选择图片');
            $type = strtolower(substr(strrchr($name, '.'), 1)); //获取文件类型 
            if (!in_array($type, $this->allow_type)) $this->return_error('请上传正确类型的图片！');
            if ($size > $this->allow_size) $this->return_error('图片大小已超过2M限制！');
            
            $imageInfo = $this->getImageInfo($name_tmp);

            #判断宽度 110-gt 100-lt 100
            if (strpos($width, "-")!==false) {
                $widtharr = explode("-", $width);
                $with = $widtharr[0];
                if ($widtharr[1] == "gt" && $imageInfo['width'] < $widtharr[0]) {
                    $this->return_error('图片宽度不能小于'.$widtharr[0]);
                }else if($widtharr[1] == "lt" && $imageInfo['width'] > $widtharr[0]) {
                     $this->return_error('图片宽度不能大于'.$widtharr[0]);
                }
            }else{
                if($width!=0 && $imageInfo['width']!=$width) {
                    $this->return_error('图片宽度不符合要求！');
                }
            }

            if (strpos($height, "-")!==false) {
                $heightarr = explode("-", $height);
                $height = $heightarr[0];
                if ($heightarr[1] == "gt" && $imageInfo['height'] < $heightarr[0]) {
                    $this->return_error('图片高度不能小于'.$heightarr[0]);
                }else if($heightarr[1] == "lt" && $imageInfo['height'] > $heightarr[0]) {
                     $this->return_error('图片高度大于'.$heightarr[0]);
                }
            }else{
                if($height!=0 && $imageInfo['height']!=$height){
                   $this->return_error('图片高度不符合要求！'); 
                } 
            }



             

            
            $time = date('Y-m-d',time()); 
            $path = $this->file_path.$file.'/'.$time.'/';
            // if(!is_dir($path)) make_dir($path);
            $pic_name = time() . rand(10000, 99999) . "." . $type;//图片名称


            $path_arr = explode('/', $file);
            $file_name = isset($path_arr[1]) ? $path_arr[1] : '';
            $model_name = isset($path_arr[0]) ? $path_arr[0] : '';
            $pic_url = $path . $pic_name;//上传后图片路径+名称
            // $img_url = "/public/image/index?path=".$model_name."/".$file_name."/".$time.'/'.$pic_name;
            //$img_url = $img_path.'/'.$time.'/'.$pic_name; #访问名称
            // if (move_uploaded_file($name_tmp, $pic_url)) 
            upload_image_to_oss($name_tmp, $pic_url);
            $img_url = "/public/image/index?path=".$pic_url;
            //$img_url = IMG_URL."/".$pic_url;
            if ($pic_url)
            { 
                echo json_encode(array("error"=>"0","pic"=>$img_url,"name"=>$pic_url)); 
            } else { 
                $this->return_error('上传有误，请检查服务器配置！');
            } 
        }
    }

    /**
     * [getImageInfo 获取图片信息]
     * @Author haodaquan
     * @Date   2016-04-14
     * @param  [type]     $img [图片临时存储地址]
     * @return [type]          [description]
     */
    public function getImageInfo($img)
    {
        $imageInfo = getimagesize($img);
        if ($imageInfo !== false) 
        {
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            $imageSize = filesize($img);
            $info = array(
                "width" => $imageInfo[0],
                "height" => $imageInfo[1],
                "type" => $imageType,
                "size" => $imageSize,
                "mime" => $imageInfo['mime']
            );
            return $info;
        } else 
        {
            return false;
        }
    }

    
}
