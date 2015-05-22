<?php

//TODO write test-cases for basename, dirname, isFilename

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/file/TextFileFormat.php';
require_once INC_PATH.'pwTools/debug/TestingTools.php';
require_once INC_PATH.'pwTools/string/encoding.php';

class FileTools {
	
	public static function createFolderIfNotExist($folder) {
		if (strlen($folder) == 0) {
			throw new Exception("Folder string cannot be empty");
		}
		if (!file_exists($folder)) {
			TestingTools::inform($folder);
			$parentFolder = self::dirname($folder.DIRECTORY_SEPARATOR."..");
			TestingTools::inform($parentFolder);
			if(!@mkdir($folder, 0755, true)) {
				$lastError = error_get_last();
				$lastError = $lastError['message'];
				throw new Exception("Creating folder '$folder' failed! ".$lastError);
			}
		}
	}
	
	public static function copyFileIfNotExist($source, $dest) {
		if (strlen($source) == 0 || strlen($dest) == 0) {
			throw new Exception("File strings cannot be empty");
		}
		if (!file_exists($source)) {
			throw new Exception("File '$source' does not exist!");
		}
		if (!file_exists($dest)) {
			if(!copy($source, $dest)) {
				throw new Exception("Copying file '$source' to '$dest' failed!");
			}
		}
	}
	
	public static function copyMultipleFilesIfNotExist($sourceWithWildcards, $dest) {
		if (strlen($sourceWithWildcards) == 0 || strlen($dest) == 0) {
			throw new Exception("File strings cannot be empty");
		}
		if (!is_dir($dest)) {
			throw new Exception("Folder '$dest' does not exist!");
		}
		$files = glob($sourceWithWildcards);
		if (!$files || count($files) == 0) {
			throw new Exception("Pattern '$sourceWithWildcards' does not match any file!");
		}
		foreach ($files as $file) {
			self::copyFileIfNotExist($file, $dest.basename($file));
		}
	}
	
	public static function getUnixFilePermission($filename) {
		if (strlen($filename) == 0) {
			throw new Exception("File strings cannot be empty");
		}
		if (!is_file($filename)) {
			throw new Exception("File '$filename' does not exist!");
		}
		
		$perms = fileperms($filename);
		
		if (($perms & 0xC000) == 0xC000) {
			// Socket
			$info = 's';
		} elseif (($perms & 0xA000) == 0xA000) {
			// Symbolic Link
			$info = 'l';
		} elseif (($perms & 0x8000) == 0x8000) {
			// Regular
			$info = '-';
		} elseif (($perms & 0x6000) == 0x6000) {
			// Block special
			$info = 'b';
		} elseif (($perms & 0x4000) == 0x4000) {
			// Directory
			$info = 'd';
		} elseif (($perms & 0x2000) == 0x2000) {
			// Character special
			$info = 'c';
		} elseif (($perms & 0x1000) == 0x1000) {
			// FIFO pipe
			$info = 'p';
		} else {
			// Unknown
			$info = 'u';
		}
		
		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));
		
		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));
		
		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));
		
		return $info;
	}
	
	private static function removeDirectoryRec($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") {
						self::removeDirectoryRec($dir."/".$object);
					} else {
						if(!is_writeable($dir."/".$object)) {
							throw new Exception("Your are not allowed to delete:<br />'".self::normalizePath($dir)."'!<br />Permissions are ".self::getUnixFilePermission($dir."/".$object));
						}
						if (!unlink($dir."/".$object)) {
							throw new Exception("Unable to remove directory '".self::normalizePath($dir."/".$object)."'");
						}
					}
				}
			}
			reset($objects);
			if (!rmdir($dir)) {
				throw new Exception("Unable to remove directory '".self::normalizePath($dir)."'");
			}
		}
	}
	
	public static function removeDirectory($dir) {
		if(!is_dir($dir)) {
			throw new Exception("Unable to find directory '".self::normalizePath($dir)."'");
		}
		self::removeDirectoryRec($dir);
	}
	
	public static function removeFile($filename) {
		$filename = self::normalizePath($filename);
		if (strlen($filename) == 0) {
			throw new Exception("File strings cannot be empty");
		}
		if (!is_file($filename)) {
			throw new Exception("The file '$filename' does not exist!");
		}
		if(!is_writeable($filename)) {
			throw new Exception("Your are not allowed to delete:<br />'$filename'!<br />Permissions are ".self::getUnixFilePermission($filename));
		}
		if (!unlink($filename)) {
			throw new Exception("Unable to remove file '$filename'");
		}
	}
	
	public static function renameFile($filename, $newFilename) {
		if (self::isValidFilename($newFilename)) {
			throw new Exception("'$newFilename' is not a valid filename!");
		}
		if (!is_file($filename)) {
			throw new Exception("The file '$filename' does not exist!");
		}
		if(!is_readable($filename)) {
			throw new Exception("Your are not allowed to read:<br />'$filename'!<br />Permissions are ".self::getUnixFilePermission($filename));
		}
		$newFilename = self::normalizePath(self::dirname($filename).$newFilename);
		if(is_file($newFilename)) {
			throw new Exception("'$newFilename' already exists.");
		}
		if (!@rename($filename, $newFilename)) {
			$lastError = error_get_last();
			$lastError = $lastError["message"];
			throw new Exception("Unable to rename file '$filename' to '$newFilename'. $lastError");
		}
	}
	
	public static function renameFolder($dirname, $newDirname) {
		// isValidFilename because newDirname should contain only a single directory name, not a whole path.
		if (self::isValidFilename($newDirname)) {
			throw new Exception("'$newDirname' is not a valid directory name!");
		}
		if (!is_dir($dirname)) {
			throw new Exception("The directory '$dirname' does not exist!");
		}
		if(!is_readable($dirname)) {
			throw new Exception("Your are not allowed to read:<br />'$dirname'!<br />Permissions are ".self::getUnixFilePermission($dirname));
		}
		$newDirname = self::normalizePath(self::dirname($dirname."..").$newDirname);
		if(is_dir($newDirname)) {
			throw new Exception("'$newDirname' already exists.");
		}
		$newDirname = rtrim($newDirname, "/");
		if (!@rename($dirname, $newDirname)) {
			$lastError = error_get_last();
			$lastError = $lastError["message"];
			throw new Exception("Unable to rename file '$dirname' to '$newDirname'. $lastError");
		}
	}
	
	public static function moveFile($from, $to) {
		if (!is_file($from)) {
			throw new Exception("The file '$from' does not exist!");
		}
		if(!is_readable($from)) {
			throw new Exception("Your are not allowed to read:<br />'$from'! Permissions are ".self::getUnixFilePermission($from));
		}
		if(!is_dir($to)) {
			throw new Exception("'$to' does not exist!");
		}
		if(!is_writable($to)) {
			throw new Exception("'$to' is not writeable! Permissions are ".self::getUnixFilePermission($to));
		}
		$newFilename = self::normalizePath($to.self::basename($from));
		if(is_file($newFilename)) {
			throw new Exception("'$newFilename' already exists.");
		}
		if (!@rename($from, $newFilename)) {
			$lastError = error_get_last();
			$lastError = $lastError["message"];
			throw new Exception("Unable to move file '$from' to '$newFilename'. $lastError");
		}
	}

	public static function moveFolder($from, $to) {
		if (!is_dir($from)) {
			throw new Exception("The folder '$from' does not exist!");
		}
		if(!is_readable($from)) {
			throw new Exception("Your are not allowed to read:<br />'$from'! Permissions are ".self::getUnixFilePermission($from));
		}
		if(!is_dir($to)) {
			throw new Exception("'$to' does not exist!");
		}
		if(!is_writable($to)) {
			throw new Exception("'$to' is not writeable! Permissions are ".self::getUnixFilePermission($to));
		}
		$newDirname = self::normalizePath($to.self::basename($from)."/");
		if(is_dir($newDirname)) {
			throw new Exception("'$newDirname' already exists.");
		}
		if (!@rename($from, $newDirname)) {
			$lastError = error_get_last();
			$lastError = $lastError["message"];
			throw new Exception("Unable to move folder '$from' to '$newDirname'. $lastError");
		}
	}
		
	public static function getTextFileFormat($text) {
		if (strpos($text,"\n") && strpos($text,"\r")===false) {
			return new TextFileFormat(TextFileFormat::UNIX);
		}
		if(strpos($text,"\r") && strpos($text, "\n")===false) {
			return new TextFileFormat(TextFileFormat::OLDMAC);
		}
		if (($nr = strpos($text,"\n\r")) || strpos($text, "\r\n")) {
			if(isset($nr)) {
				$text = str_replace("\n\r", "", $text);
			} else {
				$text = str_replace("\r\n", "", $text);
			}
			if(strpos($text,"\r") || strpos($text, "\n")) {
				return new TextFileFormat(TextFileFormat::MIXED);
			}
			return new TextFileFormat(TextFileFormat::WINDOWS);
		}
		return new TextFileFormat(TextFileFormat::UNDEFINED);
	}
	
	public static function setTextFileFormat($text, TextFileFormat $newFormat) {
		
		if($newFormat->getOrdinal() == TextFileFormat::UNDEFINED || $newFormat->getOrdinal() == TextFileFormat::MIXED) {
			throw new Exception("Cannot set text file to format ".TextFileFormat::toString($newFormat));
		}
		
		$format = self::getTextFileFormat($text);
		if($format == $newFormat) {
			return $text;
		}
		$text = str_replace(array("\n\r", "\r\n", "\r"), array("\n", "\n", "\n"), $text);
		switch ($newFormat->getOrdinal()) {
			case TextFileFormat::UNIX:
			case TextFileFormat::MAC:
				 return $text;
			break;
			case TextFileFormat::OLDMAC:
				return str_replace("\n", "\r", $text);
			break;
			case TextFileFormat::WINDOWS:
				return str_replace("\n", "\r\n", $text);
			break;
		}
	}

	/**
	 * Return the last path segment (directory or filename)
	 * This basename handels also filepath constructs like ".."
	 * It is made for utf-8 compliant strings.
	 * @param unknown_type $path
	 * @param unknown_type $extension
	 * @return boolean|Ambigous <mixed, string>
	 */
	public static function basename($path, $extension = null) {
		$path = self::normalizePath($path);
		$path = basename($path, $extension);
		return $path;
	}
	
	/**
	 * This dirname handels also filepath constructs like ".."
	 * It is made for utf-8 compliant strings.
	 * @param unknown_type $path
	 * @param unknown_type $single
	 * @return boolean|Ambigous <string, mixed>
	 */
	public static function dirname($path) {
		$path = self::normalizePath($path);
		if(substr($path, -1) == '/') {
			return $path;
		}
		$path = str_replace("\\", "/", dirname($path)).'/';
		$path = str_replace("//", "/", $path);
		return ($path == './' ? '' : $path);
	}
	
	public static function isValidPath($name) {
// 		if (strpos($name, "*") || strpos($name, "\\") || strpos($name, "?")) {
// 			return false;
// 		}
		if (0 == preg_match("=[^?*:;{}]+=", $name)) {
			return false;
		}
		return true;
	}
	
	public static function isValidFilename($name) {
		if (preg_match('=[^/?*:;{}\\\]+=', $name)) {
			return false;
		}
		return true;
	}
	
	public static function normalizePath($path) {
		if (!self::isValidPath($path)) {
			throw new Exception("'$path' is not a valid filename");
		}
		
		$path = str_replace("\\", "/", $path);
		
		//Preserve last directory separator.
		$last = "";
		if (substr($path, -1) == '/' || substr($path, -3) == '/..' || substr($path, -2) == '/.') { 
			$last = "/";
		}  
		
		$out = array();
		foreach(explode('/', $path) as $i => $fold){
			if ($fold=='' || $fold=='.') { 
				continue;
			}
			if ($fold == '..' && $i > 0 && end($out) != '..') {
				array_pop($out);
			} else {
				$out[]= $fold;
			}
		} 
		
		$path = ($path[0] == '/' ? '/' : '').join('/', $out);
		
		//Restore last directory separator, if it is not already present.
		if(substr($path, -1) != '/') {
			$path .= $last; 
		}
		return $path;
	}
	
}

?>