<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');
class MY_Model extends CI_Model
{
	
	/**
	* [__construct 初始化方法]
	*/
	public function __construct()
	{
		parent::__construct();
		$this->db = $this->load->database('db_product',true);
	}

    function setToCache($key, $value, $expire = 0) {
        $redis = $this->phpredis->redis;
        $redis->set($key, serialize($value));
        $redis->expire($key, $expire);
    }

    function getFromCache($key) {
        $redis = $this->phpredis->redis;
        $cache = $redis->get($key);
        return $cache ? unserialize($cache) : [];
    }

    function deleteCache($key) {
        $redis = $this->phpredis->redis;
        return $redis->delete($key);
    }

	/**
	* [get_array_result sql方式查询获取数组]
	* @Date   2016-06-27
	* @param  [type]     $query [query]
	* @return [type]           [数组]
	*/
	public function get_array_result($query)
	{
		$data = [];
		if(isset($query->num_rows) && $query->num_rows>0) $data = $query->result_array();
		return $data;
	}


	/**
	* [queryList 单表查询数据，子类一般需要重写]
	* @Author haodaquan
	* @Date   2016-04-06
	* @param  [type]     $param [wehre,sort,limit]
	* @return [type]          [description]
	*/
	public function queryList($param)
	{
		$map = $this->queryParam($param);
		$totalCount = $this->getCount($map['where']);

		$sql   = "SELECT * FROM ". $this->_table.' '.$map['where']. 
				$map['orderby'].' LIMIT '.($map['current_page']-1)*$map['page_size'].','.$map['page_size'];
		$query = $this->db->query($sql);
		$items = $query->result_array();
		return $this->returnData(200,'success',$items,$totalCount);
	}

	/**
	* [returnData 组成返回数据]
	* @Author haodaquan
	* @Date   2016-04-06
	* @param  [type]     $status     [状态码，200，300，500]
	* @param  [type]     $info       [状态信息]
	* @param  [type]     $data       [返回信息]
	* @param  [type]     $totalCount [查询时返回条数]
	* @return [type]                 [返回数组]
	*/
	protected function returnData($status,$info,$items,$totalCount='')
	{
		$data = [];
		$data['status']  = $status;
		$data['message'] = $info;
		if($totalCount==='')
		{
			$data['data'] = $items;
		}else{
		$data['data'] = array(
		  'totalCount'=>$totalCount,
		  'items'=>$items
		  );
		}
		return $data;
	}

	/**
	* [queryParam mmgrid处理查询数据 TODO安全过滤]
	* @Author haodaquan
	* @Date   2016-11-17
	* @param  [type]     $param [查询参数]
	* @return [type]            [description]
	*/
	protected function queryParam($param)
	{
		$where = ' WHERE 1=1 ';
		//查询分页
		$limit = isset($param['limit']) ? $param['limit'] : 10;
		$page  = isset($param['page']) ? $param['page'] : 1;
		unset($param['page']);
		unset($param['limit']);

		#排序
		$orderby = ' ORDER BY id desc ';
		if(isset($param['sort']))
		{
			$sortArr = explode('.',$param['sort']);
			$orderby = ' ORDER BY '.$sortArr[0].' '.$sortArr[1];
		}

		$allowedQuery = ['>','>=','<','<=','in','like','=','<>','time','or_like'];#允许的搜索条件 默认全是and关系
		#搜索情况下
		foreach ($param as $key => $value) {
			// 检测$value 小防范一下下
			// if(inject_check($value)) continue;
			$keyArr = explode('|',$key);

			if(!isset($keyArr[1])) continue;
			if(!in_array($keyArr[1],$allowedQuery)) continue;
			if($value==='') continue;
			if($value==-9) continue;

			if(strpos($keyArr[0],'-'))
			{
				$tbKey = explode('-',$keyArr[0]);
				$keyArr[0] = $tbKey[0].'.'.$tbKey[1];
			}
            if($keyArr[1] == 'or_like')
            {
                $search_key_arr = explode('/',$keyArr[0]);
            }
			switch ($keyArr[1]) {
				case 'like':
					$where .= ' AND '.$keyArr[0].' like "%'.$value.'%" ';
					break;
				case 'in':
					$where .= ' AND '.$keyArr[0].' in ('.$value.') ';
					break;
                case 'time':
                    $value2 = $value + 365 * 24 * 3600;
                    $where .= ' AND '.$keyArr[0].' > '.$value.' AND '.$keyArr[0].' < '.$value2;
                    break;
                case 'or_like':
                    $where .= ' AND (';
                    foreach($search_key_arr as $i=>$k){
                        if($i > 0){
                            $where .= ' OR ';
                        }
                        $where .= $k.' like "%'.$value.'%" ';
                    }
                    $where .= ' )';
                    break;
				default:
					$where .= ' AND '.$keyArr[0].$keyArr[1].'"'.$value.'"';
					break;
			}
		}
		//echo $where;
		//dump(['where'=>$where,'orderby'=>$orderby,'page_size'=>$limit,'current_page'=>$page]);
		return ['where'=>$where,'orderby'=>$orderby,'page_size'=>$limit,'current_page'=>$page];



	}


	/**
	 * [getCount 获取数据条数]
	 * @Author haodaquan
	 * @Date   2016-04-06
	 * @param  string      $where [ WHERE 查询条件]
	 * @return [type]            [description]
	 */
	public function getCount($where='')
	{
		$total_sql = "SELECT count(*) as count FROM ". $this->_table .' '. $where;
		$_total = $this->db->query($total_sql)->result_array();
		return isset($_total[0]['count']) ? $_total[0]['count'] : 0;
	}


	########################
	#
	# 常用增删改查 基础类方法 START
	#
	########################
	
	/**
	 * [getConditionData 有条件]
	 * @param  string $field [获取字段]
	 * @param  string $where [条件]
	 * @param  string $order [id desc]
	 * @param  string $where [1,10]
	 * @param  int $debug [1,10]
	 * 
	 * @return [type]        [description]
	 */
	public function getConditionData($field='*',$where='1=1',$order='',$limit='',$debug=0)
	{
		$sql   = "SELECT ".$field
				." FROM ".$this->_table
				.' WHERE '.$where;
		$sql .= $order ? ' ORDER BY '.$order : '';
		$sql .= $limit ? ' limit '.$limit : '';
		if ($debug==1) return $sql;
		return $this->db->query($sql)->result_array();
	}

	/**
	 * [addData 新增数据]
	 * @param array $data [数据]
	 * @param int $add_time [是否自动增加add_time字段]
	 * @param int $debug [是否debug,1-debug]
	 * @return int id
	 */
	public function addData($data=[],$add_time=1,$debug=0)
	{
		if (empty($data)) return false;
		if($add_time==1) $data['add_time'] = time();
		$this->db->insert($this->_table,$data);
		if($debug==1) return $this->db->last_query();
		return $this->db->insert_id();
	}

	/**
	 * [editData 修改]
	 * @param  array  $data  [数组]
	 * @param  string $where [字符串条件]
	 * @param int $edit_time [是否自动增加edit_time字段]
	 * @param int $debug [是否debug,1-debug]
	 * @return [type]        [false,or int 0,1]
	 */
	public function editData($data=[],$where='',$time=1,$debug=0)
	{
		if(!$where || empty($data)) return false;
		if($time==1) $data['edit_time'] = time();
		$this->db->where($where); 
		$this->db->update($this->_table,$data);
		if($debug==1) return $this->db->last_query();
		return $this->db->affected_rows();
	}

	/**
	 * [saveData 更新或新增商品]
	 * @param  [type] $data  [一维数组]
	 * @param  string $where [条件]
	 * @param int $time [是否自动增加更新时间]
	 * @param int $debug [是否debug,1-debug]
	 * @return [type]        [description]
	 */
	public function saveData($data=[],$where='',$time=1,$debug=0)
	{
		$res = $this->getConditionData('*',$where);
		return $res ? $this->editData($data,$where,$time,$debug) : $this->addData($data,$time,$debug);	
	}

	/**
	 * [delData 删除 慎用，一般采用edit修改状态实现]
	 * @param  string $where [description]
	 * @return [type]        [description]
	 */
	public function delData($where=[])
	{
		if (empty($where)) return false; 
		return  $this->db->delete($this->_table, $where);
	}

	########################
	#
	# 常用增删改查 基础类方法 END
	#
	########################

    function divide_page($count_sql, $count_field, $current_page, $every_page) {
        $query = $this->db->query($count_sql);
        $data = $query->row_array();
        $total_num = $data[$count_field];
        if($total_num == 0){
            return $this->get_null_page();
        }
        $total_page = ceil($total_num / $every_page);
        $start_num = ($current_page - 1) * $every_page;
        if ($start_num < 0 || $start_num >= $total_num) {
            return null;
        }
        $next_page = $current_page + 1;
        if($next_page > $total_page){
            $next_page = $total_page;
        }
        $pre_page = $current_page - 1;
        if($pre_page < 1){
            $pre_page = 1;
        }
        $page_array = [
            'start_num'=>$start_num,
            'every_page'=>$every_page,
            'total_page'=>$total_page,
            'current_page'=>$current_page,
            'total_num'=>$total_num,
            'next_page'=>$next_page,
            'pre_page'=>$pre_page
        ];
        return $page_array;
    }

    function get_null_page(){
        return [
            'start_num'=>1,
            'every_page'=>10,
            'total_page'=>1,
            'current_page'=>1,
            'total_num'=>0,
            'next_page'=>1,
            'pre_page'=>1
        ];
    }

    //SQL语句的条件
    function sql_condition($query_array) {
        $condition_str = ' where 1=1 ';
        $len = count($query_array);
        if ($query_array == null || $len == 0) {
            return $condition_str;
        }
        for ($i = 0; $i < $len; $i++) {
            $query_object = $query_array[$i];
            if ($query_object['query_type'] == "like") {
                $condition_str.=" and " . $query_object['query_field'] . " like '%" . $query_object['query_value'] . "%' ";
            } elseif (($query_object['query_type'] == "in")) {
                $query_value = str_replace("，", ",", $query_object['query_value']);
                //加上引号
                $query_value = explode(',', $query_value);
                foreach ($query_value as &$v) {
                    $v = "'" . $v . "'";
                }
                $query_value = implode(',', $query_value);
                $condition_str.=" and " . $query_object['query_field'] . " in (" . $query_value . ") ";
            } elseif (($query_object['query_type'] == "not in")) {
                $condition_str.=" and " . $query_object['query_field'] . " not in (" . $query_object['query_value'] . ") ";
            } elseif (($query_object['query_type'] == "<") || ($query_object['query_type'] == ">")) {
                $query_object['query_value'] = str_replace("，", ",", $query_object['query_value']);
                $condition_str.=" and " . $query_object['query_field'] . " " . $query_object['query_type'] . " " . $query_object['query_value'];
            } elseif (($query_object['query_type'] == "<>") ) {
                $condition_str.=" and " . $query_object['query_field'] . "<>" . $query_object['query_value'] . " ";
            } else {
                $condition_str.=" and " . $query_object['query_field'] . " = '" . $query_object['query_value'] . "' ";
            }
        }
        return $condition_str;
    }

}
