<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/wiki/WikiPluginHandler.php';

// TODO Implement a PluginProvider/PluginConsumer interface
class Plugin extends ParserRule implements ParserRuleHandler, LexerRuleHandler, WikiPluginHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '~~([\w]+):*([\w]+)*', '~~');
	}
	
	public function getAllowedModes() {
		return array(
				"#DOCUMENT", "tablecell", "listitem", "multiline", "bold", "underline", "italic", 
				"monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", "em",
				"multiline", "header", "internallinkpos", "internallinktext", "externallink", "externallinkpos", 
				"tablecell", "tableheader", "wptableheader", "wptablecell", "align", "justify", 
				"alignintable", "indent", "left", "right", "bordererror", "borderinfo", "borderwarning", 
				"bordersuccess", "bordervalidation", "border"
				);
	}
	
	public function onEntry() {
		$node = $this->getNode();
		$nodeData = $node->getData();
		$pluginname = strtolower($nodeData[0]);

		//TODO make plugins object oriented...
		$funcname = "plugin_".$pluginname;
		if (!function_exists($funcname)) {
			return nop("PLUGIN '$pluginname' not found.",false);
		}
		return call_user_func($funcname, $this->getParser(), $node);

		//TODO
// 		return nop("PLUGIN '$pluginname' not found.",false);
	}

	public function onExit() {
	}

	public function doRecursion() {
		return false;
	}

	public function runBefore(Parser $parser, Lexer $lexer) {
		$parser->setUserInfo(
				'indextable', 
				WikiTocTools::createIndexTable($parser, $lexer->getRootNode())
				);
	}

	public function runAfter(Parser $parser, Lexer $lexer) {
	}

	public function getPluginName() {
		return "toc";
	}

}

?>