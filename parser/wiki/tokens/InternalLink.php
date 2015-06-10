<?php

class InternalLink extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	const MODITEXT = "edit|showpages";
	private static $anchorText = array(
			"_top" 		  => "Page Top",
	        "_toc"        => "Content",	                                               
			"_maintitle"  => "Title",	                                                
			"_bottom"     => "Page Bottom"
			);
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {
		try {
		
			$node = $this->getNode();
	
			$linkPositionNode = $node->getFirstChild();
			$linkPositionText = $this->getTextFromNode($linkPositionNode);
			$curID = pw_wiki_getid();
				
			/*
			 * This is the first char of the linkPositionText, it is a look 
			 * ahead, that can be one of the following:
			 * 	# = anchor ID (e.g., #chapter), which must be extended with the 
			 * 		current ID, that keeps it as a relative path
			 *  : = absolute ID (e.g., :manual:links), which must not be 
			 *      extended 
			 *  default = relative ID, which must be extended with the full 
			 *      current namespace, because we need an absolute path 
			 */
			$lookAhead = utf8_substr($linkPositionText, 0, 1);
			if($lookAhead == ':') {
				$id = new WikiID($linkPositionText);
			} elseif($lookAhead == '#') {
				$id = new WikiID($curID->getID().$linkPositionText);
			} else {
				$id = new WikiID($curID->getFullNS().pw_e2u($linkPositionText));
			}
			
			// Find manually set modes like edit or showpages...
			$linkModus = null;
			if($linkPositionNode->getFirstChild()->getName() == 'internallinkmode') {
				$linkModusData = $linkPositionNode->getFirstChild()->getData();
				$linkModus = $linkModusData[0];
				$modi = explode("|", self::MODITEXT);
				if (!in_array($linkModus, $modi)) {
					throw new Exception("Wrong mode '$linkModus'. Allowed modes are: ".self::MODITEXT);
				}
			}
			
			if (!$linkPositionText) {
				throw new Exception("Wikilink without Target or Empty WikiLink!");
			}
		
			$text = null;
			$textNode = $linkPositionNode->getNextSibling();
			TestingTools::inform($textNode);
			if ($textNode != null) {
				$text = $this->getTextFromNode($textNode);
			}
	 		TestingTools::inform($text);
		
			$found = true;
			$jump = null;
			
			if ($id->hasAnchor()) {
		
				switch($id->getAnchor()) {
					case "_top": 
						$jump = "#__main";
					break;
					case "_bottom": 
						$jump = "#__bottom"; 
					break;
					case "_toc": 
						$jump = "#__toc"; 
					break;
					case "_maintitle": 
						$jump = "#__fullsite"; 
					break;
				}
				
				try {
					// To trigger an exception, also if a text is given...
					//TODO remove try/catch, handled through if, anchorText deprecated? -> check
					//TODO --------------------------> piiit continues here!!!!
					if(!array_key_exists($id->getAnchor(), self::$anchorText)) {
						throw new Exception("Anchor '".$id->getAnchor()."' not found!");
					}
					$tmp = self::$anchorText[$id->getAnchor()];
					if(!$text) {
						$text = $tmp;
					}
					$found = true;
					TestingTools::inform($text);
				} catch (Exception $e) {
					$indextable = $this->getParser()->getUserInfo('indextable');
						
					try {
						$item = $indextable->getByIdOrText(pw_url2t($id->getAnchor()));
						$jump = "#header_".$item->getId();
						if(!$text) {
							$text = pw_s2e($item->getText());
						}
						$found = true;
						TestingTools::inform($text);
					} catch (Exception $e) {
						$found = false;
						if(!$text) {
							$text = utf8_ucfirst(pw_url2e($id->getAnchor()));
						}
						TestingTools::debug($e->getMessage());
						TestingTools::debug($indextable);
						TestingTools::inform($text);
					}
				}
			} 
	
			$filename = WIKISTORAGE.$id->getPath().WIKIFILEEXT;;
			if (!file_exists($filename) && !$linkModus) {
				$linkModus = "edit";
				$found = false;
			}
			
			if (!$text) {
				$text = $linkPositionText;
			}
			TestingTools::inform($text);
			$href = "?id=".pw_s2url($id->getID());
		
			if (!$id->hasAnchor()) {
				if ($linkModus == "edit" or !$found) {
					$href .= '&mode=edit';
				}
				if ($linkModus == "showpages") {
					$href .= "&mode=showpages";
				}
			}
		} catch (Exception $e) {
			return nop($this->getName().": ".$e->getMessage());
		}
	
		return '<a href="'.$href.$jump.'"'.($found ? "" : ' class="pw_wiki_link_na"').'>'.$text.'</a>';
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return false;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_SECTION, '(?=\[\[)', '\]\]');
	}
	
	public function getAllowedModes() {
		return array(
				"#DOCUMENT", "tablecell", "listitem", "multiline", "bold", "underline", 
				"italic", "monospace", "small", "big", "strike", "sub", "sup", "hi", "lo", "notoc",
				"em", "bordererror", "borderinfo", "borderwarning", "bordersuccess", "bordervalidation", "border", 
				"tablecell", "tableheader", "wptableheader", "wptablecell", "align", 
				"justify", "alignintable", "indent", "left", "right", "footnote", "defitem", "defterm");
	}
}

?>