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
			throw new Exception("Plugin '".$this->getPluginName()."': No default command specified.");
		}
		
		$out = null;
	    switch ($categories[0]) {
	    	
	    	/*
	    	 * Wiki and Parser functions.
	    	 */
	      	case "version": 
	      		$out = $parser->getUserInfo('piwoversion'); 
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
			 * The following methods build HTML to be shown, we do not want to
			 * encode htmlentities. All local methods beginning with 'category'
			 * are handlers for specific subcategories given by $categories[1-n].
			 */
			default:
				$objectMethod = 'category'.ucfirst($categories[0]);
				if(method_exists($this, $objectMethod)) {
					$subCategories = array_slice($categories, 1);

					$out = call_user_func(
						array($this, $objectMethod), 
						$subCategories, 
						$node, 
						$parser
					);
					
					if($out !== null) {
						return $out;
					}
				} 
	    }
	    
	    if ($out === null) {
	    	throw new Exception("Plugin '".$this->getPluginName()."': No method '".implode(".", $categories)."' found.");
	    }
	    
	    $out = pw_s2e($out);
	    return $out;

	}
	
	function categoryTrace ($cat, Node $node, Parser $parser) {
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
	
	function categoryToc($cat, Node $node, Parser $parser) {
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
	
	public function categoryLexer($cat, Node $node, Parser $parser) {
		switch ($cat[0]) {
			case 'version': 
				return Lexer::getVersion();
			case 'performance':
				return $parser->getUserInfo("wiki.lexer.performance");
		}
		return null;
	}
	
	public function categoryFile($cat, Node $node, Parser $parser) {
		switch ($cat[0]) {
			case 'type':
				return $parser->getUserInfo("wiki.file.type");
			case 'path':
				return 'http://'.$_SERVER['SERVER_NAME'].FileTools::dirname($_SERVER['PHP_SELF']);
		}
		return null;
	}
	
	/**
	 * Process namespace categories:
	 * @param null|array $cat 
	 * <li> null = Default handler: Return NS name</li>
	 * <li> array = categories and subcategores, last entry is the method</li>
	 * @param Node $node
	 * @param Parser $parser
	 */
	public function categoryNs($cat, Node $node, Parser $parser) {
		
		/*
		 * Default handling, no subcategory set: Return the namespace name.
		 */
		if(! isset($cat[0])) {
			$id = pw_wiki_getid();
			$out = pw_url2u($id->getNS());
			$out = pw_s2e($out);
			return $out;
		}

		/*
		 * Subcategory handling.
		 */
		switch ($cat[0]) {
			case 'countsubs':
				/*
				 * Count all wikipages within the current namespace.
				 */
				$path = FileTools::dirname($_SERVER['PHP_SELF']);
				$out = count(glob($path."/*".WIKIFILEEXT));
				return $out;
			break;
			case 'full':
				$id = pw_wiki_getid();
				$out = pw_url2u($id->getFullNS());
				$out = pw_s2e($out);
				return $out;
			break;
			case 'toc':
				return PluginWiki::plugin_nstoc($parser, $node);
			break;
		}
		return null;
	}
	
	static function plugin_nstoc(Parser $parser, Node $node) {
	
		try {
			$token = new ParserRule($node, $parser);
			$cont = $token->getArrayFromNode($node);
		} catch (Exception $e) {
			return nop("Syntax error: ".$e->getMessage());
		}
		$curID = pw_wiki_getid();
 		TestingTools::inform($cont);
	
		//TODO errors should bubble up
		//TODO Title should be of the form TITLE=string...
		try {
			$id = new WikiID(isset($cont[1]) && WikiID::isValidAndAbsolute($cont[1]) ? $cont[1] : $curID->getFullNS());
			TestingTools::inform($id->getFullNS());
		} catch (Exception $e) {
			return nop($e->getMessage());
		}
	
	
		// Parameter TITLE: Print Title
		$titeltxt = "";
		//TODO do not use position 0 and 1 of the array (1 is the title string)
		if (in_array("TITLE", $cont)) {
			$titeltxt = utf8_ucwords(str_replace(":", " &raquo; ", $id->isRootNS() ? "[root]" : trim($id->getFullNS(), ":")));
			$titeltxt = "Content of namespace <i>\"$titeltxt\"</i>: ";
		}
	
		$error = in_array("NOERR", $cont);
	
		$path = WIKISTORAGE.$id->getFullNSPath();
	
		$wikiFiles = array_merge(glob($path."*/", GLOB_ONLYDIR), glob($path."*".WIKIFILEEXT));
		sort($wikiFiles);
	
		// 	TestingTools::inform($wikiFiles);
		// Titel werden nur ausgegeben, wenn Fehlermeldungen auch ausgegeben werden dürfen!
		// ...sonst kann es zu alleinstehenden Titeln kommen.
	
		$out = "";
		if (utf8_strlen($titeltxt) > 0) {
			$out .= $titeltxt;
		}
		if($error && empty($wikiFiles)) {
			return $out."<br />".nop("There are no pages in the namespace '".pw_s2e($id->getFullNS())."'.", false);
		}
	
		$out .= "<ul>";
		$uniqueWikiLinks = array();
		foreach($wikiFiles as $file) {
			$curId = WikiID::fromPath($file, WIKISTORAGE, WIKIFILEEXT);
			if($curId->getPage() == WIKINSDEFAULTPAGE) {
				continue;
			}
	
			if($curId->isNS()) {
				$url = $curId->getFullNSAsUrl();
				$name = $curId->getNS();
			} else {
				$url = $curId->getIDAsUrl();
				$name = $curId->getPage();
			}
			//TestingTools::inform($name);
	
			if(!in_array($name, $uniqueWikiLinks)) {
				$out .= "<li><a href='?id=".$url."'>".pw_s2e(utf8_ucfirst($name))."</a></li>";
				// 			TestingTools::inform($curId);
				$uniqueWikiLinks[] = $name;
			}
		}
		$out .= "</ul>";
	
		//  	TestingTools::inform($uniqueWikiLinks);
		return $out;
	
		// Parameter NOERR: Do not show error messages!
		// 	return pw_wiki_nstoc($titeltxt, in_array("NOERR", $cont));
	}
	
}