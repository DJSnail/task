<?php
/************************************************************
** @Description: 专家
** @Author: haodaquan
** @Date: 2017-12-04 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:05:24 
*************************************************************/

class Expert_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'expert_consult';
	}

    public function getEepertInfo($id,$expert_no='')
    {
        $sql = 'SELECT
	            ex.*
			FROM
				expert_consult AS ex
			WHERE ';
        if($id){
            $sql .= 'ex.id ='.$id;
        }else if($expert_no){
            $sql .= "ex.expert_no = '$expert_no'";
        }
        $query = $this->db->query($sql);
        return $query->result_array();
    }

	public function getFilterList(){
		static $key = 'EXPERT_FILTER_KEY';
		$ret = $this->getFromCache($key);
		if($ret && !empty($ret)){
			return $ret;
		}
		$filter = [];
		$sql = 'SELECT job,domain,company FROM '.$this->_table.' WHERE status=3 order by id desc LIMIT 10000';
		$query = $this->db->query($sql);
		$list = $query->result_array();
        $domain = [];
		$company = [];
        $job = [];
		foreach($list as $v){
            $domain_arr = explode_plus($v['domain']);
            foreach($domain_arr as $item){
                if(!$item){
                    continue;
                }
                if(array_key_exists($item,$domain)){
                    $domain[$item] += 1;
                }else{
                    $domain[$item] = 1;
                }
            }
            if(array_key_exists($v['company'],$company)){
                $company[$v['company']] += 1;
            }else{
                $company[$v['company']] = 1;
            }
            $job_arr = explode_plus($v['job']);
            foreach($job_arr as $item){
                if($item == '无'){
                    continue;
                }
                if(array_key_exists($item,$job)){
                    $job[$item] += 1;
                }else{
                    $job[$item] = 1;
                }
            }
		}
        arsort($domain);
        arsort($company);
        arsort($job);
        $domain = array_slice($domain,0,20);
        $company = array_slice($company,0,20);
        $job = array_slice($job,0,20);
		if($domain){
			$filter['domain'] = array_keys($domain);
		}
		if($company){
			$filter['company'] = array_keys($company);
		}
        if($job){
            $filter['job'] = array_keys($job);
        }
        $this->deleteCache($key);
		$this->setToCache($key, $filter, 3600 * 24);
		return $filter;
	}


}