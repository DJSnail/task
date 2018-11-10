<?php
/************************************************************
** @Description: 临时使用redis 队列
** @Author: haodaquan
** @Date:   2016-06-16 13:32:35
** @Last Modified by:   haodaquan
** @Last Modified time: 2016-06-21 11:03:31
*************************************************************/

class Redislibrary
{
	public $connect;

	public function __construct()
	{
		$this->connect = new redis();
		#测试库
		//$this->connect->connect('10.32.35.77',6379);
		if(ENVIRONMENT=='production')
		{
			#链接阿里云
			$this->connect->connect('1e339fa0807d428d.m.cnbja.kvstore.aliyuncs.com',6379);
			$this->connect->auth('1e339fa0807d428d:43F01e4Ab');
		}else {
			$this->connect->connect('10.32.33.62',6379);
		}
	}



	

	/**
	 * [set 压入队列数据]
	 * @Date  2016-06-16
	 * @param [type]     $queueName [队列名称]
	 * @param [type]     $value     [队列值]
	 * @param string     $fun       [压入方法 lpush-队首，rpush-队尾]
	 * @return [type]                [数据条数，>1表示成功]
	 */
	public function set($queueName,$value,$fun="lpush")
	{
		return  $this->connect->$fun($queueName,$value);
	}

	/**
	 * [get 获取一条数据，并删除]
	 * @Date   2016-06-16
	 * @param  [type]     $queueName [队列名称]
	 * @param  string     $fun       [获取位置，rpop-队尾，lpop-队首]
	 * @return [type]                [json 数据]
	 */
	public function get($queueName,$fun="rpop")
	{
		return $this->connect->$fun($queueName);
	}
}