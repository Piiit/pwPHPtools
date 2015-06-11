<?php

interface ParserRunBefore {
	public function runBefore(Parser $parser, Lexer $lexer);
}

?>