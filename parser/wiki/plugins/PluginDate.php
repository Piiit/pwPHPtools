<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/wiki/WikiPluginHandler.php';

 /*
  * Date and time functions.
  * TODO Date and time functions add i18n functionality.
  * TODO Date and time functions make formats configurable
  */
class PluginDate implements WikiPluginHandler {
	
	public function getPluginName() {
		return "date";
	}

	public function runBefore(Parser $parser, Lexer $lexer) {
	}

	public function runAfter(Parser $parser, Lexer $lexer) {
	}

	public function run(Parser $parser, Node $node, $categories, $parameters) {
	
		if ($categories == null) {
			throw new Exception("Plugin '".$this->getPluginName()."': No default command specified.");
		}
		
		$out = null;
	    switch ($categories[0]) {

	    	case 'now':
	    		$out = date('d.m.Y');
	    		break;
	    	case 'month':
	    		$out = date('m');
	    		break;
	    	case 'monthname':
	    		$out = date('F');
	    		break;
	    	case 'day':
	    		$out = date('d');
	    		break;
	    	case 'dayname':
	    		$out = date('l');
	    		break;
	    	case 'year':
	    		$out = date('Y');
	    		break;
	    	case 'time':
	    		$out = date('H:i');
	    		break;
		}
	
	    if ($out == null) {
	    	return nop("Plugin '".$this->getPluginName()."': No method '".implode(".", $categories)."' found.");
	    }
	    
	    $out = pw_s2e($out);
	    return $out;

	}

}