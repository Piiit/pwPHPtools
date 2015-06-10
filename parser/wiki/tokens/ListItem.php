<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class ListItem extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public static $listitems;
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		$o = "";
		$node = $this->getNode();
		$nodeData = $node->getData();
		
		$oldlevel = 0;
		if ($node->getPreviousSibling() !== null) {
			$psData = $node->getPreviousSibling()->getData();
			$oldlevel = strlen($psData[0]) / 2;
			TestingTools::inform($psData);
		}
		
		$thislevel = strlen($nodeData[0]) / 2;
		if ($oldlevel < $thislevel) {
			$difflevel = $thislevel - $oldlevel;
			
			for ($i = 0; $i < $difflevel; $i++) {
				$listtype = $nodeData[1] == "#" ? '<ol>' : '<ul>';
				self::$listitems[] = $nodeData[1];
				$o .= $listtype;
			}
		} 
		
		$o .= "<li>";
		return $o;
	}

	public function onExit() {
		$o = "</li>";
		$node = $this->getNode();
  		$ns = $node->getNextSibling();
  		if ($ns != null) {

  			$nodeData = $node->getData();
  			$nsData = $ns->getData();
    		$thislevel = strlen($nodeData[0]) / 2;
    		$nextlevel = strlen($nsData[0]) / 2;

    		TestingTools::inform($nextlevel." ".$thislevel);
    		if ($nextlevel < $thislevel) {
      			$difflevel = $thislevel - $nextlevel;
      			for ($i = 0; $i < $difflevel; $i++) {
        			$listtype = array_pop(self::$listitems);
        			$listtype = $listtype == "#" ? '</ol>' : '</ul>';
        			$o .= $listtype;
      			}
    		}
  		}
  		return $o;
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '\n( *)([\*\#]) ', '\n');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "multiline", "left", "right", "wptablecell");
	}
}

?>