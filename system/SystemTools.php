<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/debug/TestingTools.php';

function __autoload($classname) {
	foreach(SystemTools::$autoloadDirectories as $dir) {
		if(file_exists($dir.$classname.".php")) {
			TestingTools::debug("Loading class ".$dir.$classname.".php");
			require_once($dir.$classname.".php");
		}
	}
}

class SystemTools {
	
	public static $autoloadDirectories;
	
	public static function autoloadInit($directoryList) {
		self::$autoloadDirectories = array();
		foreach($directoryList as $directory) {
			self::$autoloadDirectories = array_merge(self::$autoloadDirectories, glob($directory."/*/", GLOB_ONLYDIR));
		}
	}
}

?>