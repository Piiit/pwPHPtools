<?php
if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

require_once INC_PATH.'pwTools/tree/Node.php';
require_once INC_PATH.'pwTools/tree/TreeWalkerConfig.php';

class TreeWalker {
	private $_rootNode = null;
	private $_treeWalkerConfig = null;

	public function __construct(Node $rootNode, TreeWalkerConfig $treeWalkerConfig) {
		$this->_rootNode = $rootNode;
		$this->_treeWalkerConfig = $treeWalkerConfig;
	}
	
	public function getResult() {
		$this->_treeWalker($this->_rootNode, null);
		return $this->_treeWalkerConfig->getResult();
	}
	
	private function _treeWalker(Node $node) {
		if ($node->hasChildren()) {
			for ($node = $node->getFirstChild(); $node != null; $node = $node->getNextSibling()) {
				if (!$node instanceof Node) {
					throw new Exception("TreeWalker-Nodes must be an instance of Node!");
				}
				$this->_treeWalkerConfig->callBefore($node);
				if($this->_treeWalkerConfig->doRecursion($node)) {
					$this->_treeWalker($node);
				}
				$this->_treeWalkerConfig->callAfter($node);
			}
		}
	}
	
}