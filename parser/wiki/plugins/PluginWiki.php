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
		
		/*
		 * Create an index table to be used for headers and table of content
		 * plugin.
		 */
		$parser->setUserInfo(
				'indextable',
				WikiTocTools::createIndexTable($parser, $lexer->getRootNode())
		);
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
			
			/*
			 * The following methods build HTML to be shown, we do not want to
			 * encode htmlentities.
			 */
			case "toc":
				return $this->catToc(array_slice($categories, 1), $parser);
			case "trace":
				return $this->catTrace(array_slice($categories, 1), $parser);
			
	    }
	    
	    if ($out === null) {
	    	return nop("Plugin '".$this->getPluginName()."': No method '".implode(".", $categories)."' found.");
	    }
	    
	    $out = pw_s2e($out);
	    return $out;

	}
	
	function catTrace ($cat, Parser $parser) {
		$sep = ' &raquo; ';
	
		$id = pw_wiki_getid();
	
		if($id->getID() == ":".WIKINSDEFAULTPAGE || $id->getID() == ":") {
			$out = "Home";
		} else {
			$out = "<a href='?id=".pw_s2url(":".WIKINSDEFAULTPAGE)."'>Home</a>";
		}
	
		$current_namespace = "";
		foreach ($id->getFullNSAsArray() as $index => $namespace) {
			if($id->isNS() && $index == sizeof($id->getFullNSAsArray()) - 1) {
				$out .= $sep.pw_s2e(utf8_ucfirst($namespace));
			} else {
				$current_namespace .= $namespace.":";
				$out .= $sep."<a href='?id=:$current_namespace'>".pw_s2e(utf8_ucfirst($namespace))."</a>";
			}
		}
	
		if($id->isNS() || $id->getPage() == WIKINSDEFAULTPAGE) {
			return $out;
		}
	
	
		return $out.$sep.utf8_ucfirst($id->getPage());
	}
	
	function catToc($cat, Parser $parser) {
		$indextable = $parser->getUserInfo('indextable');
		if ($indextable instanceof IndexTable) {
			$out	= '<div class="toc" id="__toc">';
			$lastlvl = 0;
			foreach($indextable->getAsArray() as $item) {
				if ($lastlvl < $item->getLevel()) {
					$diff = $item->getLevel() - $lastlvl;
					for ($i = 0; $i < $diff; $i++)
						$out .= '<ul>';
				} elseif ($lastlvl > $item->getLevel()) {
					$diff = $lastlvl - $item->getLevel();
					for ($i = 0; $i < $diff; $i++)
						$out .= '</ul>';
				}
				$out .= '<li>';
				$out .= '<a href="#header_'.$item->getId().'">'.$item->getId().' '.pw_s2e($item->getText()).'</a>';
				$out .= '</li>';
				$lastlvl = $item->getLevel();
			}
			$out .= '</ul>';
			$out .= '</div>';
		}
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