<?php

class Lists extends ParserRule implements ParserRuleHandler, LexerRuleHandlerAbstract {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		ListItem::$listitems = array();
		$node = $this->getNode();
  		$fc = $node->getFirstChild();
  		$fcData = $fc->getData();
  		$listtype = $fcData[1] == "#" ? '<ol>' : '<ul>';
  		ListItem::$listitems[] = $fcData[1];
  		return $listtype;
	}

	public function onExit() {
		$o = "";
  		$lclevel = count(ListItem::$listitems);
  		for ($i = 0; $i < $lclevel; $i++) {
    		$listtype = array_pop(ListItem::$listitems);
    		$listtype = $listtype == "#" ? '</ol>' : '</ul>';
    		$o .= $listtype;
  		}
  		return $o;
	}

	public function doRecursion() {
		return true;
	}
	
	public function getConnectTo() {
		return array("listitem");
	}

}

?>