<?php
if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

require_once INC_PATH.'pwTools/data/ArrayWalker.php';
require_once INC_PATH.'pwTools/data/ArrayWalkerConfig.php';
require_once INC_PATH.'pwTools/string/StringTools.php';

class ArrayPrinter implements ArrayWalkerConfig {
	
	private $_text = "";
	private $_space = "";
	private $_size = 0;
	private static $_SPACES = "    ";

	public function callBefore($item, $key, $index) {
		if (is_array($item)) {
			$this->_size = count($item);
			$this->_text .= $this->_space.StringTools::getItemWithTypeAndSize($item, $key)."\n";
			$this->_space .= self::$_SPACES;
		} else {
			$this->_text .= $this->_space.StringTools::getItemWithTypeAndSize($item, $key)."\n";
		} 
	}

	public function callAfter($item, $key, $index) {
		if ($index == $this->_size - 1) {
			$this->_space = substr($this->_space, 0, (-1) * strlen(self::$_SPACES));
		}
	}

	public function getResult() {
		return rtrim($this->_text);
	}
	
	public function doRecursion($item, $key, $index) {
		return true;
	}
}