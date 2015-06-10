<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Url extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		$node = $this->getNode();
		$nodeData = $node->getData();
		$url = $nodeData[0];
  		$target = 'target="_blank" ';
  		$mailto = substr($url, 0, 7) == "mailto:" ? true : false;
  		if ($mailto) {
    		$target = "";
  		}

  		return '<a '.$target.'href="'.$url.'">'.$url.'</a>';
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_WORD, '([\w]+?:\/\/.*?[^ \"\n\r\t<\]]*)');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "tablecell", "listitem", "multiline", "bold", "underline", "italic", 
				"monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", "em", "externallink", 
				"footnote", "defitem", "bordererror", "borderinfo", "borderwarning", "bordersuccess", 
				"bordervalidation", "border");
	}
}

?>