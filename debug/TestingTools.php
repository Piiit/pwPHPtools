<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/string/StringTools.php';
require_once INC_PATH.'pwTools/data/ArrayTools.php';
require_once INC_PATH.'pwTools/debug/Log.php';
require_once INC_PATH.'pwTools/data/ArrayPrinter.php';

class TestingTools {
	
	const ERROR = "ERROR";
	const WARN = "WARN";
	const INFORM = "INFORM";
	const DEBUG = "DEBUG";
	const TYPEINFO_DEFAULT = self::NOTYPEINFO;
	const TYPEINFO = true;
	const NOTYPEINFO = false;
	const DEBUG_MAX_LENGTH = 50; 
	
	private static $_debugOn = false;
	private static $_logOn = false;
	private static $_outputOn = false;
	private static $_log;
	
	private static function _createLogAndOutput($output, $level, $newline, $typeInfo = self::TYPEINFO_DEFAULT) {
		$dbg = self::getDebugInfoAsString();
		$out = "";
		if(is_array($output)) {
			$arrayPrinter = new ArrayPrinter();
			$arrayWalker = new ArrayWalker($output, $arrayPrinter);
			$out = $arrayWalker->getResult();
			if($newline == StringTools::REPLACENEWLINE) {
				$out = StringTools::replaceNewlines($out);
			}
		} else {
			if($typeInfo) {
				$out = StringTools::getItemWithTypeAndSize($output, "", $newline);
			} else {
				$out = " ".$output;
			}
		}
		if(self::$_logOn) {
			$logEntry = sprintf("%-".self::DEBUG_MAX_LENGTH."s |%s", substr($dbg,0,self::DEBUG_MAX_LENGTH), (is_array($output) ? "\n" : "").$out);
			switch($level) {
				case self::ERROR:
					self::$_log->addError($logEntry);
				break;
				case self::WARN:
					self::$_log->addWarning($logEntry);
				break;
				case self::INFORM:
					self::$_log->addInfo($logEntry);
				break;
				case self::DEBUG:
					self::$_log->addDebug($logEntry);
				break;
			}
		}
		if(self::$_outputOn) {
			echo "<pre>$dbg$out</pre>";
		}
		
	}
	
	public static function debugOff() {
		self::$_debugOn = false;
	}
	
	public static function debugOn() {
		self::$_debugOn = true;
		if(self::$_logOn) {
			self::$_log->setLogLevel(Log::DEBUG);
		}
	}
	
	public static function logOff() {
		self::$_logOn = false;
	}
	
	public static function logOn() {
		self::$_logOn = true;
		self::$_log = new Log();
		if(self::$_debugOn) {
			self::$_log->setLogLevel(Log::DEBUG);
		} else {
			self::$_log->setLogLevel(Log::INFO);
		}
	}
	
	public static function outputOff() {
		self::$_outputOn = false;
	}
	
	public static function outputOn() {
		self::$_outputOn = true;
	}
	
	public static function getLog() {
		return self::$_log;
	}
	
	private static function _log($output, $level, $typeInfo = self::TYPEINFO_DEFAULT) {
		$tempOutputStatus = self::$_outputOn;
		self::outputOff(); 
		self::_createLogAndOutput($output, $level, StringTools::PRINTNEWLINE, $typeInfo);
		if($tempOutputStatus) {
			self::outputOn();
		}
	}
	
	public static function log($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_log($output, self::INFORM, $typeInfo);
	}
	
	public static function logDebug($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_log($output, self::DEBUG, $typeInfo);
	}
	
	public static function logWarn($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_log($output, self::WARN, $typeInfo);
	}
	
	public static function logError($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_log($output, self::ERROR, $typeInfo);
	}
	
	public static function inform($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_createLogAndOutput($output, self::INFORM, StringTools::PRINTNEWLINE, $typeInfo);
	}
	
	public static function informReplaceNewlines($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_createLogAndOutput($output, self::INFORM, StringTools::REPLACENEWLINE, $typeInfo);
	}
	
	public static function warn($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_createLogAndOutput($output, self::WARN, StringTools::PRINTNEWLINE, $typeInfo);
	}
	
	public static function warnReplaceNewlines($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_createLogAndOutput($output, self::WARN, StringTools::REPLACENEWLINE, $typeInfo);
	}
	
	public static function error($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_createLogAndOutput($output, self::ERROR, StringTools::PRINTNEWLINE, $typeInfo);
	}
	
	public static function errorReplaceNewlines($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		self::_createLogAndOutput($output, self::ERROR, StringTools::REPLACENEWLINE, $typeInfo);
	}
	
	public static function debug($output, $typeInfo = self::TYPEINFO_DEFAULT) {
	  	if (self::$_debugOn == false) {
	  		return;
	  	}
	  	self::_createLogAndOutput($output, self::DEBUG, StringTools::PRINTNEWLINE, $typeInfo);
	}
	
	public static function debugReplaceNewlines($output, $typeInfo = self::TYPEINFO_DEFAULT) {
		if (self::$_debugOn == false) {
	  		return;
	  	}
		self::_createLogAndOutput($output, self::DEBUG, StringTools::REPLACENEWLINE, $typeInfo);
	}
	
	//TODO getDebugInfo should return a debug info object not an array or string
	public static function getDebugInfoAsArray() {
		$debugInfo = debug_backtrace();
		$debug = $debugInfo[0];
		while($debug["file"] == __FILE__) {
			$debug = next($debugInfo);
		}
		$line = $debug["line"];
		$file = $debug["file"];
		$debug = next($debugInfo);
		$debug["line"] = $line;
		$debug["file"] = $file;
		return $debug;
	}
	
	public static function getDebugInfoAsString() {
		$debugInfo = self::getDebugInfoAsArray();
		$funcText = ArrayTools::getIfExists($debugInfo, "class").ArrayTools::getIfExists($debugInfo, "type").ArrayTools::getIfExists($debugInfo, "function");
		if(strlen($funcText) != 0) {
			$funcText = ":".$funcText;
		}
		return basename($debugInfo["file"]).$funcText." (".$debugInfo["line"].")";
	}
	
}

?>