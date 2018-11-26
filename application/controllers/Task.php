<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->v = parent::$current_vision;
        $this->load->library('simple_html_dom');
        $this->load->model('task_model');
    }

    public function task_list(){
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $this->load->view('collect/collect_task.html', $data);
    }

    public function test_change(){
        $url = 'localhost:8089/change';
        $post['task_id'] = 4;
        $post['status'] = 1;
        $ret = curl_request($url,$post);
        var_dump($ret);
    }
    /**
     * 新建任务页面
     */
    public function create_task()
    {
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $data['category'] = $this->public_model->get_category();
        $data['institution'] = $this->public_model->get_institution();
        $this->load->view('collect/collect_task_new.html', $data);
    }
    public function create_task_ajax()
    {
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        $post = $this->input->post();
        $data['task_name'] = $post['task_name'];
        $data['institution'] = $post['institution'];
        $data['category'] = $post['category'];
        $task_id = $this->task_model->add_task($data);
        if($task_id){
            echo json_encode(['status'=>1,'task_id'=>$task_id]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'新建任务失败']);
        }
    }
    /**
     * 任务基本设置页面
     */
    public function task_config($task_id)
    {
        if(!$task_id || !is_numeric($task_id)){
            return;
        }
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $data['task'] = $this->task_model->get_one_task_info($task_id);
        if($data['task']['main_urls']){
            $data['task']['main_urls'] = str_replace("||",PHP_EOL,$data['task']['main_urls']);
        }
        $data['category'] = $this->public_model->get_category();
        $data['institution'] = $this->public_model->get_institution();
        //var_dump($data);exit;
        $this->load->view('collect/collect_task_new_detail.html', $data);
    }
    /**
     * 更新任务数据
     */
    public function update_task_ajax(){
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        $post = $this->input->post();
        $task_id = $post['task_id'];
        unset($post['task_id']);
        //文章来源
        if(isset($post['main_urls'])){
            $post['main_urls'] = str_replace(PHP_EOL,"||",$post['main_urls']);
        }
        //匹配关键词
        if(isset($post['gather_content'])){
            $post['gather_content'] = str_replace("；",";",$post['gather_content']);
        }
        //标签
        if(isset($post['custom_tags'])){
            $post['custom_tags'] = str_replace("；",";",$post['custom_tags']);
        }
        $task = $this->task_model->update_task($post,$task_id);
        if($task){
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'更新任务失败']);
        }
    }
    /**
     * 测试任务:文章来源
     */
    public function test_task_article_source(){
        $post = $this->input->post();
        $main_urls = str_replace(PHP_EOL,"||",$post['main_urls']);
        $match_rule = $post['match_rule'];
        $match_content = $post['match_content'];
        if(!$main_urls){
            return;
        }
        $main_urls = explode("||",$main_urls);
        //总文章URl列表
        $return = [];
        foreach($main_urls as $url){
            //每一个文章来源能找到的URL列表
            $url_array = [];
            $html = file_get_html($url);
            //使用URL通配符匹配
            if($match_rule == 0){
                foreach($html->find('a') as $element){
                    $each_url = $element->href;
                    $pattern = str_replace("/","\/",$match_content);
                    $pattern = str_replace("(*)","(\S)*",$pattern);
                    if(preg_match("/".$pattern."/", $each_url)){
                        $url_array[] = $each_url;
                    }
                }
            }
            //使用CSS选择器匹配
            if($match_rule == 1){
                foreach($html->find($match_content) as $element){
                    $each_url = $element->href;
                    $url_array[] = $each_url;
                }
            }
            //写入列表
            if(count($url_array) == 0){
                $return[$url] = null;
            }else{
                $return[$url] = $url_array;
            }
            $html->clear();
        }
        echo json_encode(['result'=>$return]);
    }

    /**
     * 测试任务:正文抓取
     */
    function test_text_gather(){
        $post = $this->input->post();
        $url = $post['url'];
        $title_rule = $post['title_rule'];
        $title_content = $post['title_content'];
        $text_rule = $post['text_rule'];
        $text_content = $post['text_content'];
        if(!$url || !$title_content || !$text_content){
            return;
        }
        $return = [];
        $html = file_get_html($url);
        if(!$html){
            $return;
        }
        //标题抓取,通配符
        if($title_rule == 0){
            $pattern = str_replace("/","\/",$title_content);
            $pattern = str_replace("(*)","(\S)*",$pattern);
            if(preg_match("/".$pattern."/", $html, $match)){
                $title = $match[0];
            }
        }
        //标题抓取,CSS选择器
        if($title_rule == 1){
            $title = $html->find($title_content,0)->plaintext;
        }
        //正文抓取,通配符
        if($text_rule == 0){
            $pattern = str_replace("/","\/",$text_content);
            $pattern = str_replace("(*)","(\S)*",$pattern);
            if(preg_match("/".$pattern."/", $html, $match)){
                $text = $match[0];
            }
        }
        //正文抓取,CSS选择器
        if($text_rule == 1){
            $text = $html->find($text_content,0)->outertext;
        }
        //处理图片链接 ./ 的问题
        if(isset($text)){
            $end_url = strrchr($url,"/");
            $url = str_replace($end_url,"/",$url);
            $text = str_replace('src="./','src="'. $url ,$text);
        }
        $html->clear();
        $return['title'] = isset($title)?$title:null;
        $return['text'] = isset($text)?$text:null;
        echo json_encode(['result'=>$return]);
    }

}
