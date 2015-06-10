<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';
require_once INC_PATH.'pwTools/validator/Validator.php';


class ExternalLink extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		
		$node = $this->getNode();
		$urlnode = $node->getFirstChild();
		$url = $this->getTextFromNode($urlnode);
		
		if (!Validator::isURL($url)) {
			return "[".$this->getTextFromNode($node)."]";
		}
	
		//TODO Start with $urlnode and walk to the end...
		//TODO What if constants are used or variables within a link?
		if($urlnode->getNextSibling()) {
			$txt = $urlnode->getNextSibling()->getData();
		} else {
			$txt = $url;
		}

		$target = 'target="_blank" ';
		if (substr($url, 0, 7) == "mailto:") {
			$target = "";
		}
	
		return '<a '.$target.'href="'.$url.'">'.$txt.'</a>';
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return false;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '(?=\[(?!\[))', '\]');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "tablecell", "listitem", "multiline", "bold", "underline", 
				"italic", "monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", 
				"em", "bordererror", "borderinfo", "borderwarning", "bordersuccess", "bordervalidation", "border", 
				"tablecell", "tableheader", "wptableheader", "wptablecell", "align", 
				"justify", "alignintable", "indent", "left", "right", "footnote", "defitem", "defterm");
	}
}

?>