<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'piwo-v0.2/lib/common.php';
// require_once INC_PATH.'piwo-v0.2/lib/plugins/toc.php';
require_once INC_PATH.'piwo-v0.2/cfg/main.php';
require_once INC_PATH.'pwTools/parser/wiki/WikiTocTools.php';
require_once INC_PATH.'pwTools/parser/Lexer.php';
require_once INC_PATH.'pwTools/parser/Parser.php';
require_once INC_PATH.'pwTools/tree/TreePrinter.php';

class WikiParser {
	private $lexer = null;
	private $parser = null;
	private $result = null;
	private $pluginList = array();
	
	
	public function __construct($pathUserDefinedTokens = null) {
		$this->lexer = new Lexer();
		$this->parser = new Parser();
		
		/*
		 * Tokens are build-in recognition pattern for this wiki parser, whereas
		 * plugins are user-defined additional patterns.
		 */
		$pathTokens = realpath(dirname(__FILE__).'/tokens').'/';
		$pathPlugins = realpath(dirname(__FILE__).'/plugins').'/';
		
		// include all parser token handlers...
		if (!is_dir($pathTokens)) {
			throw new Exception("'".$pathTokens."' is not a valid token path!");
		}
		
		//Plugins are optional
		if (!is_dir($pathPlugins)) {
			throw new Exception("'$pathPlugins' is not a valid plugin path!");
		}
		
		$handlerList = array_merge(glob($pathTokens."*.php"), glob($pathPlugins."*.php"));
		TestingTools::debug($handlerList);
		
		$handlerListAbstract = array();
		
		foreach ($handlerList as $handler) {
			require_once $handler;
			$className = FileTools::basename($handler, ".php");
			
			if (class_exists($className)) {
				$class = null;
				TestingTools::debug("TEST: ".$className);
				$interfaces = class_implements($className);
				if (array_search('LexerRuleHandler', $interfaces)) {
					$class = new $className;
					$this->lexer->registerHandler($class);
				}
				if (array_search('LexerRuleHandlerAbstract', $interfaces)) {
					$class = ($class == null ? new $className : $class);
					$handlerListAbstract[] = new $class;
				}
				if (array_search('ParserRuleHandler', $interfaces)) {
					$class = ($class == null ? new $className : $class);
					$this->parser->registerHandler($class);
				}
				if (array_search('WikiPluginHandler', $interfaces)) {
					$class = ($class == null ? new $className : $class);
					$this->pluginList[] = $class;
				}
			}
		}
		
		
		$this->lexer->registerHandlerList($handlerListAbstract);
	}
	
	public function parse($text) {
		$this->lexer->setSource($text);
		$this->lexer->parse();
		
		/*
		 * User information passed to the wiki parser to be accessed from 
		 * syntax handlers.
		 */
		$this->setUserInfo('lexer.performance', $this->lexer->getExecutionTime());
		$this->setUserInfo('lexer.version', $this->lexer->getVersion());
		$this->setUserInfo('file.type', FileTools::getTextFileFormat($text)->getString());
		
		foreach($this->pluginList as $pluginHandler) {
			$pluginHandler->runBefore($this->parser, $this->lexer);
		}
		
		$treeWalker = new TreeWalker($this->lexer->getRootNode(), $this->parser);
		$this->result = implode($treeWalker->getResult());
		
		foreach($this->pluginList as $pluginHandler) {
			$pluginHandler->runAfter($this->parser, $this->lexer);
		}
		
	}
	
	public function getSource() {
		return $this->lexer->getSource();
	}
	
	public function getResult() {
		if($this->result == null) {
			throw new Exception("No parsed results found!");
		}
		return $this->result;
	}
	
	public function setUserInfo($key, $value) {
		$this->parser->setUserInfo($key, $value);
	}
	
	public function getLexer() {
		return $this->lexer;
	}

	public function getParser() {
		return $this->parser;
	}
	
}

?>