<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/parser/wiki/WikiPluginHandler.php';

class PluginWiki implements WikiPluginHandler {
	
	public function getPluginName() {
		return "wiki";
	}

	public function runBefore(Parser $parser, Lexer $lexer) {
		/*
		 * User information passed to the wiki parser to be accessed from
		 * syntax handlers.
		 */
		$parser->setUserInfo('wiki.file.type', $lexer->getOrigTextFileFormat()->getString());
		$parser->setUserInfo('wiki.lexer.performance', $lexer->getExecutionTime());
	}

	public function runAfter(Parser $parser, Lexer $lexer) {
	}

	public function run(Parser $parser, Node $node, $categories, $parameters) {
	    
		if ($categories == null) {
			return nop("Plugin '".$this->getPluginName()."': No default command specified.");
		}
		
		$out = null;
	    switch ($categories[0]) {
	    	
	    	/*
	    	 * Wiki and Parser functions.
	    	 */
	      	case "version": 
	      		$out = $parser->getUserInfo('piwoversion'); 
	      	break;
		    case 'ns': 
		    	if(! isset($categories[1])) {
					$id = pw_wiki_getid();
					$out = pw_url2u($id->getNS());
		    	} else {
		    		switch ($categories[1]) {
		    			case 'countsubs':
							/*
							 * Count all wikipages within the current namespace.
							 */
							$path = FileTools::dirname($_SERVER['PHP_SELF']);
							$out = count(glob($path."/*".WIKIFILEEXT));
						break;
		    		}
		    	}
			break;
			case 'fullns': 
				$id = pw_wiki_getid();
				$out = pw_url2u($id->getFullNS()); 
			break;
			case 'page':
				$id = pw_wiki_getid();
				$out = pw_url2u($id->getPage()); 
			break;
			case 'wrongid':
				try { 
					$wrongId = pw_wiki_getcfg('wrongid');
					$out = pw_url2u($wrongId->getID());
				} catch (Exception $e) {
					$out = "";
				} 
			break;
			case 'id':
				$id = pw_wiki_getid(); 
				$out = pw_url2u($id->getID()); 
			break;
			case 'startpage': 
				$out = ':'.pw_url2u(WIKINSDEFAULTPAGE); 
			break;
			
			
			/*
			 * Lexer functions.
			 */
			case "lexer":
				$out = $this->catLexer(array_slice($categories, 1), $parser);
			break;
			
			/*
			 * File functions.
			 */
			case "file":
				$out = $this->catFile(array_slice($categories, 1), $parser);
			break;
			
	    }
	    
	    if ($out === null) {
	    	return nop("Plugin '".$this->getPluginName()."': No method '".implode(".", $categories)."' found.");
	    }
	    
	    $out = pw_s2e($out);
	    return $out;

	}
	
	public function catLexer($cat, Parser $parser) {
		switch ($cat[0]) {
			case 'version': 
				return Lexer::getVersion();
			case 'performance':
				return $parser->getUserInfo("wiki.lexer.performance");
		}
		return null;
	}
	
	public function catFile($cat, Parser $parser) {
		switch ($cat[0]) {
			case 'type':
				return $parser->getUserInfo("wiki.file.type");
			case 'path':
				return 'http://'.$_SERVER['SERVER_NAME'].FileTools::dirname($_SERVER['PHP_SELF']);
		}
		return null;
	}
	
}