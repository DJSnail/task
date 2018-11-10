<?php

class Phpredis{
	
	private $config;
	private $ci;

	function __construct(){
		$this->ci = & get_instance();
		$this->ci->config->load('redis');
		$this->config = $this->ci->config->item('redis');
		$this->init();	
	}
	
	function init(){
		$this->redis = new Redis();
		try {
			//$this->redis->connect($this->config['host'],$this->config['port']);
            if ($this->redis->connect($this->config['host'], $this->config['port']) == false) {
                die($this->redis->getLastError());
            }
		}catch(Exception $e){
			if (ENVIRONMENT == 'development'){
				echo 'Caught exception: ',  $e->getMessage(), "\n";
			}
		}
	}
}
	
