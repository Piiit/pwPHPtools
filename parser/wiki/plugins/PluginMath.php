<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/wiki/WikiPluginHandler.php';

//TODO Math-plugin: define i18n comma/dot notation
class PluginMath implements WikiPluginHandler {
	
	public function getPluginName() {
		return "math";
	}

	public function runBefore(Parser $parser, Lexer $lexer) {
	}

	public function runAfter(Parser $parser, Lexer $lexer) {
	}

	public function run(Parser $parser, Node $node, $categories, $parameters) {
	
		if ($categories == null) {
			return nop("Plugin '".$this->getPluginName()."': No default command specified.");
		}
		
		$out = null;
	    switch ($categories[0]) {
			case 'pi':
				$out = round(pi(), 5);
			break;
			case 'e':
				$out = round(2.718281828459045235, 5);
			break;
		}
	
	    if ($out == null) {
	    	return nop("Plugin '".$this->getPluginName()."': No method '".implode(".", $categories)."' found.");
	    }
	    
	    $out = pw_s2e($out);
	    return $out;

	}

}