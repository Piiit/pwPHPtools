<?php

class Constant extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {

		$nodeData = $this->getNode()->getData();
  		$conf = pw_s2u($nodeData[0]);

  		//TODO do this with quoted string tokens...
  		$matches = array();
  		if (preg_match("#(.*) *= *(.*)#i", $conf, $matches)) {
    		$varname = utf8_strtolower(utf8_trim($matches[1]));
    		$value = utf8_trim($matches[2]);
    		Variable::$variables[$varname] = $value;
    		return;
  		}

  		$conf = utf8_strtolower($conf);

  		$txt = "";

		$varnames = explode(":", $conf);
		$varname = array_shift($varnames);
		
		switch($varname) {
			
			default:
// 				TestingTools::inform($varname);
				if (isset(Variable::$variables[$varname])) {
					$txt = Variable::$variables[$varname];
					$txt = self::unescape($txt);
					return $txt;
				} else {
					$_SESSION['pw_wiki']['error'] = true;
					return nop("VARIABLE '$varname' wurde nicht gesetzt.", false);
				}
	
			break;
		}
	
		return pw_s2e($txt);
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_WORD, '{xxx{([^{]*?)}}');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "tablecell", "listitem", "multiline", "bordererror", "borderinfo", "borderwarning", 
				"bordersuccess", "bordervalidation", "border", "bold", "underline", "italic", "monospace", "small", "big", 
				"strike", "sub", "sup", "hi", "lo", "em", "tablecell", "tableheader", "wptableheader", "wptablecell",
				"align", "justify", "alignintable", "indent", "left", "right", "pluginparam", "header", "internallinkpos", 
				"internallinktext", "externallink", "externallinkpos", "variable", "plugin", "pluginparameter"
				);
	}
	
	private static function unescape($txt) {
  		return str_replace(array('\"', '\>'), array('"', '&gt;'), $txt);
	}
}

?>