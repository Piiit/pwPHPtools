<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Hi extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		$nodeData = $this->getNode()->getData();
		$colorid = trim($nodeData[0]);
  		$colors = array("orange" => 0, "green" => 1, "yellow" => 2, "red" => 3, "blue" => 4);

  		if (is_string($colorid) && array_key_exists($colorid, $colors)) {
    		$colorid = $colors[$colorid];
  		}

 		if (!is_numeric($colorid) || $colorid < 0 || $colorid > 4) {
    		$colorid = 0;
  		}

  		return '<span class="highlighted c'.$colorid.'">';
	}

	public function onExit() {
		return '</span>';
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '<hi( *[\w]*)>', '<\/hi>');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "defitem", "footnote", "align", "justify", "alignintable", "indent", "left", "right",
				"tablecell", "tableheader", "wptableheader", "wptablecell", "tablecell", "listitem", "multiline", 
				"bordererror", "borderinfo", "borderwarning", "bordersuccess", "bordervalidation", "border",
				"bold", "underline", "italic", "monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", "em");
	}
}

?>