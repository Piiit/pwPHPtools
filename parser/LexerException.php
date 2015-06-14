<?php

class LexerException extends Exception {
	
	private $data = null;
	
	public function __construct($message, $code = 0, $data = null, Exception $previous = null) {
		$this->data = $data;
		parent::__construct($message, $code, $previous);
	}
	
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}
	
	public function getData() {
		return $this->data;
	}
	
	
	
}