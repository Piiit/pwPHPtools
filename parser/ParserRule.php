<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/tree/Node.php';
require_once INC_PATH.'pwTools/parser/Parser.php';
require_once INC_PATH.'pwTools/tree/TreeWalker.php';


class ParserRule {
	
	private $node;
	private $parser;
	
	public function __construct($node = null, $parser = null) {
		if($node != null) {
			$this->setNode($node);
		}
		if($parser != null) {
			$this->setParser($parser);
		}
	}
	
	public function setNode(Node $node) {
		$this->node = $node;
	}
	
	public function getNode() {
		return $this->node;
	}
	
	public function setParser(Parser $parser) {
		$this->parser = $parser;
	}
	
	public function getParser() {
		return $this->parser;
	}
	
	public function getText() {
		return implode($this->getArray());
	}
	
	public function getArray() {
		return $this->getArrayFromNode($this->node);
	}
	
	public function getTextFromNode(Node $node) {
		return implode($this->getArrayFromNode($node));
	}
	
	public function getArrayFromNode(Node $node) {
		$ta = new TreeWalker($node, $this->parser);
		$tmp = $this->parser->getResult();
		$this->parser->resetResult();
		$result = $ta->getResult();
		$this->parser->setResult($tmp);
		return $result;
	}
	
}

?>