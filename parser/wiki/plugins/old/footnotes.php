<?php

if (!defined('INC_PATH')) {
	define ('INC_PATH', realpath(dirname(__FILE__).'/../../').'/');
}

  function plugin_footnotes(Parser $parser, Node $node) {
  	
  	$footnoteList = $parser->getUserInfo('footnotelist');
  	
    $o = '<div class="footnotes">';
    $o .= '<ol>';
    $i = 0;
    foreach($footnoteList as $ftn) {
      $i++;
      $o .= '<li><a class="footnote_t" id="fn__'.$i.'" href="#fnt__'.$i.'">&uarr;</a> ';
      $o .= $ftn;
      $o .= '</li>';
    }
    $o .= '</ol>';
    if ($i == 0) {
      $o .= 'In diesem Text kommen keine Fu&szlig;noten vor.';
    }
    $o .= '</div>';
    return $o;
  }

?>