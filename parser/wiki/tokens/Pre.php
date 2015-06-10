<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandlerAbstract.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Pre extends ParserRule implements ParserRuleHandler, LexerRuleHandlerAbstract {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		return '<pre><div>';
	}

	public function onExit() {
		return '</div></pre>';
	}

	public function doRecursion() {
		return true;
	}
	
	public function getConnectTo() {
		return array("preformat");
	}

}

?>