<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Symbol extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		$nodeData = $this->getNode()->getData();
		$symbol = $nodeData[0];

  		//TODO change style only in class=math
  		#echo '<span class="symbols">&'.$symbol.';</span>';
  		return '&'.$symbol.';';
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_WORD, '&(\#*[a-zA-Z0-9]{2,9});');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "tablecell", "listitem", "multiline", "bordererror", "borderinfo", "borderwarning", 
				"bordersuccess", "bordervalidation", "border", "bold", "underline", "italic", "monospace", "small", "big", 
				"strike", "sub", "sup", "hi", "lo", "em", "tablecell", "tableheader", "wptableheader", "wptablecell", "notoc",
				"align", "justify", "alignintable", "indent", "left", "right", "math", "defitem", "defterm");
	}
}

?>