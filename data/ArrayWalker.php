<?php
if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

require_once INC_PATH.'pwTools/data/ArrayWalkerConfig.php';

class ArrayWalker {
	private $_array = null;
	private $_arrayWalkerConfig = null;
	private $_maxDepth = 0;

	public function __construct($array, ArrayWalkerConfig $arrayWalkerConfig, $maxDepth = 0) {
		$this->_array = $array;
		$this->_arrayWalkerConfig = $arrayWalkerConfig;
		$this->_maxDepth = $maxDepth;
	}
	
	public function getResult() {
		$this->_arrayWalker($this->_array, 0, 0, 0);
		return $this->_arrayWalkerConfig->getResult();
	}
	
	private function _arrayWalker($item, $key, $index, $depth) {
		if (is_array($item)) {
			$index = 0;
			foreach($item as $key => $value) {
				$this->_arrayWalkerConfig->callBefore($value, $key, $index);
				if($this->_arrayWalkerConfig->doRecursion($value, $key, $index) && ($depth <= $this->_maxDepth || $this->_maxDepth == 0)) {
					$this->_arrayWalker($value, $key, $index, $depth+1);
				}
				$this->_arrayWalkerConfig->callAfter($value, $key, $index);
				$index++;
			}
		}
	}
	
}