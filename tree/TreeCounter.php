<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/tree/TreeWalker.php';
require_once INC_PATH.'pwTools/tree/TreeWalkerConfig.php';

class TreeCounter implements TreeWalkerConfig {
	
	private $_count = 1;  // 1, count given node first.
	
	public function callBefore($node) {
		$this->_count++;
	}
	
	public function callAfter($node) {}
	
	public function getResult() {
		return $this->_count;
	}
	
	public function doRecursion(Node $node = null) {
		return true;
	}

}