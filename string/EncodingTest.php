<?php
if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/string/encoding.php';
require_once 'PHPUnit/Framework/TestCase.php';

class EncodingTest extends PHPUnit_Framework_TestCase {
	
	protected function setUp() {
		parent::setUp();
	}
	
	protected function tearDown() {
		parent::tearDown();
	}
	
	public function testPw_s2e() {
		$this->assertEquals("&auml;", pw_s2e("ä"), "Result: ".pw_s2e("ä"));		
	}
}
	

 

