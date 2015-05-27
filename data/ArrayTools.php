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
	
	public static function average(array $array) {
		$c = count($array);
		return $c == 0 ? 0 : array_sum($array)/$c;
	}
	
	/**
	 * Calculate the cosine similarity of two arrays.
	 * @param array $u - an array of numbers or null
	 * @param array $v - an array of numbers or null
	 */
	public static function cosineSimilarity(array $u, array $v) {
		$sum_uv = 0;
		$sum_u_squared = 0;
		$sum_v_squared = 0;
		foreach($u as $i => $ui) { 
			$sum_u_squared += $ui * $ui;
			if($v[$i] == null || $ui == null) {
				continue;
			}
			$sum_uv += $ui * $v[$i];
		}
		foreach($v as $vi) {
			$sum_v_squared += $vi * $vi;
		}
		
		$sqrt = sqrt($sum_u_squared * $sum_v_squared);
		return $sqrt == 0 ? 0 : $sum_uv/$sqrt;
	}
	
	/**
	 * Calculates the Pearson Correlation of two arrays.
	 * @param array $u - an array of numbers or null
	 * @param array $v - an array of numbers or null
	 */
	public static function pearsonSimilarity(array $u, array $v) {
		$r_u = ArrayTools::average($u);
		$r_v = ArrayTools::average($v);
	
		$sum_uv = 0;
		$sum_u_squared = 0;
		$sum_v_squared = 0;
		foreach($u as $i => $ui) {
			if($v[$i] == null || $ui == null) {
				continue;
			}
	
			$sum_uv += ($ui - $r_u) * ($v[$i] - $r_v);
			$sum_u_squared += ($ui - $r_u) * ($ui - $r_u);
			$sum_v_squared += ($v[$i] - $r_v) * ($v[$i] - $r_v);
		}
	
		$sqrt = sqrt($sum_u_squared * $sum_v_squared);
		return $sqrt == 0 ? 0 : $sum_uv/$sqrt;
	}
	
}

?>