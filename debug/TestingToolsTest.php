<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/debug/TestingTools.php';
require_once 'PHPUnit/Framework/TestCase.php';

class TestingToolsTest extends PHPUnit_Framework_TestCase {
	
	
	protected function setUp() {
		parent::setUp ();
	}
	
	protected function tearDown() {
		parent::tearDown ();
	}
	
	public function test() {
		$this->markTestIncomplete("test not implemented");
	}
	
}

