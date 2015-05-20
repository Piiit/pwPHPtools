<?php

// TODO Compare (non)-keysensitive of two strings, no matter if entities or utf8 or normal ascii, ex. a&amp; == a&

class StringTools {
	
	private static $_indentation = 0;
	const MIDDLE = 0;
	const START = 1;
	const END = 2;
	const PRINTNEWLINE = 0;
	const REPLACENEWLINE = 1;
	
	private static function getLine($type, $length, $out, $name = null) {
		$name = ($name == null ? "" : $name);
		if($length == -1) {
			return "$name ($type) $out";
		}
		return "$name ($type, $length) $out";
	}
	
	public static function getItemWithTypeAndSize($item, $name = null, $newline = self::PRINTNEWLINE) {
		$name = htmlentities($name);
		
		if (is_array($item)) {
			return self::getLine("array", count($item), "", $name);
		}
		
		if (is_bool($item)) {
			return self::getLine("boolean", -1, $item ? $item = "true" : $item = "false", $name);
		}
		
		if (is_null($item)) {
			return self::getLine("null", -1, "", $name);
		}
		
		if (is_string($item)) {
			$itemClean = htmlentities($item);
			if($newline == self::REPLACENEWLINE) {
				$itemClean = self::replaceNewlines($itemClean);
			}
			$itemClean = preg_replace("#\t#", '\\t', $itemClean);
			return self::getLine("string", strlen($item), $itemClean, $name);
		}
			
		//TODO Remove var_dump and replace it with an own method!
		if (is_object($item)) {
			ob_start();
			var_dump($item);
			$content = ob_get_clean();
			return self::getLine("object", -1, $content, $name);
		}
			
		return self::getLine(gettype($item), count($item), $item, $name);
	}
	
	public static function replaceNewlines($text) {
		$text = preg_replace("#\r#", '\\r', $text);
		$text = preg_replace("#\n#", '\\n', $text);
		return $text;
	}
	
	
	public static function showLineNumbers($textInput) {
		if (!is_string($textInput)) {
			throw new InvalidArgumentException("First argument has to be string!");
		}
		if (strlen($textInput) == 0) {
			return "";
		}
		$text = explode("\n", $textInput);
		$t = "";
		foreach($text as $k => $line) {
			$t .= sprintf("%5d %s\n", $k+1, $line);
		}
		
		return $t;
	}
	
	public static function showReadableFilesize($bytes, $precision = 2, $showbytes = true) {
		$units = array('B&nbsp;', 'KB', 'MB', 'GB', 'TB');
	
		$bytes = max($bytes, 0);
		if ($bytes < 1024 and !$showbytes) {
			$pow = 1;
		} else {
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);
		}
		$bytes /= pow(1024, $pow);
	
		// Round up to 0.1 if bytes are not zero!
		$fl = $bytes;
		$bytes = round($bytes, $precision);
		if ($bytes == 0 and $fl > 0) {
			$bytes = "0.10"; // sprintf... add zeros for normalized output with given precision!
		}
	
		return str_replace(".", ",", $bytes).' '.$units[$pow];
	}
	
	public static function preFormat($textInput) {
		if(strlen($textInput) == 0) {
			return "";
		}
		return "<pre>".$textInput."</pre>";
	}
	
	public static function preFormatShowLineNumbers($textInput) {
		return self::preFormat(self::showLineNumbers($textInput));
	}
	
	public static function boolean2String($value) {
		if (!is_bool($value)) {
			throw new InvalidArgumentException("First argument has to be boolean!");
		}
		return ($value ? "true" : "false");
	}
	
	//TODO Better use a single htmlIndentation method that handles entire html pages... (this should become deprecated)
	public static function htmlIndent($type_txt="", $startend=self::MIDDLE, $newline = true, $spaces = true) {
	
		if (is_numeric($type_txt)) {
			self::$_indentation += $type_txt;
			return "";
		}
	
		$startend = strtolower($startend);
	
		if ($startend == self::END) {
			self::$_indentation--;
		}
	
		if ($spaces) {
			$spaces = "";
			for ($i = 0; $i < self::$_indentation; $i++) {
				$spaces .= "  ";
			}
		}
	
		if ($newline) {
			$newline = "\n";
		}
	
		if ($startend == self::START) {
			self::$_indentation++;
		}
	
		// 		if (array_key_exists($type_txt, $WIKI_GLOBALS[tags])) {
		// 			if (is_array($WIKI_GLOBALS[tags][$type_txt])) {
		// 				return $spaces.$WIKI_GLOBALS[tags][$type_txt][$startend].$newline;
		// 			}
		// 			return $spaces.$WIKI_GLOBALS[tags][$type_txt].$newline;
		// 		}
		return $spaces.$type_txt.$newline;
	}
	
	public static function htmlIndentPrint($type_txt="", $startend="", $newline = true, $spaces = true) {
		echo self::htmlIndent($type_txt, $startend, $newline, $spaces);
	}
	
	public static function htmlIndentReset() {
		self::$_indentation = 0;
	}
	
	public static function rightTrim($message, $strip) {
		$lines = explode($strip, $message);
		$last  = '';
		do {
			$last = array_pop($lines);
		} while (empty($last) && (count($lines)));
		return implode($strip, array_merge($lines, array($last)));
	}
	
	public static function deleteUntilDiff($string1, $string2) {
		$string1Shorter = false;
		if(strlen($string1) < strlen($string2)) {
			$minLength = strlen($string1);
			$string1Shorter = true;
		} else {
			$minLength = strlen($string2);
		}
		for($i = 0; $i < $minLength; $i++) {
			if($string1[$i] != $string2[$i]) {
				return substr($string1, $i);
			}
		}
		if($string1Shorter) {
			return substr($string2, $minLength);
		}
		return substr($string1, $minLength);
	}
	
	public static function equals($string1, $string2) {
		throw new Exception("Not implemented yet!");
	}
	
	public static function equalsIgnoreCase($string1, $string2) {
		throw new Exception("Not implemented yet!");
	}
	
	public static function equalsTrimmed($string1, $string2) {
		throw new Exception("Not implemented yet!");
	}

	public static function equalsIgnoreCaseTrimmed($string1, $string2) {
		throw new Exception("Not implemented yet!");
	}
	
}

?>