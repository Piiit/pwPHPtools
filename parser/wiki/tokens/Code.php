<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class Code extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '<code( *[\w ]*)>', '<\/code>');
	}
	
	public function onEntry() {
  		$text = pw_s2e($this->getNode()->getFirstChild()->getData());
  		return '<pre><div>'.utf8_trim($text, "\n");
	}

	public function onExit() {
		return '</div></pre>';
	}

	public function doRecursion() {
		return false;
	}
	
 	public function getAllowedModes() {
 		return array(
 				"#DOCUMENT", "tablecell", "listitem", "multiline", "left", "right", "indent", 
 				"bordererror", "borderinfo", "borderwarning", "bordersuccess", "bordervalidation", 
 				"border", "bold", "underline", "italic", "monospace", "small", "big", "strike", 
 				"sub", "sup", "hi", "lo", "em"
 				);
	}

}

?>