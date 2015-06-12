<?php

class Plugin extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '{{([\w\.]*)', '}}');
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
		
		/*
		 * Every command consists of several names separated by a '.' (dot).
		 * First part is the plugin name, the following parts define categories.
		 */
		$fqnList = explode(".", strtolower($nodeData[0]));
		$pluginName = $fqnList[0];
		array_shift($fqnList);
		$pluginCategories = $fqnList;
				
		/*
		 * The class name instance is always of the pattern: PluginPluginName.
		 */
		$className = "Plugin".ucfirst($pluginName);
		
		if(class_exists($className)) {
			$plugin = new $className();
		} else {
			return nop("PLUGIN '".$className."' not found.");
		}
		
		return $plugin->run(
			$this->getParser(), 
			$node, 
			$pluginCategories, 
			$node->getNodesByName("pluginparameter")
		);
	}
		
	public function onExit() {
	}

	public function doRecursion() {
		return false;
	}

}

?>