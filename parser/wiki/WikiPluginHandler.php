<?php

interface WikiPluginHandler {
	public function getPluginName();
	public function runBefore(Parser $parser, Lexer $lexer);
	public function runAfter(Parser $parser, Lexer $lexer);
	public function run(Parser $parser, Node $node, $pluginMethod, Array $parameters);
}

?>