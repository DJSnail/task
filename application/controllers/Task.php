<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->v = parent::$current_vision;
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
        $data['category'] = $this->public_model->get_category();
        $data['institution'] = $this->public_model->get_institution();
        //var_dump($task);exit;
        $this->load->view('collect/collect_task_new_detail.html', $data);
    }
    public function update_task_ajax(){
        if (!is_ajax()) {
            return;
        }
        $user = $this->user;
        $post = $this->input->post();
        $task_id = $post['task_id'];
        unset($post['task_id']);
        $task = $this->task_model->update_task($post,$task_id);
        if($task){
            echo json_encode(['status'=>1]);
        }else{
            echo json_encode(['status'=>-1,'error'=>'更新任务失败']);
        }
    }

}
