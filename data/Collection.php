<?php

class Collection {
	
	const UPDATE = true;
	const NOUPDATE = false;
	
	private $_archive = array();
	
	public function add($name, $item, $updateOK = self::NOUPDATE) {
		if (array_key_exists($name, $this->_archive) && !$updateOK) {
			throw new UnexpectedValueException("Item with name '$name' already exists in this collection!");
		}
		
		$this->_archive[$name] = $item;
	}
	
	public function get($name) {
		if (! array_key_exists($name, $this->_archive)) {
			throw new Exception("Item with name '$name' not found in this collection!");
		}
		
		return $this->_archive[$name];
	}
	
	public function sort() {
		ksort($this->_archive);
	}
	
	public function getArray() {
		return $this->_archive;
	}

}
