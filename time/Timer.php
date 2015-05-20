<?php

class Timer {
	
	private $_time;
	private $_lastTime;

	public function __construct ($start = true) {
		$this->_start();
	}

	private function _getTime() {
		return $this->_time;
	}

	private function _getLastTime() {
		return $this->_lastTime;
	}

	private function _start() {
		$this->_time = $this->_lastTime = $this->_getCurrentTime();
		return true;
	}

	private function _getCurrentTime() {
		$mtime = explode(" ",microtime());
		return $mtime[1] + $mtime[0];
	}

	public function getIntermediateTime ($round = 3) {
		$time = round ($this->_getCurrentTime() - $this->_getLastTime(), $round);
		$this->_lastTime = $this->_getCurrentTime();
		return $time;
	}

	public function getElapsedTime ($round = 3) {
		return round ($this->_getCurrentTime() - $this->_getTime(), $round);
	}
}