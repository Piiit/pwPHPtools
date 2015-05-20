<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/debug/LogEntry.php';
require_once INC_PATH.'pwTools/debug/TestingTools.php';

class Log {
	
	const ERROR   = 1;  
	const WARNING = 2;  
	const INFO    = 3;  
	const DEBUG   = 4;  
	
	private $_logbook = array();
	private $_dateFormat = "Y-m-d h:i:s";
	private $_logLevel = self::INFO;
	private $_dateTimezone = "Europe/Rome";
	
	public function __construct($level = self::INFO) {
		$this->setTimezone($this->_dateTimezone);
		$this->setLogLevel($level);
	}
	
	public function setTimezone($zone) {
		if(!date_default_timezone_set($zone)) {
			throw new Exception("Invalid timezone: ".$zone);
		}
	}
	
	public function add($text) {
		$this->_add($this->_logLevel, $text);
	}
	
	public function addDebug($text) {
		$this->_add(self::DEBUG, $text);
	}
	
	public function addInfo($text) {
		$this->_add(self::INFO, $text);
	}
	
	public function addWarning($text) {
		$this->_add(self::WARNING, $text);
	}
	
	public function addError($text) {
		$this->_add(self::ERROR, $text);
	}
	
	public function setLogLevel($level) {
		if ($level < 1 || $level > 4) {
			throw new InvalidArgumentException("Valid severity levels are 1=ERROR, 2=WARNING, 3=INFO or 4=DEBUG. $level given!");
		}
		$this->_logLevel = $level;
	}
	
	public function getLog() {
		return $this->_logbook;
	}
	
	public function getLogReversed() {
		return array_reverse($this->_logbook);
	}
	
	public function getLastLog() {
		return end($this->_logbook);
	}
	
	public function toStringReversed() {
		return $this->toString(true);
	}

	public function __toString() {
		return $this->toString();
	}
	
	public function toString($reversed = false) {
		$out = "";
		$logBook = $reversed ? $this->getLogReversed() : $this->getLog();
		foreach ($logBook as $logEntry) {
			$date = date($this->_dateFormat, $logEntry->getTimestamp());
			$typeString = $this->_getLogLevelString($logEntry->getLevel());
			$out .= sprintf("%19s | %-7s | %s\n", $date, trim($typeString), $logEntry->getData());
		}
		return $out;
	}
	
	public function getLogLevel() {
		return $this->_logLevel;
	}
	
	public function getLogLevelAsString() {
		return $this->_getLogLevelString($this->_logLevel);
	}
	
	private function _getLogLevelString($type) {
		switch ($type) {
			case self::DEBUG: return "DEBUG";
			case self::INFO: return "INFO";
			case self::WARNING: return "WARNING";
			case self::ERROR: return "ERROR";
		}
	}
	
	private function _add($loglevel, $text) {
		if ($this->getLogLevel() < $loglevel) {
			return;
		}
		$this->_logbook[] = new LogEntry(time(), $loglevel, $text, TestingTools::getDebugInfoAsArray());
	}
	
}

?>