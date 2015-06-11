<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/wiki/WikiPluginHandler.php';

// TODO Implement a PluginProvider/PluginConsumer interface
class Plugin extends ParserRule implements ParserRuleHandler, LexerRuleHandler, WikiPluginHandler {
	
	private $pluginList = array();
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '{{([\w]*)(\.[\w]*)?', '}}');
	}
	
	public function getAllowedModes() {
		return array(
				"#DOCUMENT", "tablecell", "listitem", "multiline", "bold", "underline", "italic", 
				"monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", "em",
				"multiline", "header", "internallinkpos", "internallinktext", "externallink", "externallinkpos", 
				"tablecell", "tableheader", "wptableheader", "wptablecell", "align", "justify", 
				"alignintable", "indent", "left", "right", "bordererror", "borderinfo", "borderwarning", 
				"bordersuccess", "bordervalidation", "border", "pluginparameter"
				);
	}
	
	public function onEntry() {
		$node = $this->getNode();
		$nodeData = $node->getData();
		$pluginName = strtolower($nodeData[0]);
		
		/*
		 * Remove first character (i.e., the leading dot) if the plugin has a 
		 * specified method. Note: This could be avoided if the lexer would 
		 * support "OR" connected patterns.
		 */
		$methodName = count($nodeData) <= 1 ? null : substr(strtolower($nodeData[1]), 1);
		$className = "Plugin".ucfirst($pluginName);
		
		$plugin = null;
		
		/*
		 * Check if the plugin has already been loaded before, if not include 
		 * the plugin class and create an instance of it.
		 */
		if(in_array($pluginName, $this->pluginList)) {
			$plugin = new $className();
		} else {
			$pluginPath = realpath(dirname(__FILE__).'/../plugins').'/';
			$filename = $className.".php";
			
			if (!file_exists($pluginPath.$filename)) {
				//throw new Exception("PLUGIN '$pluginName' not found in '".$pluginPath."'.");
				return nop("PLUGIN '$pluginName' not found in '".$pluginPath."'.");
			}
			
			require_once INC_PATH."pwTools/parser/wiki/plugins/".$filename;
			$plugin = new $className();
			
			$interfaces = class_implements($plugin);
			if (!array_search('WikiPluginHandler', $interfaces)) {
				throw new Exception("palasdf");
				return nop("PLUGIN '$pluginName' not found in '".$pluginPath."'.");
			}
			
			
			$this->pluginList[$className] = $plugin;
		}
		
		return $plugin->run($this->getParser(), $methodName, $node->getNodesByName("pluginparameters"));
	}

	public function onExit() {
	}

	public function doRecursion() {
		return false;
	}

 	public function runBefore(Parser $parser, Lexer $lexer) {
 		TestingTools::inform("Run Before: setting indextable.");
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

	public function run(Parser $parser, $pluginMethod, Array $parameters) {
	}

}

?>