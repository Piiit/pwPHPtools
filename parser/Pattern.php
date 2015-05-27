<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/tree/Node.php';

class Pattern extends Node {
	
	const TYPE_BASE 	= 0;
	const TYPE_ABSTRACT = 1;
	const TYPE_WORD 	= 2;
	const TYPE_LINE 	= 3;
	const TYPE_SECTION 	= 4;
	private static $_type_names = array("TYPE_BASE", "TYPE_ABSTRACT", "TYPE_WORD", "TYPE_LINE", "TYPE_SECTION");
	
	private $_type = self::TYPE_BASE;
	private $_modes = array();
	private $_entry = "";
	private $_exit = null;
	private $_restore = "";
	private $_connectTo = null;
	private $_level = -1;
	private $_flags = 0;
	private $_regexp = "";
	private $_keySensitive = false;
	
	public function __construct($name, $type = self::TYPE_BASE, $entry = "", $exit = null, $keySensitive = false) {
		
		if (! is_bool($this->_keySensitive)) {
			throw new InvalidArgumentException("Parameter 'keySensitive' has to be of type boolean.");
		}
		
		parent::setName($name);
		$this->_setType($type);
		
		if ($type != self::TYPE_BASE && $type != self::TYPE_ABSTRACT && substr($name, 0, 8) != "__exit__") {
			$this->setEntry($entry);
			if ($exit !== null) {
				$this->setExit($exit);
			}
		}
		$this->_keySensitive = $keySensitive;
	}
	
	public function __toString() {
		$connectTo = $this->_connectTo ? "; CONNECTED TO ".$this->_connectTo : "";
		return "[Pattern: ".parent::getName()." (".self::$_type_names[$this->_type].$connectTo.")]"; 
	}
	
	/**
	 * @return the $name
	 */
	public function getName() {
		return parent::getName();
	}

	/**
	 * @return the $type
	 */
	public function getType() {
		return $this->_type;
	}

	/**
	 * @return the $modes
	 */
	public function getModes() {
		return $this->_modes;
	}
	
	/**
	 * @return the $entry
	 */
	public function getEntry() {
		return $this->_entry;
	}

	/**
	 * @return the $exit
	 */
	public function getExit() {
		return $this->_exit;
	}

	/**
	 * @return the $connectTo
	 */
	public function getConnectTo() {
		return $this->_connectTo;
	}

	/**
	 * @return the $level
	 */
	public function getLevel() {
		return $this->_level;
	}

	/**
	 * @return the $flags
	 */
	public function getFlags() {
		return $this->_flags;
	}

	/**
	 * @return the $restore
	 */
	public function getRestore() {
		return $this->_restore;
	}
	
	/**
	 * @param multitype: $modes
	 */
	public function setModes($modes) {
		$this->_modes = $modes;
	}
	
	public function addMode($mode) {
		// Don't add yourself: Possible deadlock!
		if ($mode->getName() != $this->getName()) {
			$this->_modes[] = $mode;
		}
	}

	/**
	 * @param string $entry
	 */
	public function setEntry($entry) {
		if (!is_string($entry) || strlen($entry) == 0) {
			throw new InvalidArgumentException("Entry-pattern invalid! Not added. name='$this->_name', pattern='$this->_entry'");
		}
		$this->_entry = $entry;
		$this->_setLevel();
	}

	/**
	 * @param string $exit
	 */
	public function setExit($exit) {
		if (!is_string($exit) || strlen($exit) == 0) {
			throw new InvalidArgumentException("Exit-pattern invalid! Not added. name='$this->_name', pattern='$this->_entry'");
		}
		$this->_exit = $exit;
	}

	/**
	 * @param NULL $connectTo
	 */
	public function setConnectTo($connectTo) {
		$this->_connectTo = $connectTo;
	}
	
	public function hasConnectTo() {
		return ($this->_connectTo !== null);
	}

	public function isAbstract() {
		return ($this->_type == self::TYPE_ABSTRACT);
	}
	
	public function hasModes() {
		return (!empty($this->_modes));
	}

	/**
	 * @param number $flags
	 */
	public function setFlags($flags) {
		$this->_flags = $flags;
	}
	
	private function _setType($type) {
		// TODO check for valid types....
		$this->_type = $type;
	}
	
	private function _setLevel() {
		// Count all (...), but not lookarounds (?...)!
		// TODO: ERROR-Handling + Nesting-Level (...(...)...) + OR-Operator
		$matches = array();
		preg_match_all('/\([^\?][^\)]*\)|\(\)/', $this->_entry, $matches);
	 	$this->_level = count($matches[0]);
	 	if ($this->_level == 0) {
	 		$this->_entry .= '()';
	 		$this->_level = 1;
	 	}
	}
	
	

	/**
	 * @param string $_restore
	 */
	public function setRestore($restore) {
		$this->_restore = $restore;
	}
	
	
	/**
	 * @return the $regexp
	 */
	public function getRegexp() {
		$this->_compile();
		return $this->_regexp;
	}
	
	public function isCompiled() {
		return ($this->_regexp != "");
	}

	/**
	 * @param string $regexp
	 */
	public function setRegexp($regexp) {
		$this->_regexp = $regexp;
	}


	/**
	 * Map new array keys in order that they correspond to the pattern-levels inside the regexp-match!
	 * Compile a new regexp string with OR-catenation (do nothing, if already compiled).
	 * @return  string/boolean  regexp-string oder false bei einem Fehler
	 */
	private function _compile() {
		if ($this->isCompiled()) {
			return;
		}
		
		$regex_start = '/(.*?)(?:';
		$regex_pattern = '';
		$regex_end   = ')()/';
		$regex_param = 'msS';

		if ($this->isKeySensitive() === false) {
			$regex_param .= 'i';
		}

		// Matching-Array: 0 => Entire String (for RESTORE), 1 => Text (content), 2..n => Modes
		$modeLevel = 2;
		$newModes = array();
		
		// Get all modes and alter array keys!
		if ($this->hasModes()) {
			foreach($this->getModes() as $mode) {
				$newModes[$modeLevel] = $mode;
				$modeLevel += $mode->getLevel();
				$regex_pattern .= $mode->getEntry().'|';
			}
		}

		if ($this->getExit() === null) {
			$regex_pattern = rtrim($regex_pattern, '|');
		} else {
			$newModes[$modeLevel] = new Pattern("__exit__".$this->getName(), $this->_type);
			$modeLevel += $this->getLevel();
			$regex_pattern .= $this->getExit();
		}
		
		if (strlen($regex_pattern) == 0) {
			throw new Exception("PATTERN '{$this->getName()}': Regular Expression is empty!");
		}

		$this->setRegexp($regex_start.$regex_pattern.$regex_end.$regex_param);
		$this->setModes($newModes);
	}
	/**
	 * @return the $_keySensitive
	 */
	public function isKeySensitive() {
		return $this->_keySensitive;
	}

	
}

?>