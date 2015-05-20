<?php
class IndexItem {
	
	private $_text;
	private $_level;
	private $_id;
	
	public function __construct($id, $level, $text) {
		$this->_id = $id;
		$this->_level = $level;
		$this->_text = $text;
	}
	
	public function getText() {
		return $this->_text;
	}

	public function getLevel() {
		return $this->_level;
	}

	public function getId() {
		return $this->_id;
	}
	
	public function __toString() {
		return "[IndexItem: id=".$this->_id."; level=".$this->_level."; text=".$this->_text."]";
	}

}

?>