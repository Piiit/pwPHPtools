<?php
class ArrayTools {
	
	public static function getIfExists(array $array, $key) {
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
		return null;		
	}
	
	public static function getIfExistsNotNull($valueIfNull, array $array, $key) {
		if (array_key_exists($key, $array)) {
			return $array[$key];
		}
		return $valueIfNull;  
	}
	
	public static function getArrayAsString(array $array, $maxDepth = 0) {
		$arrayWalker = new ArrayWalker($array, new ArrayPrinter(), $maxDepth);
		return $arrayWalker->getResult();
	}
	
}

?>