<?php
/**
 * Lexer :: Creates an Abstract Syntax Tree (=AST). Inspired by docuwiki.
 * @author Peter Moser
 * @version 0.5 - newStyle
 * @package Lexer
 */

/*
TODO: old????
abstractnodes in debuginfos aufnehmen...
abstractnodes in patterntable aufnehmen...
bessere debug infos trace damit nicht nur "getPatternInfo" steht...
AST-Schleife und debuginfo-tabelle in separate datei...

TODO: new...
* $this->ids should contain real pattern objects not just strings (_stripExitTagName etc. will be deprecated)
* TODO move debuginfo to a separate class
* Source-code stuff to a separate class (print, showlines, etc.)
* Comments in english!
* combine Token with Node

FIXME LEXER SHOULD ONLY PRODUCE AN AST; PUT EVERYTHING ELSE TO NODE, TREE OR A NEW PARSER CLASS: LIKE GETARRAY, CALLFUNCTION, ETC.
*/

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

require_once INC_PATH.'pwTools/string/encoding.php';
require_once INC_PATH.'pwTools/string/StringTools.php';
require_once INC_PATH.'pwTools/file/FileTools.php';
require_once INC_PATH.'pwTools/file/TextFileFormat.php';
require_once INC_PATH.'pwTools/tree/Node.php';
require_once INC_PATH.'pwTools/data/Collection.php';
require_once INC_PATH.'pwTools/time/Timer.php';
require_once INC_PATH.'pwTools/parser/Token.php';
require_once INC_PATH.'pwTools/parser/Pattern.php';
require_once INC_PATH.'pwTools/debug/Log.php';

class Lexer {
	
	public static $version = "0.5.1";
	
	const TEXTNOTEMPTY	= 16; // Token must have a #Text node (NOT EMPTY)
	
	private $_textInput = null;			// Given string to analyze!
	private $_textPosition = 0;			// Current text position inside the string.
	private $_currentMode;
	private $_aftermatch = "";
	private $_currentLine = "";
	private $_currentLineNumber = 0;
	private $_temptxt = "";
	private $_remtext = "";
	private $_parentStack = array();
	private $_executiontime = 0;
	private $_cycle = -1;
	private $_parsed = false;
	private $_patternTable;
	private $_lastNode = null;
	private $_rootNode = null;  // root of the AST (=Abstract Syntax Tree)
	private $_handlerTable = null;
	private $_handlerTableActive = null;
	

	public function __construct() {
		$this->_handlerTable = new Collection();
		$this->_handlerTableActive = new Collection();
		$this->_patternTable = new Collection();
		$this->_patternTable->add(Token::DOC, new Pattern(Token::DOC));
		$this->_patternTable->add(Token::TXT, new Pattern(Token::TXT));
		$this->_patternTable->add(Token::EOF, new Pattern(Token::EOF));
	}

	public function parse() {
		if($this->_textInput == null) {
			throw new Exception("No text given. Can not parse!");
		}
		
		foreach($this->_handlerTable->getArray() as $handler) {
			$this->setAllowedModes($handler->getName(), $handler->getAllowedModes());
		}
		
		$timer = new Timer();
		$this->_cycle = 0;
		$this->_parsed = false;

		do {
			$this->_cycle++;
			$token = $this->_getToken();
			if ($token) {
				$this->_currentLine = $token->getTextFull();
				$this->_updateTextPosition();
				if ($token->isExit()) {
					$this->_addNodeOnClose($token);
				} else {
					$this->_addNodeOnOpen($token);
				}
			}
			$this->_executiontime = $timer->getElapsedTime(4);
		#} while($this->_cycle <= 6);
		} while(!$token->isEndOfFile());

		$this->_parsed = true;
		
		TestingTools::logDebug("FINISHED: @$this->_textPosition (line $this->_currentLineNumber)");
	}

	public function getExecutionTime() {
		return $this->_executiontime;
	}

	public function getRootNode() {
		if (!$this->_parsed) {
			$this->parse();
		}
		return $this->_rootNode;
	}

	public function getSource() {
		return $this->_textInput;
	}

	public function setSource($source) {

		if (!is_string($source)) {
			throw new InvalidArgumentException("First argument must be string!");
		}

		FileTools::setTextFileFormat($source, new TextFileFormat(TextFileFormat::UNIX));
		$source = "\n".$source."\n";
		$this->_textInput = $source; 
		$this->_temptxt = $source;

		$node = new Node(Token::DOC);
		$this->_rootNode = $node;
		$this->_lastNode = $node;
		$this->_parentStack = array();
		
		TestingTools::logDebug("STARTING: Lexer v".$this->getVersion());
	}


	public static function getVersion() {
		return self::$version;
	}


	public function __tostring() {
		return "Lexer - Version: ".$this->getVersion();
	}

	/**
	 * Fetches a named token out of the result of regexp match! 
	 * All special fields will be stored in CONFIG.
	 *
	 * @param array $regexpMatch Matches
	 */
	private function _getNamedToken($regexpMatch) {
		if (!is_array($regexpMatch)) {
			throw new InvalidArgumentException("Wrong datatype!");
		}
		
		if (empty($regexpMatch) && $this->_currentMode->getName() == Token::DOC) {
			return new Token(Token::EOF, $this->_temptxt, $this->_temptxt, null); // Nothing matched: EOF reached!
		} 

		$name = $this->_getTokenName($regexpMatch);
		$regexpMatch = $this->_cleanupArray($regexpMatch);
		$beforeMatch = $regexpMatch[1];
		$completeMatch = $regexpMatch[0];
		$conf = array_slice($regexpMatch, 2, -1);
		
		return new Token($name, $beforeMatch, $completeMatch, $conf);
	}

	private function _getTokenName($m) {
		foreach ($this->_currentMode->getModes() as $key => $id) {
			if ($m[$key][1] != -1) {
				return $id->getName();
			}
		}
		throw new Exception("ID not found in patternorder-table!");
	}

	private function _cleanupArray($m) {
		$out = array();

		foreach ($m as $i) {
			if ($i[1] != -1) {
				$out[] = $i[0];
			}
		}
		return $out;
	}

	private function _getToken() {

		// Find Entrance or Exit of a Section...
		$parent = $this->_getParentFromStack();
		$pattern = $this->_patternTable->get($parent->getName());

		if ($pattern->isAbstract()) {
			$parent = $parent->getParent();
		}

		$pattern = $this->_patternTable->get($parent->getName());
		$this->_currentMode = $pattern;
		
		$matches = array();
		$regex = $this->_currentMode->getRegexp();
		if (!preg_match($regex, $this->_temptxt, $matches, PREG_OFFSET_CAPTURE) && $pattern->getName() != Token::DOC) {
			$pattern = $this->_patternTable->get($parent->getName());
			$expected = stripslashes($pattern->getExit());
			$found = substr($this->_temptxt, 0, strlen($expected));
			$expected = pw_s2e_whiteSpace($expected);
				
// 			$dbginf = array(
// 				'TYPE' 		=> 'Syntax',	// TODO use constants not strings for dbginf types!
// 				'DESC'		=> "The Mode '{$pattern->getName()}' has been started here, but wasn't ever ended!",
// 				'LINENR' 	=> $this->_currentLineNumber,
// 				'TXTPOS' 	=> $this->_textPosition,
// 				'ENTRYNODE' => $parent,
// 				'PATTERN'	=> $pattern,
// 				//'ENTRYTOKEN' => ... TODO started @ line ".$dientry['LINENR']."; textposition = ".$dientry['TXTPOS'] save debuginfo inside a tokenlist.
// 			);

			$errorMsg = "Exit of $pattern not found: '$expected' expected but '$found' found @$this->_textPosition (line $this->_currentLineNumber).";
			TestingTools::logError($errorMsg);
			throw new Exception($errorMsg);
		}

		$token = $this->_getNamedToken($matches);
// 		TestingTools::debug($token);
		
// 		if ($token->getTextLength() == 0 && $this->_lastNode->getName() == $token->getName()) {
// 			$errorMsg = "Textpointer has not moved for pattern '".$token->getName()."'. Try the NO_RESTORE flag.";
// 			$this->_log->addError($errorMsg);
// 			throw new Exception($errorMsg);
// 		}

		$this->_temptxt = substr($this->_temptxt, $token->getTextLength());

// 		$debugInfo = array(
// 			"LINENR"       => $this->_currentLineNumber,
// 			"LASTNODE"     => $this->_lastNode,
// 			"PARENT"       => $parent,
// 			"TOKEN"        => $token,
// 			"TXTPOS"       => $this->_textPosition,
// 			"PARENTSTACK"  => $this->_parentStack,
// 		);
		TestingTools::logDebug($this->_logFormat("TOKEN FOUND", "$token @$this->_textPosition: "));

		$this->_textPosition += $token->getTextLength();
		
		return $token;
	}

	private function _connectTo($name, $to) {
		$pattern = $this->_patternTable->get($name);
		
		if ($pattern->isAbstract()) {
			$logText = "CONNECTTO: Can't connect two ABSTRACT Nodes! '$name->$to' failed!";
			TestingTools::logWarn($logText);
			throw new Exception($logText);
		}

		if ($pattern->getConnectTo() !== null) {
			TestingTools::logWarn("CONNECTTO for '$name->".$this->_patternTable->get($name)->getConnectTo()."' already set in patterntable! Will be altered to '$name->$to'!");
		}

		$pattern->setConnectTo($to);
		$this->_patternTable->add($to, new Pattern($to, Pattern::TYPE_ABSTRACT), Collection::UPDATE);
		
		TestingTools::logDebug($this->_logFormat("CONNECTTO", "'$name->$to' connected."));
	}
	
	public function registerHandlerAbstract(LexerRuleHandlerAbstract $handler) {
		$children = $handler->getConnectTo();
		foreach($children as $child) {
			$this->_connectTo($child, $handler->getName());
		}
	}
	
	public function registerHandlerActive(LexerRuleHandlerActive $handler) {
		if(strlen($handler->getName()) == 0) {
			throw new Exception("Cannot register a handler without a name!");
		}
		if(array_key_exists($handler->getName(), $this->_handlerTableActive)) {
			throw new Exception("Active handler '".$handler->getName()."' already registered!");
		}
		$this->_handlerTableActive->add($handler->getName(), $handler);
	}
		
	public function registerHandler(LexerRuleHandler $handler) {
		if(strlen($handler->getName()) == 0) {
			throw new Exception("Cannot register a handler without a name!");
		}
		if(array_key_exists($handler->getName(), $this->_handlerTable)) {
			throw new Exception("Handler '".$handler->getName()."' already registered!");
		}
		$this->_handlerTable->add($handler->getName(), $handler);
		$this->addPattern($handler->getPattern());
	}
	
	public function registerHandlerList($handlerList) {
		if(!is_array($handlerList)) {
			throw new Exception("Handler list must be an array!");
		}
		foreach($handlerList as $handler) {
			if($handler instanceof LexerRuleHandler) {
				$this->registerHandler($handler);
			}
			if($handler instanceof LexerRuleHandlerAbstract) {
				$this->registerHandlerAbstract($handler);
			}
			if($handler instanceof LexerRuleHandlerActive) {
				$this->registerHandlerActive($handler);
			}
		}
	}

	public function addWordPattern($name, $entryPattern) {
		$newPattern = new Pattern($name, Pattern::TYPE_WORD, $entryPattern);
		$this->addPattern($newPattern);
	}

	public function addLinePattern($name, $entrypattern, $exitpattern='\n') {
		$newPattern = new Pattern($name, Pattern::TYPE_LINE, $entrypattern, $exitpattern);
		$this->addPattern($newPattern);
	}
	
	public function addSectionPattern($name, $entrypattern, $exitpattern) {
		$newPattern = new Pattern($name, Pattern::TYPE_SECTION, $entrypattern, $exitpattern);
		$this->addPattern($newPattern);
	}
	
	public function addPattern(Pattern $pattern) {
		switch ($pattern->getType()) {
			case Pattern::TYPE_WORD:
				$restore = "";
				if (substr($pattern->getEntry(), -2) == '\n') {
					$restore = "\n";
				}
				$pattern->setRestore($restore);
			break;
			case Pattern::TYPE_LINE:
				$restore = "\n";
				$exitpattern = $pattern->getExit();
				$entrypattern = $pattern->getEntry();
				if (substr($exitpattern, -2) != '\n') {
					$exitpattern .= '\n';
				}
				
				$lookaheadexit = substr($exitpattern, 0, -2);
				if ($pattern->getFlags() & self::TEXTNOTEMPTY) {
					$entrypattern .= '(?=[^\n]+'.$lookaheadexit.'\n)';
				} else {
					$entrypattern .= '(?=[^\n]*'.$lookaheadexit.'\n)';
				}
				
				// Linepatterns must start with a newline...
				if (substr($entrypattern, 0, 2) != '\n') {
					$entrypattern = '\n'.$entrypattern;
				}
				
				// Alle .* ersetzen mit [^\n]* --> damit der exitpattern in derselben Zeile bleiben muss.
				// @TODO: was noch????
				$entrypattern = str_replace('.*', '[^\n]*', $entrypattern);
				
				$pattern->setEntry($entrypattern);
				$pattern->setExit($exitpattern);
				$pattern->setRestore($restore);
			break;
			case Pattern::TYPE_SECTION:
				$restore = "";
				if (substr($pattern->getExit(), -2) == '\n') {
					$restore = "\n";
				}
				
				$pattern->setRestore($restore);
			break;
			default:
				throw new InvalidArgumentException("Pattern object must be of the following types: TYPE_SECTION, TYPE_LINE or TYPE_WORD!");
		}
		$this->_patternTable->add($pattern->getName(), $pattern);
		TestingTools::logDebug($this->_logFormat("ADD PATTERN", $pattern));
	}

	
	public function setAllowedModes($name, $modes) {
		if (! is_array($modes) || ! is_string($name)) {
			throw new InvalidArgumentException("Modename must be string and modes must be an array!");
		}
		$pattern2Add = $this->_patternTable->get($name);
		$patternLogString = "";
		foreach ($modes as $mode) {
			try {
				$pattern = $this->_patternTable->get($mode);
				$pattern->addMode($pattern2Add);
				$patternLogString .= $pattern.", ";
			} catch (Exception $e) {
				TestingTools::logDebug($this->_logFormat("ADD MODE", $pattern2Add." ".$e->getMessage()));
			}
		}
		TestingTools::logDebug($this->_logFormat("ADD MODE", "$pattern2Add can be within $patternLogString"));
	}

	public function getPatternTable() {
		$this->_patternTable->sort();
		return $this->_patternTable;
	}

	public function getPatternTableAsString() {
		$ptable = $this->getPatternTable()->getArray();
		$out = "";
		foreach ($ptable as $p) {
			$out .= "$p\n";
		}
		return $out;
	}

	/**
	 * 
	 * @param string $name
	 * @return boolean
	 */
	private function _addAbstractNode($name) {

		$pattern = $this->_patternTable->get($name);
		$connectToName = $pattern->getConnectTo(); // TODO getConnectTo must return a Pattern!
		$parent = $this->_getParentFromStack();
		if ($connectToName === null || $parent->getName() == $connectToName)
			return false;

		$node = new Node($connectToName);
		$parent->addChild($node);
		$this->_lastNode = $node;

		$this->_parentStackAdd($node);

		TestingTools::logDebug($this->_logFormat("ADD #ABSTRACT", "$node @$this->_textPosition"), array(
			"LASTNODE"	   => $this->_lastNode,
			"PARENT"       => $parent,
			"PARENTSTACK"  => $this->_parentStack
		));
	}
	
	private function _parentStackAdd($node) {
		if (end($this->_parentStack) === $node) {
			throw new Exception("The node '$node' already exists in parent stack!");
		}
		$this->_parentStack[] = $node;
	}

	private function _parentStackRemove() {
		$value = array_pop($this->_parentStack);
		TestingTools::logDebug($this->_logFormat("CLOSING NODE", "$value"));
		
	}

	
	/**
	 * 
	 * @return Ambigous <NULL, mixed>
	 */
	private function _getParentFromStack() {
		$parent = end($this->_parentStack);
		return ($parent == false ? $this->_rootNode : $parent);
	}

	/**
	 * 
	 * @param $token
	 * @return boolean
	 */
	private function _addNodeOnOpen($token) {

		$this->_addTextNode($token, false, false, true);

		$pattern = $this->_patternTable->get($token->getName());
		$parent = $this->_getParentFromStack();
		$parentPattern = $this->_patternTable->get($parent->getName());
		
		if ($pattern->hasConnectTo()) {
			if ($parentPattern->isAbstract() && $parent->getName() != $pattern->getConnectTo()) {
				$this->_parentStackRemove();
			}
			$this->_addAbstractNode($token->getName());
		} else {
			if ($parentPattern->isAbstract()) {
				$this->_parentStackRemove();
			}
		}
		$parent = $this->_getParentFromStack();
		
		$node = new Node($token->getName(), $token->getConfig());
		$parent->addChild($node);
		$this->_lastNode = $node;
		TestingTools::logDebug($this->_logFormat("ADD NODE", "$node to $parent"));
		
		if(array_key_exists($node->getName(), $this->_handlerTableActive->getArray())) {
			$handlerActive = $this->_handlerTableActive->get($node->getName());
			$handlerActive->setNode($node);
			$handlerActive->onNewNodeOnEntry();
		}

		// WORD-patterns get closed right after opening (no parent ID gets stored within the stack)
		$pattern = $this->_patternTable->get($token->getName());
		if ($pattern->getType() == Pattern::TYPE_WORD) {
			// Backstep... Some matched strings must get preserved after the recognition of an exit token,
			// otherwise some entry tokens do not find there whole match (ex. NEWLINES missing)
			if ($pattern->getRestore() != "") {
				$this->_textPosition -= strlen($token->getTextFull());
				$this->_temptxt = $token->getTextFull().$this->_temptxt;
			}
		} else {
			$this->_parentStackAdd($node);
		}
		
		
	}
	
	
	/**
	 * Creates a text node and adds it to the children of "PARENT" 
	 * TODO: Should be changeable from a public function!
	 *
	 * @param Token    	      $token       Einfachen String oder Token-Array (enth?lt Value und Length)
	 * @param boolean  		  $addemptystring   TRUE, wenn leere Textknoten erstellt werden sollen.
	 * @param boolean         $addnewlines      TRUE, wenn reine Newlines '\n' (ohne Leerzeichen) aufgenommen werden sollen.
	 * @param boolean         $addspacelines    TRUE, wenn Leerzeilen = Leerzeichen+Newline aufgenommen werden sollen.
	 *
	 * @return boolean  TRUE on success
	 */
	private function _addTextNode($token, $addemptystring = false, $addnewlines = false, $addspacelines = false) {

		if (!($token instanceof Token)) {
			throw new InvalidArgumentException("First parameter must be an instance of Token!");
		}

		// Erzeuge einen Textknoten aus den neuen und alten Textfragmenten,
		// damit nicht zu viele Textknoten in der selben Hierarchie erzeugt
		// werden. L?sche danach den Textbuffer.
		$text = $this->_remtext.$token->getTextString();
		$length = strlen($text);
		$this->_rememberText(false);

		if ($length == 0 and $addemptystring == false) {
			return;
		}

		$trimtext = trim($text, ' ');

		// TODO Check if this should be removed to recognize newline patterns!!! Nur Newline entdeckt...
		if ($trimtext == "\n" and $addnewlines == false) {
			return;
		}

		// Nur Leerzeichen entdeckt...
		if ($trimtext == "" and $addspacelines == false) {
			return;
		}

		// Textknoten sind in abstrakten Knoten nicht erlaubt...
		// Abstrakte Knoten werden zuerst geschlossen.
		$parent = $this->_getParentFromStack();
		$pattern = $this->_patternTable->get($parent->getName());
		if ($pattern->isAbstract()) {
			$this->_parentStackRemove();
			$parent = $parent->getParent();
		}

		$node = new Node(Token::TXT, $text);
		$parent->addChild($node);
		$this->_lastNode = $node;
		TestingTools::logDebug($this->_logFormat("ADD #TEXT", $node));
	}

	private function _rememberText($text) {
		if ($text === false) {
			$this->_remtext = "";
			return true;
		}

		if (! is_string($text))
			return false;

		$this->_remtext .= $text;
		return true;
	}


	private function _addNodeOnClose($token) {

		// Entry-token found? Do nothing!
		if ($token->isEntry()) {
			return;
		}

		// Backstep... Manche Matches m?ssen nach der Erkennung eines Exit-Tags
		// f?r den n?chsten Entry-Tag bewahrt bleiben.
		$pattern = $this->_patternTable->get($token->getName());
		if ($pattern->getRestore() != "") {
			$this->_textPosition -= strlen($pattern->getRestore());
			$this->_temptxt = $pattern->getRestore().$this->_temptxt;
		}

		$this->_addTextNode($token, false, false, true);

		// Close abstract parent-nodes, if a new mode has to be started!
		$parent = $this->_getParentFromStack();
		$connectToName = $pattern->getConnectTo();
		if ($connectToName === null || $connectToName != $parent->getName()) {
			$parentPattern = $this->_patternTable->get($parent->getName());
			if ($parentPattern->isAbstract()) {
				$this->_parentStackRemove();
			}
		}
		
		$this->_parentStackRemove();
		
		$node = $parent;
		if(array_key_exists($node->getName(), $this->_handlerTableActive->getArray())) {
			$handlerActive = $this->_handlerTableActive->get($node->getName());
			$handlerActive->setNode($node);
			$handlerActive->onNewNodeOnExit();
		}
		
	}

	private function _updateTextPosition() {

		$this->_aftermatch = $this->_temptxt;

		// Length Match-String and Rest
		$lenmarest = strlen($this->_aftermatch.$this->_currentLine);
		$beforeMatch = substr($this->_textInput, 0, strlen($this->_textInput) - $lenmarest);

		$lines = array();
		preg_match_all("#\n#", $beforeMatch.$this->_currentLine, $lines);
		$lines = $lines[0];
		$this->_currentLineNumber = count($lines);
		if (substr($beforeMatch.$this->_currentLine, -1) == "\n") {
			$this->_currentLineNumber--;
		}
	}
	
	private function _logFormat($command, $info) {
		return sprintf("c%d: %s %s", $this->_cycle, $command, $info); 
	}
	
}

?>