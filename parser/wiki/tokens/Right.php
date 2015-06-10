<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Right extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		return '<div class="section_right">';
	}

	public function onExit() {
		$o = '</div>';
  		$nsName = $this->getNode()->getNextSibling()->getName();
  		$nodeData = $this->getNode()->getData();
  		$cfg = isset($nodeData[1]) ? $nodeData[1] : null;
  		if ($nsName != 'left' && $cfg != "alone") {
    		$o .= '<div class="clear"></div>';
  		}
  		return $o;
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '<right( *)(alone)*>', '<\/right>');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT");
	}
}

?>