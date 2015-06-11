<?php

class Constant extends ParserRule implements ParserRuleHandler, LexerRuleHandler {
	
	public function getName() {
		return strtolower(__CLASS__);
	}
	
	public function onEntry() {

		$nodeData = $this->getNode()->getData();
  		$conf = pw_s2u($nodeData[0]);

  		//TODO do this with quoted string tokens...
  		$matches = array();
  		if (preg_match("#(.*) *= *(.*)#i", $conf, $matches)) {
    		$varname = utf8_strtolower(utf8_trim($matches[1]));
    		$value = utf8_trim($matches[2]);
    		Variable::$variables[$varname] = $value;
    		return;
  		}

  		$conf = utf8_strtolower($conf);

  		$txt = "";

		//TODO: Substitute this translations with real PHP i18n functions!
		$months_translated = array("Januar","Februar","M&auml;rz","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember");;
		$months = array("January","February","March","April","May","June","July","August","September","October","November","December");
		$days = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
		$days_translated = array("Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag");
	
		$varnames = explode(":", $conf);
		$varname = array_shift($varnames);
		$subcat = array_pop($varnames);
		
		switch($varname) {
			case 'date': $txt = date('d.m.Y'); break;
			case 'month': $txt = date('m'); break;
			case 'monthname': $txt = str_replace($months,$months_translated,date('F')); break;
			case 'day': $txt = date('d'); break;
			case 'dayname': $txt = str_replace($days,$days_translated,date('l')); break;
			case 'year': $txt = date('Y'); break;
			case 'time': $txt = date('H:i'); break;
			case 'pi': $txt = str_replace('.',',',round(pi(), 5)); break;
			case 'e': $txt = str_replace('.',',',round(2.718281828459045235, 5)); break;
			case 'ns': 
				$id = pw_wiki_getid();
				$txt = pw_url2u($id->getNS()); 
			break;
			case 'fullns': 
				$id = pw_wiki_getid();
				$txt = pw_url2u($id->getFullNS()); 
			break;
			case 'page':
				$id = pw_wiki_getid();
				$txt = pw_url2u($id->getPage()); 
			break;
			case 'wrongid':
				try { 
					$wrongId = pw_wiki_getcfg('wrongid');
// 					var_dump($wrongId);
					$txt = pw_url2u($wrongId->getID());
				} catch (Exception $e) {
					$txt = "";
				} 
			break;
			case 'id':
				$id = pw_wiki_getid(); 
				$txt = pw_url2u($id->getID()); 
			break;
			case 'startpage': 
				$txt = ':'.pw_url2u(WIKINSDEFAULTPAGE); 
			break;
			case 'version': $txt = $this->getParser()->getUserInfo('piwoversion'); break;
			case 'lexerversion': $txt = Lexer::getVersion(); break;
			case 'path': $txt = 'http://'.$_SERVER['SERVER_NAME'].FileTools::dirname($_SERVER['PHP_SELF']); break;
			case 'countsubs':
				// count all wikipages within the current namespace
				$path = FileTools::dirname($_SERVER['PHP_SELF']);
				$txt = count(glob($path."/*".WIKIFILEEXT));
			break;
			case 'performance':
				$txt = $this->getParser()->getUserInfo('lexer.performance');
			break;
			case 'file':
				try {
					$txt = $this->getParser()->getUserInfo('file.'.$subcat);
				} catch (Exception $e) {
					$txt = nop($e->getMessage());
				}
			break;
			default:
// 				TestingTools::inform($varname);
				if (isset(Variable::$variables[$varname])) {
					$txt = Variable::$variables[$varname];
					$txt = self::unescape($txt);
					return $txt;
				} else {
					$_SESSION['pw_wiki']['error'] = true;
					return nop("VARIABLE '$varname' wurde nicht gesetzt.", false);
				}
	
			break;
		}
	
		return pw_s2e($txt);
	}

	public function onExit() {
		return '';
	}

	public function doRecursion() {
		return true;
	}

	public function getPattern() {
		return new Pattern($this->getName(), Pattern::TYPE_WORD, '{xxx{([^{]*?)}}');
	}
	
	public function getAllowedModes() {
		return array("#DOCUMENT", "tablecell", "listitem", "multiline", "bordererror", "borderinfo", "borderwarning", 
				"bordersuccess", "bordervalidation", "border", "bold", "underline", "italic", "monospace", "small", "big", 
				"strike", "sub", "sup", "hi", "lo", "em", "tablecell", "tableheader", "wptableheader", "wptablecell",
				"align", "justify", "alignintable", "indent", "left", "right", "pluginparam", "header", "internallinkpos", 
				"internallinktext", "externallink", "externallinkpos", "variable", "plugin", "pluginparameter"
				);
	}
	
	private static function unescape($txt) {
  		return str_replace(array('\"', '\>'), array('"', '&gt;'), $txt);
	}
}

?>