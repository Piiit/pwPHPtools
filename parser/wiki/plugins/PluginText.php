<?php

class PluginText extends Plugin implements WikiPluginHandler {
	
	public function getPluginName() {
		return "text";
	}

	public function runBefore(Parser $parser, Lexer $lexer) {
	}

	public function runAfter(Parser $parser, Lexer $lexer) {
	}

	public function run(Parser $parser, $pluginMethod, Array $parameters) {
		return "PLUGIN ".$this->getPluginName().$pluginMethod;

	}

}