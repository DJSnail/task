<?php
/************************************************************
** @Description: 监控采集
** @Author: haodaquan
** @Date: 2018-01-05 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-05 12:05:24 
*************************************************************/

class Monitor_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'st_monitor';
	}


	public function getFilterList(){
		static $key = 'MONITOR_FILTER_KEY';
		$ret = $this->getFromCache($key);
		if($ret && !empty($ret)){
			return $ret;
		}
		$filter = [];
		$sql = 'SELECT institute,articlefeature as feature FROM '.$this->_table.' order by id desc';
		$query = $this->db->query($sql);
		$list = $query->result_array();

        $feature = [];
		$institute = [];
		foreach($list as $v){
            $feature_arr = explode_plus($v['feature']);
            foreach($feature_arr as $item){
                if(array_key_exists($item,$feature)){
                    $feature[$item] += 1;
                }else{
                    $feature[$item] = 1;
                }
            }
            if(array_key_exists($v['institute'],$institute)){
                $institute[$v['institute']] += 1;
            }else{
                $institute[$v['institute']] = 1;
            }
            
		}
        arsort($feature);
        arsort($institute);
        $feature = array_slice($feature,0,20);
        $institute = array_slice($institute,0,20);
		if($feature){
			$filter['feature'] = array_keys($feature);
		}
		if($institute){
			$filter['institute'] = array_keys($institute);
		}
        
        $this->deleteCache($key);
		$this->setToCache($key, $filter, 3600 * 24);
		return $filter;
	}

	public function  m_query_list($ids){
        $sql = "select id,title,mdate,institute from ".$this->_table." where id in ($ids)";
        $query = $this->db->query($sql);
        $list = $query->result_array();
        return $list;
	}
}