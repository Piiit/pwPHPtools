<?php

interface ArrayWalkerConfig {
	public function callBefore($item, $key, $index);
	public function callAfter($item, $key, $index);
	public function doRecursion($item, $key, $index);
	public function getResult();
}

?>