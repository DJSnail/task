<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->v = parent::$current_vision;
    }

    public function index(){
        $user = $this->user;
        $data['user'] = $user;
        $data['v'] = $this->v;
        $this->load->view('index.html', $data);
    }



}
