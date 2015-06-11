<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/tree/Node.php';


//TODO make PLUGINS object oriented!
function plugin_text(Parser $parser, Node $node) {

  	$nodeData = $node->getData();
    $func = utf8_strtolower($nodeData[1]);
    $token = new ParserRule($node, $parser);
    $text = pw_e2u($token->getText());

    switch ($func) {
      	case "ucfirst":
	        $out = utf8_ucfirst($text);
      	break;
	    case "ucwords":
	      	$out = utf8_ucwords($text);
	    break;
	    case "toupper":
	        $out = utf8_strtoupper($text);
      	break;
      	case "tolower":
        	$out = utf8_strtolower($text);
      	break;
      	case "comma":
        	$out = str_replace(".", ",", $text);
      	break;
    	default: 
    		return nop("Funktion '$func' im Plugin 'TEXT' nicht vorhanden.", false);
    }

    $out = pw_s2e($out);
    return $out;
}

?>