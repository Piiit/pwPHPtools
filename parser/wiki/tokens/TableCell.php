<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/LexerRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRuleHandler.php';
require_once INC_PATH.'pwTools/parser/ParserRule.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';

class TableCell extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		$o = "";
  		$rowspan = self::getRowspanText($this->getNode());
  		$colspan = self::getColspanText($this->getNode());

  		if($this->getNode()->hasChildren()) {
	  		if ($this->getNode()->getFirstChild()->getName() !== "tablespan") {
	    		$o = '<td'.$rowspan.$colspan.'>';
	    		$o .= $this->getText();
	    		$o .= '</td>';
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
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '\|', '(?=\||\^|\n)'); 
	}
	
	public function getAllowedModes() {
		return array("tablerow");
	}
	
	public static function getRowspanText(Node $node) {
		$rowspans = 0;

		// find table row
		$tablerow = $node->getParent();
		while($tablerow->getName() !== "tablerow") {
			$tablerow = $tablerow->getParent();
		}
		
		$nexttablerow = $tablerow->getNextSibling();
		
		while($nexttablerow && $nexttablerow->hasChildren()) {
		
			// check lower cells for tablespan tokens...
			$cell = $tablerow->getFirstChild();
			$lowercell = $nexttablerow->getFirstChild();
			while($cell !== $node) {
				$cell = $cell->getNextSibling();
				if($lowercell) {
					$lowercell = $lowercell->getNextSibling();
				}
			}
			
			if ($lowercell && $lowercell->hasChildren() && $lowercell->getFirstChild()->getName() == "tablespan") {
				$rowspans++;
				$nexttablerow = $nexttablerow->getNextSibling();
			} else {
				$nexttablerow = null;
			}
		}
		
		$rowspan = $rowspans == 0 ? '' : ' rowspan="'.($rowspans+1).'"';
		return $rowspan;
	}
	
	public static function getColspanText(Node $node) {
		$nx = $node->getNextSibling();
		$colspans = 0;
		while($nx && !$nx->hasChildren()) {
			$colspans++;
			$nx = $nx->getNextSibling();
		}
		$colspan = ($colspans == 0 || $colspans == 1) ? '' : ' colspan="'.$colspans.'"';
		return $colspan;
	}
}

?>