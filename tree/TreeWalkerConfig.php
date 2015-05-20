<?php

interface TreeWalkerConfig {
	public function callBefore(Node $node);
	public function callAfter(Node $node);
	public function getResult();
	public function doRecursion(Node $node);
}

?>