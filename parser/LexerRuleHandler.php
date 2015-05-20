<?php
interface LexerRuleHandler {
	public function getPattern();
	public function getAllowedModes();
}

?>