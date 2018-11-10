<?php
/************************************************************
** @Description: 监控抓取
** @Author: haodaquan
** @Date:   2018-01-05 13:52:48
** @Last Modified by:   haodaquan
** @Last Modified time: 2018-01-05 13:52:48
*************************************************************/

class Monitor extends MY_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('public/monitor_model');

	}
	/**
	 * [index 监控抓取]
	 * @return [type] [description]
	 */
	public function index()
	{
		$date = $this->input->get("date");
        $end = $this->input->get("end");
        if(!$date){
            $end = date("Y-m-d",strtotime("-1 day"));
            $date = date("Y-m-d",strtotime("-2 day"));
        }
		$num = $this->monitor_data_handle($date,$end,10);
		$start = ceil($num / 10);#总页数
		if ($start == 1)  exit("共一页数据完成");
		for ($i = 1; $i < $start ; $i++) { 
			$this->monitor_data_handle($date,$end,$i*10);
		}
		echo "共".$num."条数据";
	}

	/**
	 * [monitor_data_handle   监控数据处理]
	 * @param  string  $date  [description]
	 * @param  integer $start [description]
	 * @return [type]         [description]
	 */
	public function monitor_data_handle($date="",$end="",$start=0)
	{
		$name = "生物质信息监测平台";
		$date = $date ? $date : date("Y-m-d");
		// dump("Page:".$start);
		$url  = "http://stm.las.ac.cn/STMonitor/services/GenerateXML?name=".urlencode($name)."&startTime=".$date."&start=".$start;
        if($end){
            $url .= "&endTime=".$end;
        }
		$res  = http($url,[],"GET");
		$result = simplexml_load_string($res,'SimpleXMLElement', LIBXML_NOCDATA);
		$jsonStr = json_encode($result);
		$jsonArray = json_decode($jsonStr,true);
		$fields = ["key","mdate","title","serverid","content","commentrank","url","stmurl","institute","instituteurl","institutecountry","institutefeature","articlefeature","language","enabstract"];
		if (!isset($jsonArray['article'])) {
			return isset($jsonArray['@attributes']['numFound']) ? $jsonArray['@attributes']['numFound'] : 0;
		}
		foreach ($jsonArray['article'] as $key => $value) {
			#组织数据
			$data = [];
			if ($value['commentRank']=="无意义") continue;
			if ($value['title']== '0' || !$value['title']) continue;
			// dump($value['@attributes']["key"]." ".$value['title']);
			foreach ($value as $k => $v) {

				
				if ($k=="@attributes") {
					$data['key'] = $v['key'];
					$data['mdate'] = $v['mdate'];
					continue;
				}
				if(!in_array(strtolower($k),$fields)) continue;
				$data[strtolower($k)] = $v;
			}
			#保存数据 并记录日志
			$num = $this->monitor_model->saveData($data,"`key`='".$data['key']."'");
			$message = " startTime:".$date." key:".$data['key']." result:".$num;
			$message = ($num>=1 || $num===0) ?  "success:".$message : "failure:".$message;
			$this->write_log("monitor.log",$message);
		}

		return $jsonArray['@attributes']['numFound'];
	}
}