<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class BorderWarning extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '<warning>', '<\/warning>');
	}
	
	public function onEntry() {
		return '<div class="section_warning">';
	}

	public function onExit() {
		return '</div>';
	}

	public function doRecursion() {
		return true;
	}
	
 	public function getAllowedModes() {
 		return array("#DOCUMENT", "multiline", "left", "right", "indent", "tablecell");
	}

}

?>