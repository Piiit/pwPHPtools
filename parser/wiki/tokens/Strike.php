<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Strike extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		return '<span class="strike">';
	}

	public function onExit() {
		return '</span>';
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '\-\+', '\+\-');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "defitem", "footnote", "align", "justify", "alignintable", "indent", "left", "right",
				"tablecell", "tableheader", "wptableheader", "wptablecell", "tablecell", "listitem", "multiline", 
				"bordererror", "borderinfo", "borderwarning", "bordersuccess", "bordervalidation", "border",
				"bold", "underline", "italic", "monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", "em");
	}
}

?>