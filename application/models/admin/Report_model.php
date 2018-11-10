<?php
/************************************************************
** @Description: 报告
** @Author: haodaquan
** @Date: 2017-12-04 12:05:24 
** @Last Modified by:   haodaquan
** @Last Modified time: 2017-12-04 12:05:24 
*************************************************************/

class Report_model extends MY_Model
{
	protected $_table;
	public function __construct()
	{
		parent::__construct();
		$this->_table = 'purchase_report';
	}

	/**
	 * [getReportInfo 获取报告]
	 * @param  [type] $id [description]
	 * @return [type]     [description]
	 */
	public function getReportInfo($id,$report_no='')
	{
		$sql = 'SELECT
				p.id,
				p.report_no,
				p.title,
				p.category,
				p.keyword,
				p.author,
				p.company,
				p.detail,
				p.cover,
				p.price,
				p.uid,
				p.verify_uid,
				p.public_time,
				p.status,
				p.pdf_id,
				p.add_time,
				a.name,
				a.path,
				m.nickname,
				m2.nickname as verify_nickname
			FROM
				purchase_report AS p
			LEFT JOIN pb_attachment AS a ON p.pdf_id = a.id
			LEFT JOIN uc_member AS m ON p.uid=m.id
			LEFT JOIN uc_member AS m2 ON p.verify_uid=m2.id
			WHERE ';
        if($id){
            $sql .= 'p.id ='.$id;
        }else if($report_no){
            $sql .= "p.report_no = '$report_no'";
        }

		$query = $this->db->query($sql);
		return $query->result_array();
	}
	
	
	public function getFilterList(){
		
		static $key='REPORT_FILTER_KEY2';
		$ret = $this->getFromCache($key);
		if($ret && !empty($ret)){
			return $ret;
		}
		
		$filter=[];
		
		$sql = 'SELECT category_root,company FROM '.$this->_table.' WHERE status=3 order by id desc LIMIT 10000';
		$query = $this->db->query($sql);
		$list = $query->result_array();
		
		$category=[];
		$company=[];
		foreach($list as $v){
            $company_arr = explode_plus($v['company']);
            foreach($company_arr as $item){
                if(array_key_exists($item,$company)){
                    $company[$item] += 1;
                }else{
                    $company[$item] = 1;
                }
            }
            $category_arr = [];
            $category_arr[] = $v['category_root'];
            foreach($category_arr as $item){
                if(array_key_exists($item,$category)){
                    $category[$item] += 1;
                }else{
                    $category[$item] = 1;
                }
            }
		}
        arsort($company);
        arsort($category);
        $company = array_keys($company);
        $category = array_keys($category);
        $company = array_slice($company,0,10);
        $category = array_slice($category,0,10);
        if($category){
            $filter['category'] = $category;
        }
        if($company){
            $filter['company'] = $company;
        }
		$this->setToCache($key, $filter, 3600 * 24);
		return $filter;
	}

    function m_query_list($ids){
        $sql = "select id,title,category,add_time from ".$this->_table." where id in ($ids)";
        $query = $this->db->query($sql);
        $list = $query->result_array();
        return $list;
    }
}