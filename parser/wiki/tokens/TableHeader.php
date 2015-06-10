<?php

class TableHeader extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		$o = "";
  		$rowspan = TableCell::getRowspanText($this->getNode());
  		$colspan = TableCell::getColspanText($this->getNode());

  		if($this->getNode()->hasChildren()) {
	  		if ($this->getNode()->getFirstChild()->getName() !== "tablespan") {
	    		$o = '<th'.$rowspan.$colspan.'>';
	    		$o .= $this->getText();
	    		$o .= '</th>';
	  		}
  		}
  		return $o;
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return false;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '\^', '(?=\||\^|\n)'); 
	}
	
	public function getAllowedModes() {
		return array("tablerow");
	}
}

?>