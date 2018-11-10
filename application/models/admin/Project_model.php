<?php
/************************************************************
** @Description: 项目
** @Author: haodaquan
** @Date: 2017-12-04 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:05:24 
*************************************************************/

class Project_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'project_cooperation';
	}

    public function getProjectInfo($id,$project_no='')
    {
        $sql = 'SELECT
	            pc.*
			FROM
				project_cooperation AS pc
			WHERE ';
        if($id){
            $sql .= 'pc.id ='.$id;
        }else if($project_no){
            $sql .= "pc.project_no = '$project_no'";
        }
        $query = $this->db->query($sql);
        return $query->result_array();
    }

	public function getFilterList(){
		static $key = 'PROJECT_FILTER_KEY';
		$ret = $this->getFromCache($key);
		if($ret && !empty($ret)){
			return $ret;
		}
		$filter = [];
		$sql = 'SELECT type,entrust_org,price FROM '.$this->_table.' WHERE status=3 order by id desc LIMIT 10000';
		$query = $this->db->query($sql);
		$list = $query->result_array();
		$type = [];
		$company = [];
		$price = [];
		foreach($list as $v){
			$type_arr = explode_plus($v['type']);
			foreach($type_arr as $item){
				if(array_key_exists($item,$type)){
                    $type[$item] += 1;
				}else{
                    $type[$item] = 1;
				}
			}
			if(array_key_exists($v['entrust_org'],$company)){
				$company[$v['entrust_org']] += 1;
			}else{
				$company[$v['entrust_org']] = 1;
			}
			$price_arr = explode_plus($v['price']);
			foreach($price_arr as $item){
                if($item == '0' || $item == '竞价'){
                    continue;
                }
                $item .= '万';
				if(array_key_exists($item,$price)){
                    $price[$item] += 1;
				}else{
                    $price[$item] = 1;
				}
			}
		}
		arsort($type);
		arsort($company);
		arsort($price);
        $type = array_slice($type,0,10);
		$company = array_slice($company,0,10);
        $price = array_slice($price,0,10);
		if($type){
			$filter['type'] = array_keys($type);
		}
		if($company){
			$filter['company'] = array_keys($company);
		}
		if($price){
			$filter['price'] = array_keys($price);
		}
		$this->deleteCache($key);
		$this->setToCache($key, $filter, 3600 * 24);
		return $filter;
	}
}