<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

class GuiTools {
	
	public static function checkbox($label, $name, $checked) {
		$checkedString = $checked ? " checked='checked' " : "";
		return "<label for='$name'>$label</label><input type='checkbox' name='$name'$checkedString />";
	}
	
	public static function textInput($label, $name, $default = "") {
		return "<label for='$name'>$label</label><input type='text' name='$name' value='$default'/>";
	}
	
	public static function passwordInput($label, $name) {
		return "<label for='$name'>$label</label><input type='password' name='$name' />";
	}
	
	public static function textButton($name, $href, $shortcut = null) {
		$o  = "<span class='button'><a href='?$href'>";
		if ($shortcut !== null) {
			$o .= "<span class='shortcut'>$shortcut</span>";
		}
		$o .= "$name</a></span>";
		return $o;
	}
	
	public static function button($label, $name, $type = "submit") {
		return "<button type='$type' name='$name'>$label</button>";
	}
	
	public static function dialogQuestion($title, $desc, $byesname, $byestext, $bnoname, $bnotext, $href, $method = "post") {
		if(!in_array($method, array("post", "get"))) {
			throw new Exception("Form methods must be 'post' or 'get'!");
		}
		$o = "<div class='admin'>";
		$o .= "<form method='$method' action='?$href' accept-charset='utf-8' id='form'>";
		$o .= "<h1>$title</h1>";
		$o .= "<p>$desc</p>";
		$o .= GuiTools::button($byestext, $byesname);
		$o .= GuiTools::button($bnotext, $bnoname);
		$o .= "</form>";
		$o .= "</div>";
		return $o;
	}
	
	public static function dialogInfo($title, $desc, $href, $method = "post") {
		if(!in_array($method, array("post", "get"))) {
			throw new Exception("Form methods must be 'post' or 'get'!");
		}
		$o = "<div class='admin'>";
		$o .= "<form method='$method' action='?$href' accept-charset='utf-8' id='form'>";
		$o .= "<h1>$title</h1>";
		$o .= "<p>$desc</p>";
		$o .= "<button type='submit'>OK</button>";
		$o .= "</form>";
		$o .= "</div>";
		return $o;
	}
	
	
}

?>