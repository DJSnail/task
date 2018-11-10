<?php
/************************************************************
** @Description: 用户model
** @Author: haodaquan
** @Date:   2016-06-03 12:21:01
** @Last Modified by:   xiyou_zlg
** @Last Modified time: 2017-08-01 13:06:56
*************************************************************/
if (! defined ( 'BASEPATH' ))
	exit ( 'No direct script access allowed' );
class Category_model extends MY_Model {
	public $_table = 'pb_category';
	public function __construct() {
		parent::__construct ();
	}
	private $_category_tree;
	
	public function queryList($param) {
		$map = $this->queryParam ( $param );
		$totalCount = $this->getCount ( $map ['where'] );
		return $this->returnData ( 200, 'success', $this->getCategoryTree(), $totalCount );
	}
	
	public function getCategoryTree() {
		$list = $this->getConditionData ( '*', 'is_del=0', 'sort ASC' );
		$items = [ ];
		foreach ( $list as $v ) {
			$items [$v ['id']] = $v;
		}
		$list_tree = $this->arrayToTree ( $items );
		$this->exportTree ( $list_tree, 0 );
		return $this->_category_tree;
	}
	
	/**
	 * 数组转树形结构
	 */
	function arrayToTree(array $items) {
		foreach ( $items as $item ) {
			$items [$item ['parent_id']] ['child'] [$item ['id']] = &$items [$item ['id']];
		}
		return isset ( $items [0] ['child'] ) ? $items [0] ['child'] : array ();
	}
	
	/**
	 * 将树形结构数组输出
	 */
	function exportTree($items, $deep = 0) {
		
		foreach ( $items as $item ) {
			
			if ($deep == 0) {
				$item ['category_name'] = str_repeat ( ' -- ', $deep ) . $item ['category_name'];
			} else {
				$item ['category_name'] = '|' . str_repeat ( ' -- ', $deep ) . $item ['category_name'];
			}
			
			$child = empty ($item ['child']) ? [] : $item ['child'];
			$item ['allow_del'] = empty ($child) ? true : false;
			unset($item['child']);
			$this->_category_tree [] = $item;

			if (! empty ($child)) {
				$this->exportTree ($child, $deep + 1 );
			}
		}
		
	}
}
