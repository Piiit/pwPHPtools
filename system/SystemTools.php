<?php

require_once PW_TOOLS_PATH.'debug/TestingTools.php';



class SystemTools {
	
	private static $autoloadDirectories;
	
	public static function autoload($directoryList) {
		self::$autoloadDirectories = array();
		foreach($directoryList as $directory) {
			
			/*
			 * Add given directory itself and all subdirectories...
			 */
			self::$autoloadDirectories[] = $directory.'/';
			self::$autoloadDirectories = array_merge(
				self::$autoloadDirectories, 
				glob($directory."/*/", GLOB_ONLYDIR)
			);
		}
		
		/*
		 * Register autoload callback function
		 */
		spl_autoload_register("self::autoloadCallback");
	}
	
	private static function autoloadCallback($classname) {
		foreach(self::$autoloadDirectories as $dir) {
			if(file_exists($dir.$classname.".php")) {
				TestingTools::debug("Loading class ".$dir.$classname.".php");
				require_once($dir.$classname.".php");
			}
		}
	}
}

?>