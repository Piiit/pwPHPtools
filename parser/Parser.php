<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/tree/Node.php';
require_once INC_PATH.'pwTools/tree/TreeWalkerConfig.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';

class Parser implements TreeWalkerConfig { 
	
	private $_handlerTable = array();
	private $_array = array();
	private $_userInfo = array();
	
	public function setUserInfo($key, $value) {
		$this->_userInfo[$key] = $value;
	}
	
	public function getUserInfoOrNew($key, $valueIfNotExists) {
		try {
			return $this->getUserInfo($key);
		} catch (Exception $e) {
			$this->setUserInfo($key, $valueIfNotExists);
		}
		return $valueIfNotExists;
	}
	
	public function getUserInfo($key) {
		if(!$this->isUserInfo($key)) {
			throw new Exception("UserInfo '$key' does not exist!");
		}
		return $this->_userInfo[$key];
	}
	
	public function isUserInfo($key) {
		return array_key_exists($key, $this->_userInfo);
	}
		
	public function registerHandler(ParserRuleHandler $tokenHandler) {
		if(strlen($tokenHandler->getName()) == 0) {
			throw new Exception("Cannot register a handler without a name!");
		} 
		if(array_key_exists($tokenHandler->getName(), $this->_handlerTable)) {
			throw new Exception("Parser token '".$tokenHandler->getName()."' already registered!");
		}
		$this->_handlerTable[$tokenHandler->getName()] = $tokenHandler;
	}
	
	public function registerHandlerList($handlerList) {
		if(!is_array($handlerList)) {
			throw new Exception("Handler list must be an array!");
		}
		foreach($handlerList as $handler) {
			$this->registerHandler($handler);
		}
	}
	
	private function getParserToken($name) {
		if(!array_key_exists($name, $this->_handlerTable)) {
			throw new Exception("Parser Token '$name' does not exist!");
		}
		return $this->_handlerTable[$name];
	}
	
	public function callBefore(Node $node) {
		if($node->getName() == "#EOF") {
			return;
		}
		if ($node->getName() == "#TEXT") {
			$this->_array[] = pw_s2e($node->getData());
			return;
		} 
		$parserToken = $this->getParserToken($node->getName());
		$parserToken->setNode($node);
		$parserToken->setParser($this);
		$ret = $parserToken->onEntry();
		if ($ret !== null) {
			$this->_array[] = $ret;
		}
	}

	public function callAfter(Node $node) {
		if ($node->getName() == "#TEXT" || $node->getName() == "#EOF") {
			return;
		}
		$parserToken = $this->getParserToken($node->getName());
		$parserToken->setNode($node);
		$parserToken->setParser($this);
		$ret = $parserToken->onExit();
		if ($ret !== null) {
			$this->_array[] = $ret;
		}
	}

	public function getResult() {
		return $this->_array;
	}
	
	public function resetResult() {
		$this->_array = array();
	}
	
	public function setResult($resultArray) {
		$this->_array = $resultArray;
	}
	
	public function doRecursion(Node $node) {
		if ($node->getName() == "#TEXT" || $node->getName() == "#EOF") {
			return;
		}
		$parserToken = $this->getParserToken($node->getName());
		$parserToken->setNode($node);
		$parserToken->setParser($this);
		return $parserToken->doRecursion();
	}
}

?>