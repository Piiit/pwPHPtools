<?php
/**
 * UTF8 helper functions
 *
 * @license    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

/**
 * check for mb_string support
 */
if(!defined('UTF8_MBSTRING')){
    if(function_exists('mb_substr') && !defined('UTF8_NOMBSTRING')){
        define('UTF8_MBSTRING',1);
    }else{
        define('UTF8_MBSTRING',0);
    }
}

// UTF8_MBSTRING Support turned off!!! //PITIZ
#define('UTF8_MBSTRING', 0);

if(UTF8_MBSTRING){ mb_internal_encoding('UTF-8'); }



if(!function_exists('utf8_encodeFN')){
    /**
     * URL-Encode a filename to allow unicodecharacters
     *
     * Slashes are not encoded
     *
     * When the second parameter is true the string will
     * be encoded only if non ASCII characters are detected -
     * This makes it safe to run it multiple times on the
     * same string (default is true)
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @see    urlencode
     */
    function utf8_encodeFN($file,$safe=true){
        if($safe && preg_match('#^[a-zA-Z0-9/_\-.%]+$#',$file)){
            return $file;
        }
        $file = urlencode($file);
        $file = str_replace('%2F','/',$file);
        return $file;
    }
}

if(!function_exists('utf8_decodeFN')){
    /**
     * URL-Decode a filename
     *
     * This is just a wrapper around urldecode
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @see    urldecode
     */
    function utf8_decodeFN($file){
        $file = urldecode($file);
        return $file;
    }
}

if(!function_exists('utf8_isASCII')){
    /**
     * Checks if a string contains 7bit ASCII only
     *
     * @author Andreas Haerter <netzmeister@andreas-haerter.de>
     */
    function utf8_isASCII($str){
        return (preg_match('/(?:[^\x00-\x7F])/', $str) !== 1);
    }
}

if(!function_exists('utf8_strip')){
    /**
     * Strips all highbyte chars
     *
     * Returns a pure ASCII7 string
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function utf8_strip($str){
        $ascii = '';
        $len = strlen($str);
        for($i=0; $i<$len; $i++){
            if(ord($str{$i}) <128){
                $ascii .= $str{$i};
            }
        }
        return $ascii;
    }
}

if(!function_exists('utf8_check')){
    /**
     * Tries to detect if a string is in Unicode encoding
     *
     * @author <bmorel@ssi.fr>
     * @link   http://www.php.net/manual/en/function.utf8-encode.php
     */
    function utf8_check($Str) {
        if (is_array($Str)) {
          //TestingTools::debug($Str, "utf8_check: array to string conversation!");
          $Str = (string)$Str;
        }
        $len = strlen($Str);
        for ($i=0; $i<$len; $i++) {
            $b = ord($Str[$i]);
            if ($b < 0x80) continue; # 0bbbbbbb
            elseif (($b & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif (($b & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif (($b & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif (($b & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif (($b & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $len) || ((ord($Str[$i]) & 0xC0) != 0x80))
                    return false;
            }
        }
        return true;
    }
}

if(!function_exists('utf8_strlen')){
    /**
     * Unicode aware replacement for strlen()
     *
     * utf8_decode() converts characters that are not in ISO-8859-1
     * to '?', which, for the purpose of counting, is alright - It's
     * even faster than mb_strlen.
     *
     * @author <chernyshevsky at hotmail dot com>
     * @see    strlen()
     * @see    utf8_decode()
     */
    function utf8_strlen($string){
        return strlen(utf8_decode($string));
    }
}

if(!function_exists('utf8_substr')){
    /**
     * UTF-8 aware alternative to substr
     *
     * Return part of a string given character offset (and optionally length)
     *
     * @author Harry Fuecks <hfuecks@gmail.com>
     * @author Chris Smith <chris@jalakai.co.uk>
     * @param string
     * @param integer number of UTF-8 characters offset (from left)
     * @param integer (optional) length in UTF-8 characters from offset
     * @return mixed string or false if failure
     */
    function utf8_substr($str, $offset, $length = null) {
        if(UTF8_MBSTRING){
            if( $length === null ){
                return mb_substr($str, $offset);
            }else{
                return mb_substr($str, $offset, $length);
            }
        }

        /*
         * Notes:
         *
         * no mb string support, so we'll use pcre regex's with 'u' flag
         * pcre only supports repetitions of less than 65536, in order to accept up to MAXINT values for
         * offset and length, we'll repeat a group of 65535 characters when needed (ok, up to MAXINT-65536)
         *
         * substr documentation states false can be returned in some cases (e.g. offset > string length)
         * mb_substr never returns false, it will return an empty string instead.
         *
         * calculating the number of characters in the string is a relatively expensive operation, so
         * we only carry it out when necessary. It isn't necessary for +ve offsets and no specified length
         */

        // cast parameters to appropriate types to avoid multiple notices/warnings
        $str = (string)$str;                          // generates E_NOTICE for PHP4 objects, but not PHP5 objects
        $offset = (int)$offset;
        if (!is_null($length)) $length = (int)$length;

        // handle trivial cases
        if ($length === 0) return '';
        if ($offset < 0 && $length < 0 && $length < $offset) return '';

        $offset_pattern = '';
        $length_pattern = '';

        // normalise -ve offsets (we could use a tail anchored pattern, but they are horribly slow!)
        if ($offset < 0) {
            $strlen = strlen(utf8_decode($str));        // see notes
            $offset = $strlen + $offset;
            if ($offset < 0) $offset = 0;
        }

        // establish a pattern for offset, a non-captured group equal in length to offset
        if ($offset > 0) {
            $Ox = (int)($offset/65535);
            $Oy = $offset%65535;

            if ($Ox) $offset_pattern = '(?:.{65535}){'.$Ox.'}';
            $offset_pattern = '^(?:'.$offset_pattern.'.{'.$Oy.'})';
        } else {
            $offset_pattern = '^';                      // offset == 0; just anchor the pattern
        }

        // establish a pattern for length
        if (is_null($length)) {
            $length_pattern = '(.*)$';                  // the rest of the string
        } else {

            if (!isset($strlen)) $strlen = strlen(utf8_decode($str));    // see notes
            if ($offset > $strlen) return '';           // another trivial case

            if ($length > 0) {

                $length = min($strlen-$offset, $length);  // reduce any length that would go passed the end of the string

                $Lx = (int)($length/65535);
                $Ly = $length%65535;

                // +ve length requires ... a captured group of length characters
                if ($Lx) $length_pattern = '(?:.{65535}){'.$Lx.'}';
                    $length_pattern = '('.$length_pattern.'.{'.$Ly.'})';

            } else if ($length < 0) {

                if ($length < ($offset - $strlen)) return '';

                $Lx = (int)((-$length)/65535);
                $Ly = (-$length)%65535;

                // -ve length requires ... capture everything except a group of -length characters
                //                         anchored at the tail-end of the string
                if ($Lx) $length_pattern = '(?:.{65535}){'.$Lx.'}';
                $length_pattern = '(.*)(?:'.$length_pattern.'.{'.$Ly.'})$';
            }
        }

        if (!preg_match('#'.$offset_pattern.$length_pattern.'#us',$str,$match)) return '';
        return $match[1];
    }
}

if(!function_exists('utf8_substr_replace')){
    /**
     * Unicode aware replacement for substr_replace()
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @see    substr_replace()
     */
    function utf8_substr_replace($string, $replacement, $start , $length=0 ){
        $ret = '';
        if($start>0) $ret .= utf8_substr($string, 0, $start);
        $ret .= $replacement;
        $ret .= utf8_substr($string, $start+$length);
        return $ret;
    }
}

if(!function_exists('utf8_ltrim')){
    /**
     * Unicode aware replacement for ltrim()
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @see    ltrim()
     * @return string
     */
    function utf8_ltrim($str,$charlist=''){
        if($charlist == '') return ltrim($str);

        //quote charlist for use in a characterclass
        $charlist = preg_replace('!([\\\\\\-\\]\\[/])!','\\\${1}',$charlist);

        return preg_replace('/^['.$charlist.']+/u','',$str);
    }
}

if(!function_exists('utf8_rtrim')){
    /**
     * Unicode aware replacement for rtrim()
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @see    rtrim()
     * @return string
     */
    function  utf8_rtrim($str,$charlist=''){
        if($charlist == '') return rtrim($str);

        //quote charlist for use in a characterclass
        $charlist = preg_replace('!([\\\\\\-\\]\\[/])!','\\\${1}',$charlist);

        return preg_replace('/['.$charlist.']+$/u','',$str);
    }
}

if(!function_exists('utf8_trim')){
    /**
     * Unicode aware replacement for trim()
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @see    trim()
     * @return string
     */
    function  utf8_trim($str,$charlist='') {
        if($charlist == '') return trim($str);

        return utf8_ltrim(utf8_rtrim($str,$charlist),$charlist);
    }
}

if(!function_exists('utf8_strtolower')){
    /**
     * This is a unicode aware replacement for strtolower()
     *
     * Uses mb_string extension if available
     *
     * @author Leo Feyer <leo@typolight.org>
     * @see    strtolower()
     * @see    utf8_strtoupper()
     */
    function utf8_strtolower($string){
        if(UTF8_MBSTRING) return mb_strtolower($string,'utf-8');

        global $UTF8_UPPER_TO_LOWER;
        return strtr($string,$UTF8_UPPER_TO_LOWER);
    }
}

if(!function_exists('utf8_strtoupper')){
    /**
     * This is a unicode aware replacement for strtoupper()
     *
     * Uses mb_string extension if available
     *
     * @author Leo Feyer <leo@typolight.org>
     * @see    strtoupper()
     * @see    utf8_strtoupper()
     */
    function utf8_strtoupper($string){
        if(UTF8_MBSTRING) return mb_strtoupper($string,'utf-8');

        global $UTF8_LOWER_TO_UPPER;
        return strtr($string,$UTF8_LOWER_TO_UPPER);
    }
}

if(!function_exists('utf8_ucfirst')){
    /**
     * UTF-8 aware alternative to ucfirst
     * Make a string's first character uppercase
     *
     * @author Harry Fuecks
     * @param string
     * @return string with first character as upper case (if applicable)
     */
    function utf8_ucfirst($str){
        switch ( utf8_strlen($str) ) {
            case 0:
                return '';
            case 1:
                return utf8_strtoupper($str);
            default:
                preg_match('/^(.{1})(.*)$/us', $str, $matches);
                //Something wrong... keep string!
                if(sizeof($matches) < 3) {
                	return $str;
                } 
                return utf8_strtoupper($matches[1]).$matches[2];
        }
    }
}

if(!function_exists('utf8_ucwords')){
    /**
     * UTF-8 aware alternative to ucwords
     * Uppercase the first character of each word in a string
     *
     * @author Harry Fuecks
     * @param string
     * @return string with first char of each word uppercase
     * @see http://www.php.net/ucwords
     */
    function utf8_ucwords($str) {
        // Note: [\x0c\x09\x0b\x0a\x0d\x20] matches;
        // form feeds, horizontal tabs, vertical tabs, linefeeds and carriage returns
        // This corresponds to the definition of a "word" defined at http://www.php.net/ucwords
        $pattern = '/(^|([\x0c\x09\x0b\x0a\x0d\x20]+))([^\x0c\x09\x0b\x0a\x0d\x20]{1})[^\x0c\x09\x0b\x0a\x0d\x20]*/u';

        return preg_replace_callback($pattern, 'utf8_ucwords_callback',$str);
    }

    /**
     * Callback function for preg_replace_callback call in utf8_ucwords
     * You don't need to call this yourself
     *
     * @author Harry Fuecks
     * @param array of matches corresponding to a single word
     * @return string with first char of the word in uppercase
     * @see utf8_ucwords
     * @see utf8_strtoupper
     */
    function utf8_ucwords_callback($matches) {
        $leadingws = $matches[2];
        $ucfirst = utf8_strtoupper($matches[3]);
        $ucword = utf8_substr_replace(ltrim($matches[0]),$ucfirst,0,1);
        return $leadingws . $ucword;
    }
}

if(!function_exists('utf8_deaccent')){
    /**
     * Replace accented UTF-8 characters by unaccented ASCII-7 equivalents
     *
     * Use the optional parameter to just deaccent lower ($case = -1) or upper ($case = 1)
     * letters. Default is to deaccent both cases ($case = 0)
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function utf8_deaccent($string,$case=0){
        if($case <= 0){
            global $UTF8_LOWER_ACCENTS;
            $string = strtr($string,$UTF8_LOWER_ACCENTS);
        }
        if($case >= 0){
            global $UTF8_UPPER_ACCENTS;
            $string = strtr($string,$UTF8_UPPER_ACCENTS);
        }
        return $string;
    }
}

if(!function_exists('utf8_romanize')){
    /**
     * Romanize a non-latin string
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    function utf8_romanize($string){
        if(utf8_isASCII($string)) return $string; //nothing to do

        global $UTF8_ROMANIZATION;
        return strtr($string,$UTF8_ROMANIZATION);
    }
}

if(!function_exists('utf8_stripspecials')){
    /**
     * Removes special characters (nonalphanumeric) from a UTF-8 string
     *
     * This function adds the controlchars 0x00 to 0x19 to the array of
     * stripped chars (they are not included in $UTF8_SPECIAL_CHARS)
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param  string $string     The UTF8 string to strip of special chars
     * @param  string $repl       Replace special with this string
     * @param  string $additional Additional chars to strip (used in regexp char class)
     */
    function utf8_stripspecials($string,$repl='',$additional=''){
        global $UTF8_SPECIAL_CHARS;
        global $UTF8_SPECIAL_CHARS2;

        static $specials = null;
        if(is_null($specials)){
            #$specials = preg_quote(unicode_to_utf8($UTF8_SPECIAL_CHARS), '/');
            $specials = preg_quote($UTF8_SPECIAL_CHARS2, '/');
        }

        return preg_replace('/['.$additional.'\x00-\x19'.$specials.']/u',$repl,$string);
    }
}

if(!function_exists('utf8_strpos')){
    /**
     * This is an Unicode aware replacement for strpos
     *
     * @author Leo Feyer <leo@typolight.org>
     * @see    strpos()
     * @param  string
     * @param  string
     * @param  integer
     * @return integer
     */
    function utf8_strpos($haystack, $needle, $offset=0){
        $comp = 0;
        $length = null;

        while (is_null($length) || $length < $offset) {
            $pos = strpos($haystack, $needle, $offset + $comp);

            if ($pos === false)
                return false;

            $length = utf8_strlen(substr($haystack, 0, $pos));

            if ($length < $offset)
                $comp = $pos - $length;
        }

        return $length;
    }
}

if(!function_exists('utf8_tohtml')){
    /**
     * Encodes UTF-8 characters to HTML entities
     *
     * @author Tom N Harris <tnharris@whoopdedo.org>
     * @author <vpribish at shopping dot com>
     * @link   http://www.php.net/manual/en/function.utf8-decode.php
     */
    function utf8_tohtml ($str) {
        $ret = '';
        foreach (utf8_to_unicode($str) as $cp) {
            if ($cp < 0x80)
                $ret .= chr($cp);
            elseif ($cp < 0x100)
                $ret .= "&#$cp;";
            else
                $ret .= '&#x'.dechex($cp).';';
        }
        return $ret;
    }
}

if(!function_exists('utf8_unhtml')){
    /**
     * Decodes HTML entities to UTF-8 characters
     *
     * Convert any &#..; entity to a codepoint,
     * The entities flag defaults to only decoding numeric entities.
     * Pass HTML_ENTITIES and named entities, including &amp; &lt; etc.
     * are handled as well. Avoids the problem that would occur if you
     * had to decode "&amp;#38;&#38;amp;#38;"
     *
     * unhtmlspecialchars(utf8_unhtml($s)) -> "&#38;&#38;"
     * utf8_unhtml(unhtmlspecialchars($s)) -> "&&amp#38;"
     * what it should be                   -> "&#38;&amp#38;"
     *
     * @author Tom N Harris <tnharris@whoopdedo.org>
     * @param  string  $str      UTF-8 encoded string
     * @param  boolean $entities Flag controlling decoding of named entities.
     * @return UTF-8 encoded string with numeric (and named) entities replaced.
     */
    function utf8_unhtml($str, $entities=null) {
        static $decoder = null;
        if (is_null($decoder))
            $decoder = new utf8_entity_decoder();
        if (is_null($entities))
            return preg_replace_callback('/(&#([Xx])?([0-9A-Za-z]+);)/m',
                                         'utf8_decode_numeric', $str);
        else
            return preg_replace_callback('/&(#)?([Xx])?([0-9A-Za-z]+);/m',
                                         array(&$decoder, 'decode'), $str);
    }
}

if(!function_exists('utf8_decode_numeric')){
    function utf8_decode_numeric($ent) {
        switch ($ent[2]) {
            case 'X':
            case 'x':
                $cp = hexdec($ent[3]);
                break;
            default:
                $cp = intval($ent[3]);
                break;
        }
        return unicode_to_utf8(array($cp));
    }
}

if(!class_exists('utf8_entity_decoder')){
    class utf8_entity_decoder {
        var $table;
        function utf8_entity_decoder() {
            $table = get_html_translation_table(HTML_ENTITIES);
            $table = array_flip($table);
            $this->table = array_map(array(&$this,'makeutf8'), $table);
        }
        function makeutf8($c) {
            return unicode_to_utf8(array(ord($c)));
        }
        function decode($ent) {
            if ($ent[1] == '#') {
                return utf8_decode_numeric($ent);
            } elseif (array_key_exists($ent[0],$this->table)) {
                return $this->table[$ent[0]];
            } else {
                return $ent[0];
            }
        }
    }
}

if(!function_exists('utf8_to_unicode')){
    /**
     * Takes an UTF-8 string and returns an array of ints representing the
     * Unicode characters. Astral planes are supported ie. the ints in the
     * output can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
     * are not allowed.
     *
     * If $strict is set to true the function returns false if the input
     * string isn't a valid UTF-8 octet sequence and raises a PHP error at
     * level E_USER_WARNING
     *
     * Note: this function has been modified slightly in this library to
     * trigger errors on encountering bad bytes
     *
     * @author <hsivonen@iki.fi>
     * @author Harry Fuecks <hfuecks@gmail.com>
     * @param  string  UTF-8 encoded string
     * @param  boolean Check for invalid sequences?
     * @return mixed array of unicode code points or false if UTF-8 invalid
     * @see    unicode_to_utf8
     * @link   http://hsivonen.iki.fi/php-utf8/
     * @link   http://sourceforge.net/projects/phputf8/
     */
    function utf8_to_unicode($str,$strict=false) {
        $mState = 0;     // cached expected number of octets after the current octet
                         // until the beginning of the next UTF8 character sequence
        $mUcs4  = 0;     // cached Unicode character
        $mBytes = 1;     // cached expected number of octets in the current sequence

        $out = array();

        $len = strlen($str);

        for($i = 0; $i < $len; $i++) {

            $in = ord($str{$i});

            if ( $mState == 0) {

                // When mState is zero we expect either a US-ASCII character or a
                // multi-octet sequence.
                if (0 == (0x80 & ($in))) {
                    // US-ASCII, pass straight through.
                    $out[] = $in;
                    $mBytes = 1;

                } else if (0xC0 == (0xE0 & ($in))) {
                    // First octet of 2 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x1F) << 6;
                    $mState = 1;
                    $mBytes = 2;

                } else if (0xE0 == (0xF0 & ($in))) {
                    // First octet of 3 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x0F) << 12;
                    $mState = 2;
                    $mBytes = 3;

                } else if (0xF0 == (0xF8 & ($in))) {
                    // First octet of 4 octet sequence
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x07) << 18;
                    $mState = 3;
                    $mBytes = 4;

                } else if (0xF8 == (0xFC & ($in))) {
                    /* First octet of 5 octet sequence.
                     *
                     * This is illegal because the encoded codepoint must be either
                     * (a) not the shortest form or
                     * (b) outside the Unicode range of 0-0x10FFFF.
                     * Rather than trying to resynchronize, we will carry on until the end
                     * of the sequence and let the later error handling code catch it.
                     */
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 0x03) << 24;
                    $mState = 4;
                    $mBytes = 5;

                } else if (0xFC == (0xFE & ($in))) {
                    // First octet of 6 octet sequence, see comments for 5 octet sequence.
                    $mUcs4 = ($in);
                    $mUcs4 = ($mUcs4 & 1) << 30;
                    $mState = 5;
                    $mBytes = 6;

                } elseif($strict) {
                    /* Current octet is neither in the US-ASCII range nor a legal first
                     * octet of a multi-octet sequence.
                     */
                    trigger_error(
                            'utf8_to_unicode: Illegal sequence identifier '.
                                'in UTF-8 at byte '.$i,
                            E_USER_WARNING
                        );
                    return false;

                }

            } else {

                // When mState is non-zero, we expect a continuation of the multi-octet
                // sequence
                if (0x80 == (0xC0 & ($in))) {

                    // Legal continuation.
                    $shift = ($mState - 1) * 6;
                    $tmp = $in;
                    $tmp = ($tmp & 0x0000003F) << $shift;
                    $mUcs4 |= $tmp;

                    /**
                     * End of the multi-octet sequence. mUcs4 now contains the final
                     * Unicode codepoint to be output
                     */
                    if (0 == --$mState) {

                        /*
                         * Check for illegal sequences and codepoints.
                         */
                        // From Unicode 3.1, non-shortest form is illegal
                        if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
                            ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
                            ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
                            (4 < $mBytes) ||
                            // From Unicode 3.2, surrogate characters are illegal
                            (($mUcs4 & 0xFFFFF800) == 0xD800) ||
                            // Codepoints outside the Unicode range are illegal
                            ($mUcs4 > 0x10FFFF)) {

                            if($strict){
                                trigger_error(
                                        'utf8_to_unicode: Illegal sequence or codepoint '.
                                            'in UTF-8 at byte '.$i,
                                        E_USER_WARNING
                                    );

                                return false;
                            }

                        }

                        if (0xFEFF != $mUcs4) {
                            // BOM is legal but we don't want to output it
                            $out[] = $mUcs4;
                        }

                        //initialize UTF8 cache
                        $mState = 0;
                        $mUcs4  = 0;
                        $mBytes = 1;
                    }

                } elseif($strict) {
                    /**
                     *((0xC0 & (*in) != 0x80) && (mState != 0))
                     * Incomplete multi-octet sequence.
                     */
                    trigger_error(
                            'utf8_to_unicode: Incomplete multi-octet '.
                            '   sequence in UTF-8 at byte '.$i,
                            E_USER_WARNING
                        );

                    return false;
                }
            }
        }
        return $out;
    }
}

if(!function_exists('unicode_to_utf8')){
    /**
     * Takes an array of ints representing the Unicode characters and returns
     * a UTF-8 string. Astral planes are supported ie. the ints in the
     * input can be > 0xFFFF. Occurrances of the BOM are ignored. Surrogates
     * are not allowed.
     *
     * If $strict is set to true the function returns false if the input
     * array contains ints that represent surrogates or are outside the
     * Unicode range and raises a PHP error at level E_USER_WARNING
     *
     * Note: this function has been modified slightly in this library to use
     * output buffering to concatenate the UTF-8 string (faster) as well as
     * reference the array by it's keys
     *
     * @param  array of unicode code points representing a string
     * @param  boolean Check for invalid sequences?
     * @return mixed UTF-8 string or false if array contains invalid code points
     * @author <hsivonen@iki.fi>
     * @author Harry Fuecks <hfuecks@gmail.com>
     * @see    utf8_to_unicode
     * @link   http://hsivonen.iki.fi/php-utf8/
     * @link   http://sourceforge.net/projects/phputf8/
     */
    function unicode_to_utf8($arr,$strict=false) {
        if (!is_array($arr)) return '';
        ob_start();

        foreach (array_keys($arr) as $k) {

            if ( ($arr[$k] >= 0) && ($arr[$k] <= 0x007f) ) {
                # ASCII range (including control chars)

                echo chr($arr[$k]);

            } else if ($arr[$k] <= 0x07ff) {
                # 2 byte sequence

                echo chr(0xc0 | ($arr[$k] >> 6));
                echo chr(0x80 | ($arr[$k] & 0x003f));

            } else if($arr[$k] == 0xFEFF) {
                # Byte order mark (skip)

                // nop -- zap the BOM

            } else if ($arr[$k] >= 0xD800 && $arr[$k] <= 0xDFFF) {
                # Test for illegal surrogates

                // found a surrogate
                if($strict){
                    trigger_error(
                        'unicode_to_utf8: Illegal surrogate '.
                            'at index: '.$k.', value: '.$arr[$k],
                        E_USER_WARNING
                        );
                    return false;
                }

            } else if ($arr[$k] <= 0xffff) {
                # 3 byte sequence

                echo chr(0xe0 | ($arr[$k] >> 12));
                echo chr(0x80 | (($arr[$k] >> 6) & 0x003f));
                echo chr(0x80 | ($arr[$k] & 0x003f));

            } else if ($arr[$k] <= 0x10ffff) {
                # 4 byte sequence

                echo chr(0xf0 | ($arr[$k] >> 18));
                echo chr(0x80 | (($arr[$k] >> 12) & 0x3f));
                echo chr(0x80 | (($arr[$k] >> 6) & 0x3f));
                echo chr(0x80 | ($arr[$k] & 0x3f));

            } elseif($strict) {

                trigger_error(
                    'unicode_to_utf8: Codepoint out of Unicode range '.
                        'at index: '.$k.', value: '.$arr[$k],
                    E_USER_WARNING
                    );

                // out of range
                return false;
            }
        }

        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
}

if(!function_exists('utf8_to_utf16be')){
    /**
     * UTF-8 to UTF-16BE conversion.
     *
     * Maybe really UCS-2 without mb_string due to utf8_to_unicode limits
     */
    function utf8_to_utf16be(&$str, $bom = false) {
        $out = $bom ? "\xFE\xFF" : '';
        if(UTF8_MBSTRING) return $out.mb_convert_encoding($str,'UTF-16BE','UTF-8');

        $uni = utf8_to_unicode($str);
        foreach($uni as $cp){
            $out .= pack('n',$cp);
        }
        return $out;
    }
}

if(!function_exists('utf16be_to_utf8')){
    /**
     * UTF-8 to UTF-16BE conversion.
     *
     * Maybe really UCS-2 without mb_string due to utf8_to_unicode limits
     */
    function utf16be_to_utf8(&$str) {
        $uni = unpack('n*',$str);
        return unicode_to_utf8($uni);
    }
}

if(!function_exists('utf8_bad_replace')){
    /**
     * Replace bad bytes with an alternative character
     *
     * ASCII character is recommended for replacement char
     *
     * PCRE Pattern to locate bad bytes in a UTF-8 string
     * Comes from W3 FAQ: Multilingual Forms
     * Note: modified to include full ASCII range including control chars
     *
     * @author Harry Fuecks <hfuecks@gmail.com>
     * @see http://www.w3.org/International/questions/qa-forms-utf-8
     * @param string to search
     * @param string to replace bad bytes with (defaults to '?') - use ASCII
     * @return string
     */
    function utf8_bad_replace($str, $replace = '') {
        $UTF8_BAD =
         '([\x00-\x7F]'.                          # ASCII (including control chars)
         '|[\xC2-\xDF][\x80-\xBF]'.               # non-overlong 2-byte
         '|\xE0[\xA0-\xBF][\x80-\xBF]'.           # excluding overlongs
         '|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.    # straight 3-byte
         '|\xED[\x80-\x9F][\x80-\xBF]'.           # excluding surrogates
         '|\xF0[\x90-\xBF][\x80-\xBF]{2}'.        # planes 1-3
         '|[\xF1-\xF3][\x80-\xBF]{3}'.            # planes 4-15
         '|\xF4[\x80-\x8F][\x80-\xBF]{2}'.        # plane 16
         '|(.{1}))';                              # invalid byte
        ob_start();
        while (preg_match('/'.$UTF8_BAD.'/S', $str, $matches)) {
            if ( !isset($matches[2])) {
                echo $matches[0];
            } else {
                echo $replace;
            }
            $str = substr($str,strlen($matches[0]));
        }
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }
}

if(!function_exists('utf8_correctIdx')){
    /**
     * adjust a byte index into a utf8 string to a utf8 character boundary
     *
     * @param $str   string   utf8 character string
     * @param $i     int      byte index into $str
     * @param $next  bool     direction to search for boundary,
     *                           false = up (current character)
     *                           true = down (next character)
     *
     * @return int            byte index into $str now pointing to a utf8 character boundary
     *
     * @author       chris smith <chris@jalakai.co.uk>
     */
    function utf8_correctIdx(&$str,$i,$next=false) {

        if ($i <= 0) return 0;

        $limit = strlen($str);
        if ($i>=$limit) return $limit;

        if ($next) {
            while (($i<$limit) && ((ord($str[$i]) & 0xC0) == 0x80)) $i++;
        } else {
            while ($i && ((ord($str[$i]) & 0xC0) == 0x80)) $i--;
        }

        return $i;
    }
}

// only needed if no mb_string available
if(!UTF8_MBSTRING){
    /**
     * UTF-8 Case lookup table
     *
     * This lookuptable defines the upper case letters to their correspponding
     * lower case letter in UTF-8
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    global $UTF8_LOWER_TO_UPPER;
    if(empty($UTF8_LOWER_TO_UPPER)) $UTF8_LOWER_TO_UPPER = array(
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","Æ’"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"S","Å¾"=>"Å½",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","Å¡"=>"Å ","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "Å“"=>"Å’","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"I","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","Ã¿"=>"Å¸","Ã¾"=>"Ãž","Ã½"=>"Ã�","Ã¼"=>"Ãœ","Ã»"=>"Ã›","Ãº"=>"Ãš","Ã¹"=>"Ã™","Ã¸"=>"Ã˜","Ã¶"=>"Ã–",
            "Ãµ"=>"Ã•","Ã´"=>"Ã”","Ã³"=>"Ã“","Ã²"=>"Ã’","Ã±"=>"Ã‘","Ã°"=>"Ã�","Ã¯"=>"Ã�","Ã®"=>"ÃŽ","Ã­"=>"Ã�","Ã¬"=>"ÃŒ",
            "Ã«"=>"Ã‹","Ãª"=>"ÃŠ","Ã©"=>"Ã‰","Ã¨"=>"Ãˆ","Ã§"=>"Ã‡","Ã¦"=>"Ã†","Ã¥"=>"Ã…","Ã¤"=>"Ã„","Ã£"=>"Ãƒ","Ã¢"=>"Ã‚",
            "Ã¡"=>"Ã�","Ã "=>"Ã€","Âµ"=>"?","z"=>"Z","y"=>"Y","x"=>"X","w"=>"W","v"=>"V","u"=>"U","t"=>"T",
            "s"=>"S","r"=>"R","q"=>"Q","p"=>"P","o"=>"O","n"=>"N","m"=>"M","l"=>"L","k"=>"K","j"=>"J",
            "i"=>"I","h"=>"H","g"=>"G","f"=>"F","e"=>"E","d"=>"D","c"=>"C","b"=>"B","a"=>"A"
                );

    /**
     * UTF-8 Case lookup table
     *
     * This lookuptable defines the lower case letters to their correspponding
     * upper case letter in UTF-8
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    global $UTF8_UPPER_TO_LOWER;
    if(empty($UTF8_UPPER_TO_LOWER)) $UTF8_UPPER_TO_LOWER = array (
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"Æ’","?"=>"?","?"=>"?","?"=>"?","?"=>"?","S"=>"?","Å½"=>"Å¾",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","Å "=>"Å¡","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "Å’"=>"Å“","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","I"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?","?"=>"?",
            "?"=>"?","Å¸"=>"Ã¿","Ãž"=>"Ã¾","Ã�"=>"Ã½","Ãœ"=>"Ã¼","Ã›"=>"Ã»","Ãš"=>"Ãº","Ã™"=>"Ã¹","Ã˜"=>"Ã¸","Ã–"=>"Ã¶",
            "Ã•"=>"Ãµ","Ã”"=>"Ã´","Ã“"=>"Ã³","Ã’"=>"Ã²","Ã‘"=>"Ã±","Ã�"=>"Ã°","Ã�"=>"Ã¯","ÃŽ"=>"Ã®","Ã�"=>"Ã­","ÃŒ"=>"Ã¬",
            "Ã‹"=>"Ã«","ÃŠ"=>"Ãª","Ã‰"=>"Ã©","Ãˆ"=>"Ã¨","Ã‡"=>"Ã§","Ã†"=>"Ã¦","Ã…"=>"Ã¥","Ã„"=>"Ã¤","Ãƒ"=>"Ã£","Ã‚"=>"Ã¢",
            "Ã�"=>"Ã¡","Ã€"=>"Ã ","?"=>"Âµ","Z"=>"z","Y"=>"y","X"=>"x","W"=>"w","V"=>"v","U"=>"u","T"=>"t",
            "S"=>"s","R"=>"r","Q"=>"q","P"=>"p","O"=>"o","N"=>"n","M"=>"m","L"=>"l","K"=>"k","J"=>"j",
            "I"=>"i","H"=>"h","G"=>"g","F"=>"f","E"=>"e","D"=>"d","C"=>"c","B"=>"b","A"=>"a"
                );
}; // end of case lookup tables

/**
 * UTF-8 lookup table for lower case accented letters
 *
 * This lookuptable defines replacements for accented characters from the ASCII-7
 * range. This are lower case letters only.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    utf8_deaccent()
 */
global $UTF8_LOWER_ACCENTS;
if(empty($UTF8_LOWER_ACCENTS)) $UTF8_LOWER_ACCENTS = array(
  'Ã ' => 'a', 'Ã´' => 'o', '?' => 'd', '?' => 'f', 'Ã«' => 'e', 'Å¡' => 's', '?' => 'o',
  'ÃŸ' => 'ss', '?' => 'a', '?' => 'r', '?' => 't', '?' => 'n', '?' => 'a', '?' => 'k',
  '?' => 's', '?' => 'y', '?' => 'n', '?' => 'l', '?' => 'h', '?' => 'p', 'Ã³' => 'o',
  'Ãº' => 'u', '?' => 'e', 'Ã©' => 'e', 'Ã§' => 'c', '?' => 'w', '?' => 'c', 'Ãµ' => 'o',
  '?' => 's', 'Ã¸' => 'o', '?' => 'g', '?' => 't', '?' => 's', '?' => 'e', '?' => 'c',
  '?' => 's', 'Ã®' => 'i', '?' => 'u', '?' => 'c', '?' => 'e', '?' => 'w', '?' => 't',
  '?' => 'u', '?' => 'c', 'Ã¶' => 'oe', 'Ã¨' => 'e', '?' => 'y', '?' => 'a', '?' => 'l',
  '?' => 'u', '?' => 'u', '?' => 's', '?' => 'g', '?' => 'l', 'Æ’' => 'f', 'Å¾' => 'z',
  '?' => 'w', '?' => 'b', 'Ã¥' => 'a', 'Ã¬' => 'i', 'Ã¯' => 'i', '?' => 'd', '?' => 't',
  '?' => 'r', 'Ã¤' => 'ae', 'Ã­' => 'i', '?' => 'r', 'Ãª' => 'e', 'Ã¼' => 'ue', 'Ã²' => 'o',
  '?' => 'e', 'Ã±' => 'n', '?' => 'n', '?' => 'h', '?' => 'g', '?' => 'd', '?' => 'j',
  'Ã¿' => 'y', '?' => 'u', '?' => 'u', '?' => 'u', '?' => 't', 'Ã½' => 'y', '?' => 'o',
  'Ã¢' => 'a', '?' => 'l', '?' => 'w', '?' => 'z', '?' => 'i', 'Ã£' => 'a', '?' => 'g',
  '?' => 'm', '?' => 'o', '?' => 'i', 'Ã¹' => 'u', '?' => 'i', '?' => 'z', 'Ã¡' => 'a',
  'Ã»' => 'u', 'Ã¾' => 'th', 'Ã°' => 'dh', 'Ã¦' => 'ae', 'Âµ' => 'u', '?' => 'e',
);

/**
 * UTF-8 lookup table for upper case accented letters
 *
 * This lookuptable defines replacements for accented characters from the ASCII-7
 * range. This are upper case letters only.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    utf8_deaccent()
 */
global $UTF8_UPPER_ACCENTS;
if(empty($UTF8_UPPER_ACCENTS)) $UTF8_UPPER_ACCENTS = array(
  'Ã€' => 'A', 'Ã”' => 'O', '?' => 'D', '?' => 'F', 'Ã‹' => 'E', 'Å ' => 'S', '?' => 'O',
  '?' => 'A', '?' => 'R', '?' => 'T', '?' => 'N', '?' => 'A', '?' => 'K',
  '?' => 'S', '?' => 'Y', '?' => 'N', '?' => 'L', '?' => 'H', '?' => 'P', 'Ã“' => 'O',
  'Ãš' => 'U', '?' => 'E', 'Ã‰' => 'E', 'Ã‡' => 'C', '?' => 'W', '?' => 'C', 'Ã•' => 'O',
  '?' => 'S', 'Ã˜' => 'O', '?' => 'G', '?' => 'T', '?' => 'S', '?' => 'E', '?' => 'C',
  '?' => 'S', 'ÃŽ' => 'I', '?' => 'U', '?' => 'C', '?' => 'E', '?' => 'W', '?' => 'T',
  '?' => 'U', '?' => 'C', 'Ã–' => 'Oe', 'Ãˆ' => 'E', '?' => 'Y', '?' => 'A', '?' => 'L',
  '?' => 'U', '?' => 'U', '?' => 'S', '?' => 'G', '?' => 'L', '?' => 'F', 'Å½' => 'Z',
  '?' => 'W', '?' => 'B', 'Ã…' => 'A', 'ÃŒ' => 'I', 'Ã�' => 'I', '?' => 'D', '?' => 'T',
  '?' => 'R', 'Ã„' => 'Ae', 'Ã�' => 'I', '?' => 'R', 'ÃŠ' => 'E', 'Ãœ' => 'Ue', 'Ã’' => 'O',
  '?' => 'E', 'Ã‘' => 'N', '?' => 'N', '?' => 'H', '?' => 'G', '?' => 'D', '?' => 'J',
  'Å¸' => 'Y', '?' => 'U', '?' => 'U', '?' => 'U', '?' => 'T', 'Ã�' => 'Y', '?' => 'O',
  'Ã‚' => 'A', '?' => 'L', '?' => 'W', '?' => 'Z', '?' => 'I', 'Ãƒ' => 'A', '?' => 'G',
  '?' => 'M', '?' => 'O', '?' => 'I', 'Ã™' => 'U', '?' => 'I', '?' => 'Z', 'Ã�' => 'A',
  'Ã›' => 'U', 'Ãž' => 'Th', 'Ã�' => 'Dh', 'Ã†' => 'Ae', '?' => 'E',
);

/**
 * UTF-8 array of common special characters
 *
 * This array should contain all special characters (not a letter or digit)
 * defined in the various local charsets - it's not a complete list of non-alphanum
 * characters in UTF-8. It's not perfect but should match most cases of special
 * chars.
 *
 * The controlchars 0x00 to 0x19 are _not_ included in this array. The space 0x20 is!
 * These chars are _not_ in the array either:  _ (0x5f), : 0x3a, . 0x2e, - 0x2d, * 0x2a
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    utf8_stripspecials()
 */
global $UTF8_SPECIAL_CHARS;
if(empty($UTF8_SPECIAL_CHARS)) $UTF8_SPECIAL_CHARS = array(
  0x001a, 0x001b, 0x001c, 0x001d, 0x001e, 0x001f, 0x0020, 0x0021, 0x0022, 0x0023,
  0x0024, 0x0025, 0x0026, 0x0027, 0x0028, 0x0029,         0x002b, 0x002c,
          0x002f,         0x003b, 0x003c, 0x003d, 0x003e, 0x003f, 0x0040, 0x005b,
  0x005c, 0x005d, 0x005e,         0x0060, 0x007b, 0x007c, 0x007d, 0x007e,
  0x007f, 0x0080, 0x0081, 0x0082, 0x0083, 0x0084, 0x0085, 0x0086, 0x0087, 0x0088,
  0x0089, 0x008a, 0x008b, 0x008c, 0x008d, 0x008e, 0x008f, 0x0090, 0x0091, 0x0092,
  0x0093, 0x0094, 0x0095, 0x0096, 0x0097, 0x0098, 0x0099, 0x009a, 0x009b, 0x009c,
  0x009d, 0x009e, 0x009f, 0x00a0, 0x00a1, 0x00a2, 0x00a3, 0x00a4, 0x00a5, 0x00a6,
  0x00a7, 0x00a8, 0x00a9, 0x00aa, 0x00ab, 0x00ac, 0x00ad, 0x00ae, 0x00af, 0x00b0,
  0x00b1, 0x00b2, 0x00b3, 0x00b4, 0x00b5, 0x00b6, 0x00b7, 0x00b8, 0x00b9, 0x00ba,
  0x00bb, 0x00bc, 0x00bd, 0x00be, 0x00bf, 0x00d7, 0x00f7, 0x02c7, 0x02d8, 0x02d9,
  0x02da, 0x02db, 0x02dc, 0x02dd, 0x0300, 0x0301, 0x0303, 0x0309, 0x0323, 0x0384,
  0x0385, 0x0387, 0x03c6, 0x03d1, 0x03d2, 0x03d5, 0x03d6, 0x05b0, 0x05b1,
  0x05b2, 0x05b3, 0x05b4, 0x05b5, 0x05b6, 0x05b7, 0x05b8, 0x05b9, 0x05bb, 0x05bc,
  0x05bd, 0x05be, 0x05bf, 0x05c0, 0x05c1, 0x05c2, 0x05c3, 0x05f3, 0x05f4, 0x060c,
  0x061b, 0x061f, 0x0640, 0x064b, 0x064c, 0x064d, 0x064e, 0x064f, 0x0650, 0x0651,
  0x0652, 0x066a, 0x0e3f, 0x200c, 0x200d, 0x200e, 0x200f, 0x2013, 0x2014, 0x2015,
  0x2017, 0x2018, 0x2019, 0x201a, 0x201c, 0x201d, 0x201e, 0x2020, 0x2021, 0x2022,
  0x2026, 0x2030, 0x2032, 0x2033, 0x2039, 0x203a, 0x2044, 0x20a7, 0x20aa, 0x20ab,
  0x20ac, 0x2116, 0x2118, 0x2122, 0x2126, 0x2135, 0x2190, 0x2191, 0x2192, 0x2193,
  0x2194, 0x2195, 0x21b5, 0x21d0, 0x21d1, 0x21d2, 0x21d3, 0x21d4, 0x2200, 0x2202,
  0x2203, 0x2205, 0x2206, 0x2207, 0x2208, 0x2209, 0x220b, 0x220f, 0x2211, 0x2212,
  0x2215, 0x2217, 0x2219, 0x221a, 0x221d, 0x221e, 0x2220, 0x2227, 0x2228, 0x2229,
  0x222a, 0x222b, 0x2234, 0x223c, 0x2245, 0x2248, 0x2260, 0x2261, 0x2264, 0x2265,
  0x2282, 0x2283, 0x2284, 0x2286, 0x2287, 0x2295, 0x2297, 0x22a5, 0x22c5, 0x2310,
  0x2320, 0x2321, 0x2329, 0x232a, 0x2469, 0x2500, 0x2502, 0x250c, 0x2510, 0x2514,
  0x2518, 0x251c, 0x2524, 0x252c, 0x2534, 0x253c, 0x2550, 0x2551, 0x2552, 0x2553,
  0x2554, 0x2555, 0x2556, 0x2557, 0x2558, 0x2559, 0x255a, 0x255b, 0x255c, 0x255d,
  0x255e, 0x255f, 0x2560, 0x2561, 0x2562, 0x2563, 0x2564, 0x2565, 0x2566, 0x2567,
  0x2568, 0x2569, 0x256a, 0x256b, 0x256c, 0x2580, 0x2584, 0x2588, 0x258c, 0x2590,
  0x2591, 0x2592, 0x2593, 0x25a0, 0x25b2, 0x25bc, 0x25c6, 0x25ca, 0x25cf, 0x25d7,
  0x2605, 0x260e, 0x261b, 0x261e, 0x2660, 0x2663, 0x2665, 0x2666, 0x2701, 0x2702,
  0x2703, 0x2704, 0x2706, 0x2707, 0x2708, 0x2709, 0x270c, 0x270d, 0x270e, 0x270f,
  0x2710, 0x2711, 0x2712, 0x2713, 0x2714, 0x2715, 0x2716, 0x2717, 0x2718, 0x2719,
  0x271a, 0x271b, 0x271c, 0x271d, 0x271e, 0x271f, 0x2720, 0x2721, 0x2722, 0x2723,
  0x2724, 0x2725, 0x2726, 0x2727, 0x2729, 0x272a, 0x272b, 0x272c, 0x272d, 0x272e,
  0x272f, 0x2730, 0x2731, 0x2732, 0x2733, 0x2734, 0x2735, 0x2736, 0x2737, 0x2738,
  0x2739, 0x273a, 0x273b, 0x273c, 0x273d, 0x273e, 0x273f, 0x2740, 0x2741, 0x2742,
  0x2743, 0x2744, 0x2745, 0x2746, 0x2747, 0x2748, 0x2749, 0x274a, 0x274b, 0x274d,
  0x274f, 0x2750, 0x2751, 0x2752, 0x2756, 0x2758, 0x2759, 0x275a, 0x275b, 0x275c,
  0x275d, 0x275e, 0x2761, 0x2762, 0x2763, 0x2764, 0x2765, 0x2766, 0x2767, 0x277f,
  0x2789, 0x2793, 0x2794, 0x2798, 0x2799, 0x279a, 0x279b, 0x279c, 0x279d, 0x279e,
  0x279f, 0x27a0, 0x27a1, 0x27a2, 0x27a3, 0x27a4, 0x27a5, 0x27a6, 0x27a7, 0x27a8,
  0x27a9, 0x27aa, 0x27ab, 0x27ac, 0x27ad, 0x27ae, 0x27af, 0x27b1, 0x27b2, 0x27b3,
  0x27b4, 0x27b5, 0x27b6, 0x27b7, 0x27b8, 0x27b9, 0x27ba, 0x27bb, 0x27bc, 0x27bd,
  0x27be, 0x3000, 0x3001, 0x3002, 0x3003, 0x3008, 0x3009, 0x300a, 0x300b, 0x300c,
  0x300d, 0x300e, 0x300f, 0x3010, 0x3011, 0x3012, 0x3014, 0x3015, 0x3016, 0x3017,
  0x3018, 0x3019, 0x301a, 0x301b, 0x3036,
  0xf6d9, 0xf6da, 0xf6db, 0xf8d7, 0xf8d8, 0xf8d9, 0xf8da, 0xf8db, 0xf8dc,
  0xf8dd, 0xf8de, 0xf8df, 0xf8e0, 0xf8e1, 0xf8e2, 0xf8e3, 0xf8e4, 0xf8e5, 0xf8e6,
  0xf8e7, 0xf8e8, 0xf8e9, 0xf8ea, 0xf8eb, 0xf8ec, 0xf8ed, 0xf8ee, 0xf8ef, 0xf8f0,
  0xf8f1, 0xf8f2, 0xf8f3, 0xf8f4, 0xf8f5, 0xf8f6, 0xf8f7, 0xf8f8, 0xf8f9, 0xf8fa,
  0xf8fb, 0xf8fc, 0xf8fd, 0xf8fe, 0xfe7c, 0xfe7d,
          0xff01, 0xff02, 0xff03, 0xff04, 0xff05, 0xff06, 0xff07, 0xff08, 0xff09,
  0xff09, 0xff0a, 0xff0b, 0xff0c, 0xff0d, 0xff0e, 0xff0f, 0xff1a, 0xff1b, 0xff1c,
  0xff1d, 0xff1e, 0xff1f, 0xff20, 0xff3b, 0xff3c, 0xff3d, 0xff3e, 0xff40, 0xff5b,
  0xff5c, 0xff5d, 0xff5e, 0xff5f, 0xff60, 0xff61, 0xff62, 0xff63, 0xff64, 0xff65,
  0xffe0, 0xffe1, 0xffe2, 0xffe3, 0xffe4, 0xffe5, 0xffe6, 0xffe8, 0xffe9, 0xffea,
  0xffeb, 0xffec, 0xffed, 0xffee,
  0x01d6fc, 0x01d6fd, 0x01d6fe, 0x01d6ff, 0x01d700, 0x01d701, 0x01d702, 0x01d703,
  0x01d704, 0x01d705, 0x01d706, 0x01d707, 0x01d708, 0x01d709, 0x01d70a, 0x01d70b,
  0x01d70c, 0x01d70d, 0x01d70e, 0x01d70f, 0x01d710, 0x01d711, 0x01d712, 0x01d713,
  0x01d714, 0x01d715, 0x01d716, 0x01d717, 0x01d718, 0x01d719, 0x01d71a, 0x01d71b,
  0xc2a0, 0xe28087, 0xe280af, 0xe281a0, 0xefbbbf,
);

// utf8 version of above data
global $UTF8_SPECIAL_CHARS2;
if(empty($UTF8_SPECIAL_CHARS2)) $UTF8_SPECIAL_CHARS2 =
    "\x1A".' !"#$%&\'()+,/;<=>?@[\]^`{|}~?Â�???????????Â�?Â�Â�??????'.
    '???????Â�?? Â¡Â¢Â£Â¤Â¥Â¦Â§Â¨Â©ÂªÂ«Â¬Â­Â®Â¯Â°Â±Â²Â³Â´ÂµÂ¶Â·Â¸Â¹ÂºÂ»Â¼Â½?'.
    '?Â¿Ã—Ã·?????Ëœ??????????????????????????'.
    '????????????????????????â€“â€”??â€˜â€™â€šâ€œâ€�?'.
    '??â€ â€¡â€¢â€¦â€°??â€¹â€º????â‚¬??â„¢?????????'.
    '???????????????????????????'.
    '????????????????????????????'.
    '????????????????????????????'.
    '???????????????????????????'.
    '????????????????????????????'.
    '????????????????????????????'.
    '???????????????????????????'.
    '????????????????????????????'.
    '????????????????????????????'.
    '????????'.
    '????????????????????????'.
    '???????????????????'.
    '???????????????????????????'.
    '???????????????????????????????'.
    '?????????????????????'.
    '????????????????????????????????????????????????????????????????'.
    ' ????';

/**
 * Romanization lookup table
 *
 * This lookup tables provides a way to transform strings written in a language
 * different from the ones based upon latin letters into plain ASCII.
 *
 * Please note: this is not a scientific transliteration table. It only works
 * oneway from nonlatin to ASCII and it works by simple character replacement
 * only. Specialities of each language are not supported.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Vitaly Blokhin <vitinfo@vitn.com>
 * @link   http://www.uconv.com/translit.htm
 * @author Bisqwit <bisqwit@iki.fi>
 * @link   http://kanjidict.stc.cx/hiragana.php?src=2
 * @link   http://www.translatum.gr/converter/greek-transliteration.htm
 * @link   http://en.wikipedia.org/wiki/Royal_Thai_General_System_of_Transcription
 * @link   http://www.btranslations.com/resources/romanization/korean.asp
 * @author Arthit Suriyawongkul <arthit@gmail.com>
 * @author Denis Scheither <amorphis@uni-bremen.de>
 */
global $UTF8_ROMANIZATION;
if(empty($UTF8_ROMANIZATION)) $UTF8_ROMANIZATION = array(
  // scandinavian - differs from what we do in deaccent
  'Ã¥'=>'a','Ã…'=>'A','Ã¤'=>'a','Ã„'=>'A','Ã¶'=>'o','Ã–'=>'O',

  //russian cyrillic
  '?'=>'a','?'=>'A','?'=>'b','?'=>'B','?'=>'v','?'=>'V','?'=>'g','?'=>'G',
  '?'=>'d','?'=>'D','?'=>'e','?'=>'E','?'=>'jo','?'=>'Jo','?'=>'zh','?'=>'Zh',
  '?'=>'z','?'=>'Z','?'=>'i','?'=>'I','?'=>'j','?'=>'J','?'=>'k','?'=>'K',
  '?'=>'l','?'=>'L','?'=>'m','?'=>'M','?'=>'n','?'=>'N','?'=>'o','?'=>'O',
  '?'=>'p','?'=>'P','?'=>'r','?'=>'R','?'=>'s','?'=>'S','?'=>'t','?'=>'T',
  '?'=>'u','?'=>'U','?'=>'f','?'=>'F','?'=>'x','?'=>'X','?'=>'c','?'=>'C',
  '?'=>'ch','?'=>'Ch','?'=>'sh','?'=>'Sh','?'=>'sch','?'=>'Sch','?'=>'',
  '?'=>'','?'=>'y','?'=>'Y','?'=>'','?'=>'','?'=>'eh','?'=>'Eh','?'=>'ju',
  '?'=>'Ju','?'=>'ja','?'=>'Ja',
  // Ukrainian cyrillic
  '?'=>'Gh','?'=>'gh','?'=>'Je','?'=>'je','?'=>'I','?'=>'i','?'=>'Ji','?'=>'ji',
  // Georgian
  '?'=>'a','?'=>'b','?'=>'g','?'=>'d','?'=>'e','?'=>'v','?'=>'z','?'=>'th',
  '?'=>'i','?'=>'p','?'=>'l','?'=>'m','?'=>'n','?'=>'o','?'=>'p','?'=>'zh',
  '?'=>'r','?'=>'s','?'=>'t','?'=>'u','?'=>'ph','?'=>'kh','?'=>'gh','?'=>'q',
  '?'=>'sh','?'=>'ch','?'=>'c','?'=>'dh','?'=>'w','?'=>'j','?'=>'x','?'=>'jh',
  '?'=>'xh',
  //Sanskrit
  '?'=>'a','?'=>'ah','?'=>'i','?'=>'ih','?'=>'u','?'=>'uh','?'=>'ry',
  '?'=>'ryh','?'=>'ly','?'=>'lyh','?'=>'e','?'=>'ay','?'=>'o','?'=>'aw',
  '??'=>'amh','??'=>'aq','?'=>'k','?'=>'kh','?'=>'g','?'=>'gh','?'=>'nh',
  '?'=>'c','?'=>'ch','?'=>'j','?'=>'jh','?'=>'ny','?'=>'tq','?'=>'tqh',
  '?'=>'dq','?'=>'dqh','?'=>'nq','?'=>'t','?'=>'th','?'=>'d','?'=>'dh',
  '?'=>'n','?'=>'p','?'=>'ph','?'=>'b','?'=>'bh','?'=>'m','?'=>'z','?'=>'r',
  '?'=>'l','?'=>'v','?'=>'sh','?'=>'sqh','?'=>'s','?'=>'x',
  //Hebrew
  '?'=>'a', '?'=>'b','?'=>'g','?'=>'d','?'=>'h','?'=>'v','?'=>'z','?'=>'kh','?'=>'th',
  '?'=>'y','?'=>'h','?'=>'k','?'=>'l','?'=>'m','?'=>'m','?'=>'n','?'=>'n',
  '?'=>'s','?'=>'ah','?'=>'f','?'=>'p','?'=>'c','?'=>'c','?'=>'q','?'=>'r',
  '?'=>'sh','?'=>'t',
  //Arabic
  '?'=>'a','?'=>'b','?'=>'t','?'=>'th','?'=>'g','?'=>'xh','?'=>'x','?'=>'d',
  '?'=>'dh','?'=>'r','?'=>'z','?'=>'s','?'=>'sh','?'=>'s\'','?'=>'d\'',
  '?'=>'t\'','?'=>'z\'','?'=>'y','?'=>'gh','?'=>'f','?'=>'q','?'=>'k',
  '?'=>'l','?'=>'m','?'=>'n','?'=>'x\'','?'=>'u','?'=>'i',

  // Japanese characters  (last update: 2008-05-09)

  // Japanese hiragana

  // 3 character syllables, ? doubles the consonant after
  '???'=>'ccha','???'=>'cche','???'=>'ccho','???'=>'cchu',
  '???'=>'bbya','???'=>'bbye','???'=>'bbyi','???'=>'bbyo','???'=>'bbyu',
  '???'=>'ppya','???'=>'ppye','???'=>'ppyi','???'=>'ppyo','???'=>'ppyu',
  '???'=>'ccha','???'=>'cche','??'=>'cchi','???'=>'ccho','???'=>'cchu',
  // '???'=>'hya','???'=>'hye','???'=>'hyi','???'=>'hyo','???'=>'hyu',
  '???'=>'kkya','???'=>'kkye','???'=>'kkyi','???'=>'kkyo','???'=>'kkyu',
  '???'=>'ggya','???'=>'ggye','???'=>'ggyi','???'=>'ggyo','???'=>'ggyu',
  '???'=>'mmya','???'=>'mmye','???'=>'mmyi','???'=>'mmyo','???'=>'mmyu',
  '???'=>'nnya','???'=>'nnye','???'=>'nnyi','???'=>'nnyo','???'=>'nnyu',
  '???'=>'rrya','???'=>'rrye','???'=>'rryi','???'=>'rryo','???'=>'rryu',
  '???'=>'ssha','???'=>'sshe','??'=>'sshi','???'=>'ssho','???'=>'sshu',

  // seperate hiragana 'n' ('n' + 'i' != 'ni', normally we would write "kon'nichi wa" but the apostrophe would be converted to _ anyway)
  '??'=>'n_a','??'=>'n_e','??'=>'n_i','??'=>'n_o','??'=>'n_u',
  '??'=>'n_ya','??'=>'n_yo','??'=>'n_yu',

   // 2 character syllables - normal
  '??'=>'fa','??'=>'fe','??'=>'fi','??'=>'fo',
  '??'=>'cha','??'=>'che','?'=>'chi','??'=>'cho','??'=>'chu',
  '??'=>'hya','??'=>'hye','??'=>'hyi','??'=>'hyo','??'=>'hyu',
  '??'=>'bya','??'=>'bye','??'=>'byi','??'=>'byo','??'=>'byu',
  '??'=>'pya','??'=>'pye','??'=>'pyi','??'=>'pyo','??'=>'pyu',
  '??'=>'kya','??'=>'kye','??'=>'kyi','??'=>'kyo','??'=>'kyu',
  '??'=>'gya','??'=>'gye','??'=>'gyi','??'=>'gyo','??'=>'gyu',
  '??'=>'mya','??'=>'mye','??'=>'myi','??'=>'myo','??'=>'myu',
  '??'=>'nya','??'=>'nye','??'=>'nyi','??'=>'nyo','??'=>'nyu',
  '??'=>'rya','??'=>'rye','??'=>'ryi','??'=>'ryo','??'=>'ryu',
  '??'=>'sha','??'=>'she','?'=>'shi','??'=>'sho','??'=>'shu',
  '??'=>'ja','??'=>'je','??'=>'jo','??'=>'ju',
  '??'=>'we','??'=>'wi',
  '??'=>'ye',

  // 2 character syllables, ? doubles the consonant after
  '??'=>'bba','??'=>'bbe','??'=>'bbi','??'=>'bbo','??'=>'bbu',
  '??'=>'ppa','??'=>'ppe','??'=>'ppi','??'=>'ppo','??'=>'ppu',
  '??'=>'tta','??'=>'tte','??'=>'cchi','??'=>'tto','??'=>'ttsu',
  '??'=>'dda','??'=>'dde','??'=>'ddi','??'=>'ddo','??'=>'ddu',
  '??'=>'gga','??'=>'gge','??'=>'ggi','??'=>'ggo','??'=>'ggu',
  '??'=>'kka','??'=>'kke','??'=>'kki','??'=>'kko','??'=>'kku',
  '??'=>'mma','??'=>'mme','??'=>'mmi','??'=>'mmo','??'=>'mmu',
  '??'=>'nna','??'=>'nne','??'=>'nni','??'=>'nno','??'=>'nnu',
  '??'=>'rra','??'=>'rre','??'=>'rri','??'=>'rro','??'=>'rru',
  '??'=>'ssa','??'=>'sse','??'=>'sshi','??'=>'sso','??'=>'ssu',
  '??'=>'zza','??'=>'zze','??'=>'jji','??'=>'zzo','??'=>'zzu',

  // 1 character syllabels
  '?'=>'a','?'=>'e','?'=>'i','?'=>'o','?'=>'u','?'=>'n',
  '?'=>'ha','?'=>'he','?'=>'hi','?'=>'ho','?'=>'fu',
  '?'=>'ba','?'=>'be','?'=>'bi','?'=>'bo','?'=>'bu',
  '?'=>'pa','?'=>'pe','?'=>'pi','?'=>'po','?'=>'pu',
  '?'=>'ta','?'=>'te','?'=>'chi','?'=>'to','?'=>'tsu',
  '?'=>'da','?'=>'de','?'=>'di','?'=>'do','?'=>'du',
  '?'=>'ga','?'=>'ge','?'=>'gi','?'=>'go','?'=>'gu',
  '?'=>'ka','?'=>'ke','?'=>'ki','?'=>'ko','?'=>'ku',
  '?'=>'ma','?'=>'me','?'=>'mi','?'=>'mo','?'=>'mu',
  '?'=>'na','?'=>'ne','?'=>'ni','?'=>'no','?'=>'nu',
  '?'=>'ra','?'=>'re','?'=>'ri','?'=>'ro','?'=>'ru',
  '?'=>'sa','?'=>'se','?'=>'shi','?'=>'so','?'=>'su',
  '?'=>'wa','?'=>'wo',
  '?'=>'za','?'=>'ze','?'=>'ji','?'=>'zo','?'=>'zu',
  '?'=>'ya','?'=>'yo','?'=>'yu',
  // old characters
  '?'=>'we','?'=>'wi',

  //  convert what's left (probably only kicks in when something's missing above)
  // '?'=>'a','?'=>'e','?'=>'i','?'=>'o','?'=>'u',
  // '?'=>'ya','?'=>'yo','?'=>'yu',

  // never seen one of those (disabled for the moment)
  // '??'=>'va','??'=>'ve','??'=>'vi','??'=>'vo','?'=>'vu',
  // '??'=>'dha','??'=>'dhe','??'=>'dhi','??'=>'dho','??'=>'dhu',
  // '??'=>'dwa','??'=>'dwe','??'=>'dwi','??'=>'dwo','??'=>'dwu',
  // '??'=>'dya','??'=>'dye','??'=>'dyi','??'=>'dyo','??'=>'dyu',
  // '??'=>'fwa','??'=>'fwe','??'=>'fwi','??'=>'fwo','??'=>'fwu',
  // '??'=>'fya','??'=>'fye','??'=>'fyi','??'=>'fyo','??'=>'fyu',
  // '??'=>'swa','??'=>'swe','??'=>'swi','??'=>'swo','??'=>'swu',
  // '??'=>'tha','??'=>'the','??'=>'thi','??'=>'tho','??'=>'thu',
  // '??'=>'tsa','??'=>'tse','??'=>'tsi','??'=>'tso','?'=>'tsu',
  // '??'=>'twa','??'=>'twe','??'=>'twi','??'=>'two','??'=>'twu',
  // '??'=>'vya','??'=>'vye','??'=>'vyi','??'=>'vyo','??'=>'vyu',
  // '??'=>'wha','??'=>'whe','??'=>'whi','??'=>'who','??'=>'whu',
  // '??'=>'zha','??'=>'zhe','??'=>'zhi','??'=>'zho','??'=>'zhu',
  // '??'=>'zya','??'=>'zye','??'=>'zyi','??'=>'zyo','??'=>'zyu',

  // 'spare' characters from other romanization systems
  // '?'=>'da','?'=>'de','?'=>'di','?'=>'do','?'=>'du',
  // '?'=>'la','?'=>'le','?'=>'li','?'=>'lo','?'=>'lu',
  // '?'=>'sa','?'=>'se','?'=>'si','?'=>'so','?'=>'su',
  // '??'=>'cya','??'=>'cye','??'=>'cyi','??'=>'cyo','??'=>'cyu',
  //'??'=>'jya','??'=>'jye','??'=>'jyi','??'=>'jyo','??'=>'jyu',
  //'??'=>'lya','??'=>'lye','??'=>'lyi','??'=>'lyo','??'=>'lyu',
  //'??'=>'sya','??'=>'sye','??'=>'syi','??'=>'syo','??'=>'syu',
  //'??'=>'tya','??'=>'tye','??'=>'tyi','??'=>'tyo','??'=>'tyu',
  //'?'=>'ci',,?'=>'yi','?'=>'dzi',
  //'???'=>'jja','???'=>'jje','??'=>'jji','???'=>'jjo','???'=>'jju',


  // Japanese katakana

  // 4 character syllables: ? doubles the consonant after, ? doubles the vowel before (usualy written with macron, but we don't want that in our URLs)
  '????'=>'bbyaa','????'=>'bbyee','????'=>'bbyii','????'=>'bbyoo','????'=>'bbyuu',
  '????'=>'ppyaa','????'=>'ppyee','????'=>'ppyii','????'=>'ppyoo','????'=>'ppyuu',
  '????'=>'kkyaa','????'=>'kkyee','????'=>'kkyii','????'=>'kkyoo','????'=>'kkyuu',
  '????'=>'ggyaa','????'=>'ggyee','????'=>'ggyii','????'=>'ggyoo','????'=>'ggyuu',
  '????'=>'mmyaa','????'=>'mmyee','????'=>'mmyii','????'=>'mmyoo','????'=>'mmyuu',
  '????'=>'nnyaa','????'=>'nnyee','????'=>'nnyii','????'=>'nnyoo','????'=>'nnyuu',
  '????'=>'rryaa','????'=>'rryee','????'=>'rryii','????'=>'rryoo','????'=>'rryuu',
  '????'=>'sshaa','????'=>'sshee','???'=>'sshii','????'=>'sshoo','????'=>'sshuu',
  '????'=>'cchaa','????'=>'cchee','???'=>'cchii','????'=>'cchoo','????'=>'cchuu',
  '????'=>'ttii',
  '????'=>'ddii',

  // 3 character syllables - doubled vowels
  '???'=>'faa','???'=>'fee','???'=>'fii','???'=>'foo',
  '???'=>'fyaa','???'=>'fyee','???'=>'fyii','???'=>'fyoo','???'=>'fyuu',
  '???'=>'hyaa','???'=>'hyee','???'=>'hyii','???'=>'hyoo','???'=>'hyuu',
  '???'=>'byaa','???'=>'byee','???'=>'byii','???'=>'byoo','???'=>'byuu',
  '???'=>'pyaa','???'=>'pyee','???'=>'pyii','???'=>'pyoo','???'=>'pyuu',
  '???'=>'kyaa','???'=>'kyee','???'=>'kyii','???'=>'kyoo','???'=>'kyuu',
  '???'=>'gyaa','???'=>'gyee','???'=>'gyii','???'=>'gyoo','???'=>'gyuu',
  '???'=>'myaa','???'=>'myee','???'=>'myii','???'=>'myoo','???'=>'myuu',
  '???'=>'nyaa','???'=>'nyee','???'=>'nyii','???'=>'nyoo','???'=>'nyuu',
  '???'=>'ryaa','???'=>'ryee','???'=>'ryii','???'=>'ryoo','???'=>'ryuu',
  '???'=>'shaa','???'=>'shee','??'=>'shii','???'=>'shoo','???'=>'shuu',
  '???'=>'jaa','???'=>'jee','??'=>'jii','???'=>'joo','???'=>'juu',
  '???'=>'swaa','???'=>'swee','???'=>'swii','???'=>'swoo','???'=>'swuu',
  '???'=>'daa','???'=>'dee','???'=>'dii','???'=>'doo','???'=>'duu',
  '???'=>'chaa','???'=>'chee','??'=>'chii','???'=>'choo','???'=>'chuu',
  '???'=>'dyaa','???'=>'dyee','???'=>'dyii','???'=>'dyoo','???'=>'dyuu',
  '???'=>'tsaa','???'=>'tsee','???'=>'tsii','???'=>'tsoo','??'=>'tsuu',
  '???'=>'twaa','???'=>'twee','???'=>'twii','???'=>'twoo','???'=>'twuu',
  '???'=>'dwaa','???'=>'dwee','???'=>'dwii','???'=>'dwoo','???'=>'dwuu',
  '???'=>'whaa','???'=>'whee','???'=>'whii','???'=>'whoo','???'=>'whuu',
  '???'=>'vyaa','???'=>'vyee','???'=>'vyii','???'=>'vyoo','???'=>'vyuu',
  '???'=>'vaa','???'=>'vee','???'=>'vii','???'=>'voo','??'=>'vuu',
  '???'=>'wee','???'=>'wii',
  '???'=>'yee',
  '???'=>'tii',
  '???'=>'dii',

  // 3 character syllables - doubled consonants
  '???'=>'bbya','???'=>'bbye','???'=>'bbyi','???'=>'bbyo','???'=>'bbyu',
  '???'=>'ppya','???'=>'ppye','???'=>'ppyi','???'=>'ppyo','???'=>'ppyu',
  '???'=>'kkya','???'=>'kkye','???'=>'kkyi','???'=>'kkyo','???'=>'kkyu',
  '???'=>'ggya','???'=>'ggye','???'=>'ggyi','???'=>'ggyo','???'=>'ggyu',
  '???'=>'mmya','???'=>'mmye','???'=>'mmyi','???'=>'mmyo','???'=>'mmyu',
  '???'=>'nnya','???'=>'nnye','???'=>'nnyi','???'=>'nnyo','???'=>'nnyu',
  '???'=>'rrya','???'=>'rrye','???'=>'rryi','???'=>'rryo','???'=>'rryu',
  '???'=>'ssha','???'=>'sshe','??'=>'sshi','???'=>'ssho','???'=>'sshu',
  '???'=>'ccha','???'=>'cche','??'=>'cchi','???'=>'ccho','???'=>'cchu',
  '???'=>'tti',
  '???'=>'ddi',

  // 3 character syllables - doubled vowel and consonants
  '???'=>'bbaa','???'=>'bbee','???'=>'bbii','???'=>'bboo','???'=>'bbuu',
  '???'=>'ppaa','???'=>'ppee','???'=>'ppii','???'=>'ppoo','???'=>'ppuu',
  '???'=>'kkee','???'=>'kkii','???'=>'kkoo','???'=>'kkuu','???'=>'kkaa',
  '???'=>'ggaa','???'=>'ggee','???'=>'ggii','???'=>'ggoo','???'=>'gguu',
  '???'=>'maa','???'=>'mee','???'=>'mii','???'=>'moo','???'=>'muu',
  '???'=>'nnaa','???'=>'nnee','???'=>'nnii','???'=>'nnoo','???'=>'nnuu',
  '???'=>'rraa','???'=>'rree','???'=>'rrii','???'=>'rroo','???'=>'rruu',
  '???'=>'ssaa','???'=>'ssee','???'=>'sshii','???'=>'ssoo','???'=>'ssuu',
  '???'=>'zzaa','???'=>'zzee','???'=>'jjii','???'=>'zzoo','???'=>'zzuu',
  '???'=>'ttaa','???'=>'ttee','???'=>'chii','???'=>'ttoo','???'=>'ttsuu',
  '???'=>'ddaa','???'=>'ddee','???'=>'ddii','???'=>'ddoo','???'=>'dduu',

  // 2 character syllables - normal
  '??'=>'fa','??'=>'fe','??'=>'fi','??'=>'fo','??'=>'fu',
  // '??'=>'fya','??'=>'fye','??'=>'fyi','??'=>'fyo','??'=>'fyu',
  '??'=>'fa','??'=>'fe','??'=>'fi','??'=>'fo','??'=>'fu',
  '??'=>'hya','??'=>'hye','??'=>'hyi','??'=>'hyo','??'=>'hyu',
  '??'=>'bya','??'=>'bye','??'=>'byi','??'=>'byo','??'=>'byu',
  '??'=>'pya','??'=>'pye','??'=>'pyi','??'=>'pyo','??'=>'pyu',
  '??'=>'kya','??'=>'kye','??'=>'kyi','??'=>'kyo','??'=>'kyu',
  '??'=>'gya','??'=>'gye','??'=>'gyi','??'=>'gyo','??'=>'gyu',
  '??'=>'mya','??'=>'mye','??'=>'myi','??'=>'myo','??'=>'myu',
  '??'=>'nya','??'=>'nye','??'=>'nyi','??'=>'nyo','??'=>'nyu',
  '??'=>'rya','??'=>'rye','??'=>'ryi','??'=>'ryo','??'=>'ryu',
  '??'=>'sha','??'=>'she','??'=>'sho','??'=>'shu',
  '??'=>'ja','??'=>'je','??'=>'jo','??'=>'ju',
  '??'=>'swa','??'=>'swe','??'=>'swi','??'=>'swo','??'=>'swu',
  '??'=>'da','??'=>'de','??'=>'di','??'=>'do','??'=>'du',
  '??'=>'cha','??'=>'che','?'=>'chi','??'=>'cho','??'=>'chu',
  // '??'=>'dya','??'=>'dye','??'=>'dyi','??'=>'dyo','??'=>'dyu',
  '??'=>'tsa','??'=>'tse','??'=>'tsi','??'=>'tso','?'=>'tsu',
  '??'=>'twa','??'=>'twe','??'=>'twi','??'=>'two','??'=>'twu',
  '??'=>'dwa','??'=>'dwe','??'=>'dwi','??'=>'dwo','??'=>'dwu',
  '??'=>'wha','??'=>'whe','??'=>'whi','??'=>'who','??'=>'whu',
  '??'=>'vya','??'=>'vye','??'=>'vyi','??'=>'vyo','??'=>'vyu',
  '??'=>'va','??'=>'ve','??'=>'vi','??'=>'vo','?'=>'vu',
  '??'=>'we','??'=>'wi',
  '??'=>'ye',
  '??'=>'ti',
  '??'=>'di',

  // 2 character syllables - doubled vocal
  '??'=>'aa','??'=>'ee','??'=>'ii','??'=>'oo','??'=>'uu',
  '??'=>'daa','??'=>'dee','??'=>'dii','??'=>'doo','??'=>'duu',
  '??'=>'haa','??'=>'hee','??'=>'hii','??'=>'hoo','??'=>'fuu',
  '??'=>'baa','??'=>'bee','??'=>'bii','??'=>'boo','??'=>'buu',
  '??'=>'paa','??'=>'pee','??'=>'pii','??'=>'poo','??'=>'puu',
  '??'=>'kee','??'=>'kii','??'=>'koo','??'=>'kuu','??'=>'kaa',
  '??'=>'gaa','??'=>'gee','??'=>'gii','??'=>'goo','??'=>'guu',
  '??'=>'maa','??'=>'mee','??'=>'mii','??'=>'moo','??'=>'muu',
  '??'=>'naa','??'=>'nee','??'=>'nii','??'=>'noo','??'=>'nuu',
  '??'=>'raa','??'=>'ree','??'=>'rii','??'=>'roo','??'=>'ruu',
  '??'=>'saa','??'=>'see','??'=>'shii','??'=>'soo','??'=>'suu',
  '??'=>'zaa','??'=>'zee','??'=>'jii','??'=>'zoo','??'=>'zuu',
  '??'=>'taa','??'=>'tee','??'=>'chii','??'=>'too','??'=>'tsuu',
  '??'=>'waa','??'=>'woo',
  '??'=>'yaa','??'=>'yoo','??'=>'yuu',
  '??'=>'kaa','??'=>'kee',
  // old characters
  '??'=>'wee','??'=>'wii',

  // seperate katakana 'n'
  '??'=>'n_a','??'=>'n_e','??'=>'n_i','??'=>'n_o','??'=>'n_u',
  '??'=>'n_ya','??'=>'n_yo','??'=>'n_yu',

  // 2 character syllables - doubled consonants
  '??'=>'bba','??'=>'bbe','??'=>'bbi','??'=>'bbo','??'=>'bbu',
  '??'=>'ppa','??'=>'ppe','??'=>'ppi','??'=>'ppo','??'=>'ppu',
  '??'=>'kke','??'=>'kki','??'=>'kko','??'=>'kku','??'=>'kka',
  '??'=>'gga','??'=>'gge','??'=>'ggi','??'=>'ggo','??'=>'ggu',
  '??'=>'ma','??'=>'me','??'=>'mi','??'=>'mo','??'=>'mu',
  '??'=>'nna','??'=>'nne','??'=>'nni','??'=>'nno','??'=>'nnu',
  '??'=>'rra','??'=>'rre','??'=>'rri','??'=>'rro','??'=>'rru',
  '??'=>'ssa','??'=>'sse','??'=>'sshi','??'=>'sso','??'=>'ssu',
  '??'=>'zza','??'=>'zze','??'=>'jji','??'=>'zzo','??'=>'zzu',
  '??'=>'tta','??'=>'tte','??'=>'cchi','??'=>'tto','??'=>'ttsu',
  '??'=>'dda','??'=>'dde','??'=>'ddi','??'=>'ddo','??'=>'ddu',

  // 1 character syllables
  '?'=>'a','?'=>'e','?'=>'i','?'=>'o','?'=>'u','?'=>'n',
  '?'=>'ha','?'=>'he','?'=>'hi','?'=>'ho','?'=>'fu',
  '?'=>'ba','?'=>'be','?'=>'bi','?'=>'bo','?'=>'bu',
  '?'=>'pa','?'=>'pe','?'=>'pi','?'=>'po','?'=>'pu',
  '?'=>'ke','?'=>'ki','?'=>'ko','?'=>'ku','?'=>'ka',
  '?'=>'ga','?'=>'ge','?'=>'gi','?'=>'go','?'=>'gu',
  '?'=>'ma','?'=>'me','?'=>'mi','?'=>'mo','?'=>'mu',
  '?'=>'na','?'=>'ne','?'=>'ni','?'=>'no','?'=>'nu',
  '?'=>'ra','?'=>'re','?'=>'ri','?'=>'ro','?'=>'ru',
  '?'=>'sa','?'=>'se','?'=>'shi','?'=>'so','?'=>'su',
  '?'=>'za','?'=>'ze','?'=>'ji','?'=>'zo','?'=>'zu',
  '?'=>'ta','?'=>'te','?'=>'chi','?'=>'to','?'=>'tsu',
  '?'=>'da','?'=>'de','?'=>'di','?'=>'do','?'=>'du',
  '?'=>'wa','?'=>'wo',
  '?'=>'ya','?'=>'yo','?'=>'yu',
  '?'=>'ka','?'=>'ke',
  // old characters
  '?'=>'we','?'=>'wi',

  //  convert what's left (probably only kicks in when something's missing above)
  '?'=>'a','?'=>'e','?'=>'i','?'=>'o','?'=>'u',
  '?'=>'ya','?'=>'yo','?'=>'yu',

  // special characters
  '?'=>'_','?'=>'_',
  '?'=>'_', // when used with hiragana (seldom), this character would not be converted otherwise

  // '?'=>'la','?'=>'le','?'=>'li','?'=>'lo','?'=>'lu',
  // '??'=>'cya','??'=>'cye','??'=>'cyi','??'=>'cyo','??'=>'cyu',
  //'??'=>'dha','??'=>'dhe','??'=>'dhi','??'=>'dho','??'=>'dhu',
  // '??'=>'lya','??'=>'lye','??'=>'lyi','??'=>'lyo','??'=>'lyu',
  // '??'=>'tha','??'=>'the','??'=>'thi','??'=>'tho','??'=>'thu',
  //'??'=>'fwa','??'=>'fwe','??'=>'fwi','??'=>'fwo','??'=>'fwu',
  //'??'=>'tya','??'=>'tye','??'=>'tyi','??'=>'tyo','??'=>'tyu',
  // '??'=>'jya','??'=>'jye','??'=>'jyi','??'=>'jyo','??'=>'jyu',
  // '??'=>'zha','??'=>'zhe','??'=>'zhi','??'=>'zho','??'=>'zhu',
  //'??'=>'zya','??'=>'zye','??'=>'zyi','??'=>'zyo','??'=>'zyu',
  //'??'=>'sya','??'=>'sye','??'=>'syi','??'=>'syo','??'=>'syu',
  //'?'=>'ci','?'=>'hu',?'=>'si','?'=>'ti','?'=>'tu','?'=>'yi','?'=>'dzi',

  // "Greeklish"
  '?'=>'G','?'=>'E','?'=>'Th','?'=>'L','?'=>'X','?'=>'P','?'=>'S','?'=>'F','?'=>'Ps',
  '?'=>'g','?'=>'e','?'=>'th','?'=>'l','?'=>'x','?'=>'p','?'=>'s','?'=>'f','?'=>'ps',

  // Thai
  '?'=>'k','?'=>'kh','?'=>'kh','?'=>'kh','?'=>'kh','?'=>'kh','?'=>'ng','?'=>'ch',
  '?'=>'ch','?'=>'ch','?'=>'s','?'=>'ch','?'=>'y','?'=>'d','?'=>'t','?'=>'th',
  '?'=>'d','?'=>'th','?'=>'n','?'=>'d','?'=>'t','?'=>'th','?'=>'th','?'=>'th',
  '?'=>'n','?'=>'b','?'=>'p','?'=>'ph','?'=>'f','?'=>'ph','?'=>'f','?'=>'ph',
  '?'=>'m','?'=>'y','?'=>'r','?'=>'rue','??'=>'rue','?'=>'l','?'=>'lue',
  '??'=>'lue','?'=>'w','?'=>'s','?'=>'s','?'=>'s','?'=>'h','?'=>'l','?'=>'h',
  '?'=>'a','?'=>'a','??'=>'a','?'=>'a','?'=>'a','?'=>'am','??'=>'am',
  '?'=>'i','?'=>'i','?'=>'ue','?'=>'ue','?'=>'u','?'=>'u',
  '?'=>'e','?'=>'ae','?'=>'o','?'=>'o',
  '???'=>'ia','??'=>'ia','???'=>'uea','??'=>'uea','???'=>'ua','??'=>'ua',
  '?'=>'ai','?'=>'ai','??'=>'ai','??'=>'ai','??'=>'ao',
  '??'=>'ui','??'=>'oi','???'=>'ueai','??'=>'uai',
  '??'=>'io','??'=>'eo','???'=>'iao',
  '?'=>'','?'=>'','?'=>'','?'=>'','?'=>'',
  '?'=>'','?'=>'','?'=>'','?'=>'',
  '?'=>'2','?'=>'o','?'=>'-','?'=>'-','?'=>'-',
  '?'=>'0','?'=>'1','?'=>'2','?'=>'3','?'=>'4',
  '?'=>'5','?'=>'6','?'=>'7','?'=>'8','?'=>'9',

  // Korean
  '?'=>'k','?'=>'kh','?'=>'kk','?'=>'t','?'=>'th','?'=>'tt','?'=>'p',
  '?'=>'ph','?'=>'pp','?'=>'c','?'=>'ch','?'=>'cc','?'=>'s','?'=>'ss',
  '?'=>'h','?'=>'ng','?'=>'n','?'=>'l','?'=>'m', '?'=>'a','?'=>'e','?'=>'o',
  '?'=>'wu','?'=>'u','?'=>'i','?'=>'ay','?'=>'ey','?'=>'oy','?'=>'wa','?'=>'we',
  '?'=>'wi','?'=>'way','?'=>'wey','?'=>'uy','?'=>'ya','?'=>'ye','?'=>'oy',
  '?'=>'yu','?'=>'yay','?'=>'yey',
);


