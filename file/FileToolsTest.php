<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}
require_once INC_PATH.'pwTools/file/FileTools.php';
require_once 'PHPUnit/Framework/TestCase.php';

class FileToolsTest extends PHPUnit_Framework_TestCase {
	
	private $inputList = array(
			"dat/dok/todos/v0.01.txt",
			"dat/dok/todos/",
			"dat/dok/todos",
			"////hallo//du/",
			"start/../anfang/",
			"/../../",
			"/Ähm/",
			"1/2/3/../../Hallo.php",
			"dat/h1/h2/../../zwei ebenen zurück.txt",
			"dat/tests/x/",
			"/",
			"hallo/../zwei.txt",
			"/_index",
			"dat/c/.."
	);
	
	protected function setUp() {
		parent::setUp ();
		
		// TODO Auto-generated FileToolsTest::setUp()
		
		$this->FileTools = new FileTools(/* parameters */);
	}
	
	protected function tearDown() {
		// TODO Auto-generated FileToolsTest::tearDown()
		$this->FileTools = null;
		
		parent::tearDown ();
	}
	
	public function testBasename() {
		$expectedList = array(
				"v0.01.txt",
				"todos",
				"todos",
				"du",
				"anfang",
				"",
				"Ähm",
				"Hallo.php",
				"zwei ebenen zurück.txt",
				"x",
				"",
				"zwei.txt",
				"_index",
				"dat"
		);
		
		foreach ($this->inputList as $i => $input) {
			$this->assertEquals($expectedList[$i], FileTools::basename($input), "$i: INPUT=$input; EXPECTED=".$expectedList[$i]."; RESULT=".FileTools::basename($input));
		}
		
		try {
			FileTools::basename("öäü/SOnderzeichen/ßÖ*");
			$this->fail('An expected exception has not been raised.');
		} catch (Exception $e) {
		}
	}
	
	public function testDirname() {
		$expectedList = array(
			  "dat/dok/todos/",
			  "dat/dok/todos/",
			  "dat/dok/",
			  "/hallo/du/",
			  "anfang/",
			  "/",
			  "/Ähm/",
			  "1/",
			  "dat/",
			  "dat/tests/x/",
			  "/",
			  "",
			  "/",
		 	  "dat/"
		);
		
		foreach ($this->inputList as $i => $input) {
			$this->assertEquals($expectedList[$i], FileTools::dirname($input), "$i: INPUT=$input; EXPECTED=".$expectedList[$i]."; RESULT=".FileTools::dirname($input));
		}
		
		try {
			FileTools::dirname("öäü/SOnderzeichen/ßÖ*");
			$this->fail('An expected exception has not been raised.');
		} catch (Exception $e) {
		}
	}
	
	public function testIsValidPath() {
		// TODO Auto-generated FileToolsTest::testIsFilename()
		$this->markTestIncomplete ( "test not implemented" );
		
		FileTools::isValidPath(/* parameters */);
	}
	
	public function testIsValidFilename() {
		// TODO Auto-generated FileToolsTest::testIsFilename()
		$this->markTestIncomplete ( "test not implemented" );
	
		FileTools::isValidFilename(/* parameters */);
	}
	
	public function testNormalizePath() {
		$expectedList = array(
				"dat/dok/todos/v0.01.txt",
				"dat/dok/todos/",
				"dat/dok/todos",
				"/hallo/du/",
				"anfang/",
				"/",
				"/Ähm/",
				"1/Hallo.php",
				"dat/zwei ebenen zurück.txt",
				"dat/tests/x/",
				"/",
				"zwei.txt",
				"/_index",
				"dat/"
		);
		
		foreach ($this->inputList as $i => $input) {
			$this->assertEquals($expectedList[$i], FileTools::normalizePath($input), "$i: INPUT=$input; EXPECTED=".$expectedList[$i]."; RESULT=".FileTools::normalizePath($input));
		}
		
		try {
			FileTools::normalizePath("öäü/SOnderzeichen/ßÖ*");
			$this->fail('An expected exception has not been raised.');
		} catch (Exception $e) {
		}
	}
}

