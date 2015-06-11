<?php

class Plugin extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
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
		
		if(class_exists($className)) {
			$plugin = new $className();
		} else {
			return nop("PLUGIN '".$className."' not found.");
		}
		
		try {
			return $plugin->run($this->getParser(), $node, $methodName, $node->getNodesByName("pluginparameter"));
		} catch (Exception $e) {
			return $plugin->run($this->getParser(), $node, $methodName, array());
		}
	}

	public function onExit() {
	}

	public function doRecursion() {
		return false;
	}

}

?>