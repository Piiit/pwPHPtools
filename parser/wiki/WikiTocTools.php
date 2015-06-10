<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/data/IndexTable.php';

class WikiTocTools {
	
	public static function createIndexTable(Parser $parser, Node $node) {
		$indextable = new IndexTable();
		self::_createindextable($parser, $node, $indextable);
		return $indextable;
	}
	
	private static function _createIndexTable(Parser $parser, Node $node, IndexTable $indextable = null) {
		if($node->hasChildren() && $node->getName() != "notoc") {
			for ($node = $node->getFirstChild(); $node != null; $node = $node->getNextSibling()) {
				if ($node->getName() == "header") {
					$token = new ParserRule($node, $parser);
					$text = utf8_trim(pw_e2u($token->getText($node)));
					$config = $node->getData();
					$level = utf8_strlen($config[0]);
					$indextable->add($level, $text);
				}
				self::_createindextable($parser, $node, $indextable);
			}
		}
	}
	
}

?>