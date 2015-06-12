<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/wiki/WikiPluginHandler.php';

class PluginToc implements WikiPluginHandler {

	public function getPluginName() {
		return "toc";
	}

	public function runBefore(Parser $parser, Lexer $lexer) {
		$parser->setUserInfo(
				'indextable',
				WikiTocTools::createIndexTable($parser, $lexer->getRootNode())
		);
	}

	public function runAfter(Parser $parser, Lexer $lexer) {
	}

	public function run(Parser $parser, Node $node, $categories, $parameters) {
		return "PLUGIN ".$this->getPluginName().implode(".", $categories);

	}

}