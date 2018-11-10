<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Requirement_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'tech_requirement';
	}

	public function getTechInfo($id,$apply_no='')
	{
		$sql = 'SELECT
	            tr.*
			FROM
				tech_requirement AS tr
			WHERE ';
		if($id){
			$sql .= 'tr.id ='.$id;
		}else if($apply_no){
			$sql .= "tr.apply_no = '$apply_no'";
		}
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	public function getFilterList(){
		static $key = 'REQUIREMENT_FILTER_KEY';
		$ret = $this->getFromCache($key);
		if($ret && !empty($ret)){
			return $ret;
		}
		$filter = [];
		$sql = 'SELECT category_no,apply_user,apply_time FROM '.$this->_table.' WHERE status=3 order by id desc LIMIT 10000';
		$query = $this->db->query($sql);
		$list = $query->result_array();
		$category_no = [];
		$apply_user = [];
		$apply_time = [];
		foreach($list as $v){
			$category_arr = explode_plus($v['category_no']);
			foreach($category_arr as $item){
				if(!$item){
					continue;
				}
				if(array_key_exists($item,$category_no)){
					$category_no[$item] += 1;
				}else{
					$category_no[$item] = 1;
				}
			}
			$user_arr = explode_plus($v['apply_user']);
			foreach($user_arr as $item){
                if(!$item){
                    continue;
                }
				if(array_key_exists($item,$apply_user)){
					$apply_user[$item] += 1;
				}else{
					$apply_user[$item] = 1;
				}
			}
			$time_arr = explode_plus($v['apply_time']);
			foreach($time_arr as $item){
                if(!$item){
                    continue;
                }
                $item = date('Y',$item);
				if(array_key_exists($item,$apply_time)){
					$apply_time[$item.''] += 1;
				}else{
					$apply_time[$item.''] = 1;
				}
			}
		}
		arsort($category_no);
		arsort($apply_user);
		arsort($apply_time);
		$category_no = array_slice($category_no,0,10,true);
		$apply_user = array_slice($apply_user,0,10,true);
		$apply_time = array_slice($apply_time,0,10,true);
		if($category_no){
			$filter['category_no'] = array_keys($category_no);
		}
		if($apply_user){
			$filter['apply_user'] = array_keys($apply_user);
		}
		if($apply_time){
			$filter['apply_time'] = array_keys($apply_time);
            rsort($filter['apply_time']);
		}

		$this->deleteCache($key);
		$this->setToCache($key, $filter, 3600 * 24);
		return $filter;
	}

}