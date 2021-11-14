<?php

/**
 * 
 *   
 * @copyright Simourg 2003-2016. All right reserved.
 */

// типы XML-тегов
define('XML_TAG_NORMAL',   1); // <tag>...</tag>
define('XML_TAG_SHORT',    2); // <tag />
define('XML_TAG_OPENING',  3); // <tag>
define('XML_TAG_CLOSING',  4); // </tag>
define('XML_TAG_XML',      5); // <?...>
define('XML_TAG_COMMENT',  6); // <!-- ...>

// BOM - байты, указывающие на кодировку UTF-8
define('XML_BOM', "\xEF\xBB\xBF");


//   make beautiful XML-string
// parameters:
//   $xml_tokens - array - XML tokens
// return:
//   string - beautiful XML
function xml_beautifier($xml_tokens){
    
    $res = '';
    
    // one-level offset size
    $offset_size = 4;
    // default nesting level
    $nlevel = 0;
    // remembered last tag type (LAST TAG, NOT LAST TOKEN!!!)
    $last_tag_type = false;
    
    // was 'new line used' between current tag and previous tag?
    $new_line_before_tag = false;
    
    // for each XLML-token...
    $ci = count($xml_tokens);
    for( $i = 0; $i < $ci; $i++ ){
        
        // check if token is tag, and get it's type.
        $tag_type = xml_token_tag_type($xml_tokens[$i]);
        
        // Решить нужен ли отступ для тега
        $need_offset = false;
        if( $tag_type !== false ){
            
            if( $last_tag_type == $tag_type ){ // tag type are same
                $need_offset = true;
            }
            
            elseif(     $last_tag_type == XML_TAG_OPENING
                    &&  $tag_type      == XML_TAG_OPENING
            ){
                $need_offset = true;
            }
            elseif(     $last_tag_type == XML_TAG_OPENING 
                    &&  $tag_type      == XML_TAG_SHORT
            ){
                $need_offset = true;
            }
            elseif(     $last_tag_type == XML_TAG_SHORT
                    &&  $tag_type      == XML_TAG_OPENING
            ){
                $need_offset = true;
            }
            elseif(    $last_tag_type == XML_TAG_SHORT
                    && $tag_type      == XML_TAG_CLOSING 
            ){
                $need_offset = true;
            }
            elseif(     $last_tag_type == XML_TAG_CLOSING
                    &&  $tag_type      == XML_TAG_SHORT
            ){
                $need_offset = true;
            }
            elseif(     $last_tag_type == XML_TAG_CLOSING
                    &&  $tag_type      == XML_TAG_OPENING
            ){
                $need_offset = true;
            }
            elseif( $last_tag_type == XML_TAG_XML ){
                $need_offset = true;
            } 
        }
        
        
        
        // если нужен сдвиг, то помимо этого еще и переносим его на новую строку
        // (если он уже не на новой строке)
        if( !$new_line_before_tag && $need_offset ) $res .= "\n";
        
        
        
        // add offset and change nesting level (if needed).
        if( $tag_type === false ){ // not tag
            // do nothing
        }
        elseif( $tag_type == XML_TAG_CLOSING ){
            // decrease nesting level, to decrease offset
            $nlevel--;
            // add offset
            if( $need_offset ){
                $res .= str_repeat(' ', $nlevel * $offset_size);
            } 
        }
        elseif( $tag_type == XML_TAG_OPENING ){
            // add offset
            if( $need_offset ){
                $res .= str_repeat(' ', $nlevel * $offset_size);
            } 
            // increase nesting level, to increase offset
            $nlevel++;
        }
        else{
            // add offset
            if( $need_offset ) $res .= str_repeat(' ', $nlevel * $offset_size);
        }
        
        
        
        // remember type of last tag.
        if( $tag_type !== false ){ // is tag.
            $last_tag_type = $tag_type;
            
            $new_line_before_tag = false;
        }
        else{
            $new_line_before_tag = ( strpos($xml_tokens[$i], "\n") !== false );
        }
        
        // add XML-token to string.
        $res .= $xml_tokens[$i];
        
    }
    
    
    return $res;
    
}



//   check is XML is valid (version, encoding and root tag are checked)
// parameters:
//   $tokens   - array  - whole XML from where we will get XML-blocks
//   $root_tag - string - expected root tag
//   &$error   - int    - error code
//     -1 - no <?xml ...> found
//     -2 - failed to parse attribs in <?xml ...>
//     -3 - incorrect 'version'
//     -4 - incorrect 'encoding'
//     -5 - root tag different from expected
// return:
//   true - ok
//   false  - error
// notes:
//   - WARNING: version must be '1.0' and encoding 'UTF-8' (case-insensitive);
function xml_check($tokens, $root_tag, &$error = 0){
    
    // 1st token must be xml header ------------------------------------------->
    if( !isset($tokens[0]) ){
        $error = -1;
        return false;
    }
    
    $token = $tokens[0];
    $pos_ot = strpos($token, '<' . '?xml ');
    $pos_et = strpos($token, '?' . '>');
    // XML-statement must be started from the first symbol in XML
    if( $pos_ot !== 0 || $pos_ot === false || $pos_et === false ){
        $error = -1;
        return false;
    }
    // ------------------------------------------------------------------------>
    
    
    // get attributes --------------------------------------------------------->
    // cut part with attributes (get 'version="1.0" encoding="UTF-8"' part)
    $attribs_str = substr($token, 6, $pos_et - 6);
    
    $attribs = xml_parse_attribs($attribs_str); // get names and values of attributes
    if( $attribs === false ){
        $error = -2;
        return false;
    }
    // ------------------------------------------------------------------------>
    
    
    // check mandatory attributes and their values ---------------------------->
    if( !isset($attribs['version']) || $attribs['version'] !== '1.0' ){
        $error = -3;
        return false;
    } // check XML version
    if( !isset($attribs['encoding']) || strtoupper($attribs['encoding']) !== 'UTF-8' ){
        $error = -4;
        return false;
    } // check encoding
    // ------------------------------------------------------------------------>
    
    
    // check root tag --------------------------------------------------------->
    if( !empty($root_tag) && $root_tag !== xml_read_root_tag($tokens) ){
        $error = -5;
        return false;
    }
    // ------------------------------------------------------------------------>
    
    return true;
}



//   escape spec-symbols
// parameters:
//   $str - int, float, string or array
// return:
//   string - escaped string or array
function xml_escape($str){
    
    // recursive part
    if( is_array($str) ){
        $keys = array_keys($str);
        $ci = count($keys);
        for( $i = 0; $i < $ci; $i++ ){
            $str[$keys[$i]] = xml_escape($str[$keys[$i]]);
        }
        return $str;
    }
    
    // escape some symbols with predefined entities
    return str_replace(array('&',     '<',    '>',    "'",      '"'),
                       array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'),
                       $str
           );
}



//   return XML header
// parameters:
//   -
// return:
//   atring - XML tag
//   false  - error
function xml_header(){
    return '<?xml version="1.0" encoding="UTF-8" ?' . '>';
}



//   initialize XML-params
// parameters:
//   &$xml_params - additional parameters & statistics
//     ['allwd_broken'] - bool - flag (false by default)
//       true  - do not return false if end of tag not found ('<...' & '...>' allowed)
//       false - all tags must be closed ('<...>')
//     ['nlevel_start']  - int   - nesting level of the 1st token in $tokens
//     ['nlevel_end']    - int   - nesting level of last token in $tokens
//     ['nlevel_tokens'] - array - nesting levels of all tags $tokens
//       [key] - int - nesting level of tag (key - index in $tokens of same tag)
//       [...]
//     ['optimize_xml'] - bool - flag (true by default)
//       true  - parser will try to remove unneeded symbols (space-like symbols),
//               like two or more spaces will be replaced with single space if it is
//               not inside qoutes or tag content
//       false - read XML as it is
//     ['last_tag'] - string - name of last tag in previous tokens-block (used when file parsed by blocks,
//                             to defined that we "continue" reading some tag, even if tokens has no tags at all,
//                             for eaxample tokens contain anly text that was inside some tag)
// return:
//   true - ok
function xml_params_init(&$xml_params){
    
    $xml_params = array();
    
    // default values
    $xml_params['allwd_broken'] = false;
    $xml_params['optimize_xml'] = true;
    
    // 0 = root tag
    $xml_params['nlevel_start'] = 0;
    $xml_params['nlevel_end']   = 0;
    $xml_params['nlevel_tokens'] = array();
    
    
    $xml_params['last_tag'] = NULL; // last tag in $tokens, that was not read completely
    
    return true;
}



//   get attribute names and values from string
// parameters:
//   $str - string like 'a="123" b="ABC" c="12.00" ... '.
// return:
//   array - keys are attribute names and values - are attribute values
// notes:
//   - example:
//     if you got string:
//       a="123" b="ABC" c="12.00"
//     result will be:
//       array('a' => '123', 'b' => 'ABC', 'c' => '12.00');
//   - all values will be string (not integer or float)!;
//   - all values are XML-unescaped automatically;
function xml_parse_attribs($str){
    
    $res = array();     // array of attributes (keys - names, values - values)
    
    $ci = strlen($str); // number of symbols (bytes)
    $op = 1;            // operation (1 - read key, 2 - read '=', 3 - read val)
    $i  = 0;            // position in string (symbol number)
    
    while( $i < $ci ){  // until all symbols
        
        // skip spaces, tabs and line breaks
        while( $i < $ci && strpos(" \n\r\t\v", $str[$i]) !== false ) $i++;
        if( $i >= $ci ) break;
        
        switch( $op ){
            // read key ------------------------------------------------------>>
            case 1:{
                $key = '';
                while( $i < $ci ){
                    
                    $sym     = $str[$i];          // read next symbol.
                    $sym_low = strtolower($sym);  // same symbol in lower case.
                    if(      $sym_low === '_'
                        //||   $sym_low === '-'
                        ||   $sym_low === ':'
                        //||   $sym_low === '.' 
                        || ( $sym_low >= '0' && $sym_low <= '9' )
                        || ( $sym_low >= 'a' && $sym_low <= 'z' ) 
                    ){ $key .= $sym; }
                    else{
                        $i--;  // next operation start from same symbol (otherwise this symbol will be lost)
                        break;
                    }
                    $i++;
                } // while( $i < $ci )
                
                if( $key === '' ) return false;
                
                $op = 2; // proceed to 'read "="'
                // d_echo('key="'. $key.'"');
                break;
            } 
            // --------------------------------------------------------------->>
            
            
            // read '=' symbol ----------------------------------------------->>
            case 2:
                $sym = $str[$i];
                if( $sym !== '=' ) return false;
                $op = 3; // proceed to 'read value'
                // d_echo('"=" found');
                break;
            // --------------------------------------------------------------->>
            
            
            // read val ------------------------------------------------------>>
            case 3:{
                $val = '';
                $sym = $str[$i];
                
                // 1st symbol must be double quote
                if( $sym !== '"' ) return false;
                $i++;
                
                // read until next double qoute (end of value)
                while( $i < $ci ){
                    $sym = $str[$i];
                    if( $sym === '"' ) break;
                    $val .= $sym;
                    $i++;
                }
                
                // if reach end of string, but 2nd double quote not found return error
                if( $sym !== '"' ) return false;
                
                // decode XML special chararacters, to get value in original form
                $val = xml_unescape($val);
                // d_echo('val="'. $val.'"');
                $op = 1; // proceed to 'read key'
                $res[$key] = $val; 
                break;
            }
            // --------------------------------------------------------------->>
            
            default: break;
        } // switch( ...
        
        $i++;
    } // while( $i < $ci )
    
    if( $op !== 1 ) return false;
    return $res;
}



//   get attribute names and values from XML token (that must be tag!)
// parameters:
//   $str - tag like '<any_tag_name a="123" b="ABC" c="12.00" ...>'.
// return:
//   array - keys are attribute names and values - are attribute values
// notes:
//   - example:
//     if you got token:
//       '<any_tag_name a="123" b="ABC" c="12.00" />'
//     result will be:
//       array('a' => '123', 'b' => 'ABC', 'c' => '12.00');
//   - all values will be string (not integer or float)!;
//   - all values are XML-unescaped automatically;
function xml_read_attribs($str){
    
    $space_pos = strpos($str, ' ');             // 1st space after tagname.
    $len       = strlen($str);                  // length of string.
    
    if( $str[$len - 1] !== '>' ) return false;  // last symbol must be '>'.
    $cut = ( $str[$len - 2] === '/' ) ? 2 : 1;  // cut one symbol (>) or two (/>).
    
    if( $space_pos === false ){ $str = ''; }    // if tag without attributes make string empty.
    else{
        // cut unneded parts from string ("<tagname a="1" b="2">" --> "a="1" b="2"")
        $start  = $space_pos + 1;               // start from symbol next to 1st space after tagname.
        $length = $len - $start - $cut;         // calculate needed string length (string that contain attributes).
        $str = substr($str, $start, $length);   // cut unneded part (tag name, '<', '>' or '/>').
    }
    
    // d_echo($str, 'hd');
    return xml_parse_attribs($str);           // get attribute values.
}



//   read XML blocks
// parameters:
//   $tokens        - array  - whole XML from where we will get XML-blocks
//   $tag           - string - tag name (we will get blocks for tag with this name)
//   $limit         - int    - max. number of blocks that we will look for (0 = unlimited)
//   $nlevel_search - int    - nesting level where to search (NULL = all levels)
// return:
//   array - XML blocks (each block is in 'tokens' format)
//   false - error
// notes:
//   - example:
//     if you got tokens from XML:
//       <a>ABC</a>
//       <a><a>QWERTY</a></a>
//       <b><a>ASDFGG</a></b>
//       <a qwerty="123" />
//     result will be blocks:
//       1) <a>ABC</a>
//       2) <a><a>QWERTY</a></a>
//       3) <a>ASDFGG</a>
//       4) <a qwerty="123" />
function xml_read_blocks($tokens, $tag, $limit = 0, $nlevel_search = 0){
    
    // preprocessing ---------------------------------------------------------->
    $blocks = array();        // store all blocks ('<tag[ attribs]>...<tag/>' or '<tag[ attribs]/>')
    $block  = array();        // store one block data
    
    $add_token = false;       // flag "add token to current $block"
    $tag_len = strlen($tag);  // length of tag name
    $nlevel  = 0;             // nesting level
    $nlevel_main = 0;         // zero = minimal nesting level in $tokens
    // ------------------------------------------------------------------------>
    
    
    // go through all tokens, looking for needed blocks ----------------------->
    $ci = count($tokens);               // count tokens
    for( $i = 0; $i < $ci; $i++ ){
     
        $token     = $tokens[$i];       // read one token from array
        $token_len = strlen($token);    // token length (in bytes, because not mb_strlen() is used, and it is correct!)
        $add_block = false;             // reset flag "add $block to $blocks"

        // if token is tag ----------------------------------------------->>
        if( $token_len > 0 && $token[0] === '<' ){
            
            
            // control total nesting level in received tokens
            $tag_type = xml_token_tag_type($token);
            switch( $tag_type ){
                case XML_TAG_OPENING: $nlevel_main++; break;
                case XML_TAG_CLOSING: $nlevel_main--; break;
                default: break;
            }
            
            // if serched nesting level match current nesting level, check
            // if it is needed tag
            if(     $nlevel_search === NULL 
                ||  ( $tag_type == XML_TAG_SHORT   && $nlevel_main     == $nlevel_search )
                ||  ( $tag_type == XML_TAG_OPENING && $nlevel_main - 1 == $nlevel_search )
                ||  ( $tag_type == XML_TAG_CLOSING && $nlevel_main     == $nlevel_search )
            ){
                
                $tag_pos = strpos($token, $tag);          // position of tag name in token
                
                $sym_is_slash = ( $token[1] === '/' );
                
                // if it is like $token contain needed tag ("<tag" or '</tag')
                if(    ( $tag_pos == 2 && $sym_is_slash ) // '</tag' 
                    || ( $tag_pos == 1 )                  // '<tag' 
                ){ 
                    // get symbol after '</$tag' or '<$tag'
                    $next_sym = $token[$tag_len + $tag_pos];
                    
                    // analyse next sym after $tag in token.
                    // (if we look for '<a' it allows us to ignore tags '<ab>', <abc>,
                    //  but '<a >', '<a />', '</a >', '<a x="1" >' is ok)
                    if(    ( $sym_is_slash && strpos(' >', $next_sym) !== false ) // closing tag (allowed ' ' or '>')                        
                        || ( strpos(' />', $next_sym) !== false  )                // short tag   (allowed ' ', '/' or '>') 
                    ){
                        $add_token = true;
                        
                        if(    $sym_is_slash                            // if closing tag (</...>)
                            || substr($token, $token_len - 2) === '/>'  // or short tag (without closing tag <../>) 
                        ){
                            if( $sym_is_slash ) $nlevel--;              // if it is closing tag, descrease nesting level
                            $add_block = ( $nlevel == 0 );              // if nesting level is 0 add block to array, 
                                                                        // else continue to look for closing tag for the current nesting level
                        }
                        
                        // if it is openning tag "<$tag[ attribs]>" 
                        // or opening tag was found inside another tag - increase level
                        else{ $nlevel++; }
                    } // if(    ( $token[1]
                } // if(     ( $tag_pos ...
            }
            
        } // if( $token_len ...
        // --------------------------------------------------------------->>
        
        
        // add $token to $block if flag "add $token to $block" is true --->>
        if( $add_token ) $block[] = $token;
        // --------------------------------------------------------------->>
        
        
        // add block to blocks  ------------------------------------------>>
        if( $add_block ){
                
            $add_token = false;   // reset flag (stop adding tokens to current block)
            $blocks[]  = $block;  // add current block to blocks
            $block = array();     // reset 'block' data
            
            // check if we need to stop searching for more blocks
            // note: if $limit is zero from the beggining, it will turn to -1 on this step,
            //       and if( $limit == 0 ){ break; } will never work, so tag count will be unlimited.
            //       but if  $limit > 0 number of blocks will be limited by that number
            if( $limit >= 0 ){
                $limit--;
                // if number of elements for returned array is limited
                // exit the cycle if needed number of elements is reached
                if( $limit == 0 ) break;
            }
        }
        // --------------------------------------------------------------->>
        
    }
    
    if( $add_token ) return false; // error: closing tag was not found for last block
    // ------------------------------------------------------------------------>
    
    return $blocks;
}



//   read tokens, get root tag name
// parameters:
//   $tokens - XML tokens
// return:
//   string - root tag name
//   false  - error
function xml_read_root_tag($tokens){
    
    $ci = count($tokens);
    for( $i = 0; $i < $ci; $i++ ){
        if( $tokens[$i][0] === '<' && strpos('?!', $tokens[$i][1]) === false ){ // not ('<?xml ...>' || <!...>)
            return xml_read_tag_name($tokens[$i]);
            break;
        }
    }
    
    // root tag was not found
    return false;
}


//   read one tag completely (attributes + content)
// parameters:
//   &$tokens       - array  - XML tokens
//   $tag           - string - tag that will be found and parsed.
//   $nlevel_search - int    - nesting level where to search (NULL = all levels)
// return:
//   array - tag data
//     ['attribs'] - attributes (keys=attribute names / values=attribute values)
//     ['data']    - tokens of tag content
//   NULL - tag not found
//   false - error / tag was not found
// notes:
//   - WARNING: tag content is not XML-unescaped automatically, because XML-tag can contain other tags;
function xml_read_tag(&$tokens, $tag, $nlevel_search = 0){
    
    $res = array();
    
    // search for the first appearance of $tag in array $mixed ------------> 
    // read it's content and attribs 
    // (only one instance of tag will be returned)
    $blocks = xml_read_blocks($tokens, $tag, 1, $nlevel_search);
    
    if( $blocks === false )  return false;                           // unable to read $mixed
    if( !isset($blocks[0]) ) return NULL;  // tag was not found
    
    $block = $blocks[0];                                             // store tag and its content
    $res['attribs'] = xml_read_attribs($block[0]);              // get attributes from opening tag
    
    if( count($block) == 1 ){ // if tag is short (<tag ... />), set tag content as empty
        $res['data'] = array();
    }
    else{ // cut opening and closing tag and put into data only content
        array_pop($block);    // cut closing tag
        array_shift($block);  // cut opening tag
        $res['data'] = $block;
    }
    // -------------------------------------------------------------------->
   
    return $res;
}


/**
 * Прочесть аттрибуты тега
 * 
 * @param array  $tokens
 * @param string $tag_name
 *
 * @return array - аттрибуты (ключ=имя аттрибута; значение=значение аттрибута)
 * @return null - тег не найден
 * @return false - ошибка
 */
function xml_read_tag_attributes($tokens, $tag_name){
    
    $res = xml_read_tag($tokens, $tag_name);
    if( $res === false || $res === null ) return $res;
    $res = $res['attribs'];
    return $res;
}



//   read one tag content in 'token' format.
//   attributes will be ignored.
// parameters:
//   &$tokens - XML tokens
//   $tag     - tag that will be found and parsed.
// return:
//   array - content XML tokens
//   NULL - not found
//   false - error
// notes:
//   - WARNING: tag content is not XML-unescaped automatically, because XML-tag can contain other tags;
function xml_read_tag_data(&$tokens, $tag){
    
    $res = xml_read_tag($tokens, $tag);
    if( $res === false || $res === null ) return $res;
    $res = $res['data'];
    return $res;
}



//   get tag name from token (that must be tag!)
// parameters:
//   $token - XML token (tag!)
// return:
//   string - tag name
//   false - error
// notes:
//   - suggested that tag name can contain symbols '_0-9a-z';
//   - XML specification (ru): http://www.rol.ru/news/it/helpdesk/xml01.htm;
//   - tag name can be stared with symbols: 'a-z', '_', '-', ':', '.' (digit cannot be first symbol in name); 
//   - tag names stared with 'xml'(case-insensitive) are reserved and must not be used;
function xml_read_tag_name($token){
    
    $ci = strlen($token);
    $si = 1; // start $i value
    
    if( isset($token[0]) && $token[0] !== '<' ) return false;
    if( isset($token[1]) && $token[1] === '/' ) $si = 2;
    
    // skip space-like symbols
    for( $i = $si; $i < $ci; $i++ ){
        $sym = $token[$i];
        if( strpos(" \n\r\t\v", $sym) !== false ) $si++; else break;
    }
    
    $tag = '';
    for( $i = $si; $i < $ci; $i++ ){
        
        $sym     = $token[$i];
        $sym_low = strtolower($sym);
        
        if(      $sym_low === '_' 
            ||   $sym_low === '-' // 27.01.2016 - AL - откомментировал, т.к. встретилась ситуация когда имя тега такое
            ||   $sym_low === ':'
            //||   $sym_low === '.'
            || ( $sym_low >= '0' && $sym_low <= '9' )
            || ( $sym_low >= 'a' && $sym_low <= 'z' ) 
        ){ $tag .= $sym; }
        else{ break; }
    }
    
    return $tag;
}



//   read one tag content as one string.
//   attributes will be ignored.
//   useful when you know that tag contain value, not nested XML.
// parameters:
//   &$tokens       - array  - XML tokens
//   $tag           - string - tag that will be found and parsed.
//   $datatype      - string - datatype of expected value ('int', 'float' or 'str')
//   $nlevel_search - int    - nesting level where to search (NULL = all levels)
// return:
//   int    - tag value as integer
//   float  - tag value as float
//   string - tag value as string
//   NULL   - tag not found
//   false  - error
// notes:
//   - WARNING: tag content IS XML-unescaped automatically, because this function awaits actual value, not XML;
//   - WARNING: value always is of string type!;
//   - WARNING: if $datatype is 'int' or 'float' result will be converted to php int or float,
//              but if result will contain unallowed symbols (for example '123a' when trying convert to int)
//              'false' will be returned;
function xml_read_tag_val(&$tokens, $tag, $datatype = 'str', $nlevel_search = 0){
    
    $res = xml_read_tag($tokens, $tag, $nlevel_search);
    if( $res === false || $res === NULL ) return $res;
    
    $res['data'] = xml_unescape($res['data']);
    $res = implode('', $res['data']);
    
    switch( $datatype ){
        case 'int':   $res = xml_str_to_int($res);   break;
        case 'float': $res = xml_str_to_float($res); break;
        default: break;
    }
    
    return $res;
}



//   read one XML token ('<...>' or text till '<' or end of string.)
// parameters:
//   $str  - string
//   &$pos - position in string from which we shoul start
//   &$xml_params - additional parameters
// return:
//   string - XML token
// notes: 
//   - it is easy to read all token from string if $pos is used;
//   - <!-- ... --> also will be read as normal tag;
function xml_read_token($str, &$pos = 0, &$xml_params = array()){
    
    // check mandatory attribs
    if( !isset($xml_params['allwd_broken'],
               $xml_params['optimize_xml']
         ) 
    ) return false ;
    
    
    $token = '';
    
    if( $ci = strlen($str) ){  // string length
        
        // skip <!-- ... ---> ------------------------------------------------->
        while(    $pos < $ci
               && $str[$pos] === '<'
               && isset($str[$pos + 1]) && $str[$pos + 1] === '!' 
               && isset($str[$pos + 2]) && $str[$pos + 2] === '-' 
               && isset($str[$pos + 3]) && $str[$pos + 3] === '-' 
        ){
            // go to position after '<!--'
            $pos += 4;
            
            for( $i = $pos; $i < $ci; $i++ ){ // read all other symbols, until '<' symbol will be found
                
                if(    $str[$pos] === '-' 
                    && isset($str[$pos + 1]) && $str[$pos + 1] === '-' 
                    && isset($str[$pos + 2]) && $str[$pos + 2] === '>' 
                ){
                    // go to position after '-->'
                    $pos += 3;
                    // exit for( $i = ...
                    break;
                }
                
                $pos++;
            } // for( ...
        } // while( ...
        // -------------------------------------------------------------------->
        
        if( $pos >= $ci ) return $token;
        
        // read tag "<...>" --------------------------------------------->
        // d_echo($str, 'hr');
        // d_echo($pos, 'hr');
        if( $str[$pos] === '<' ){     // if first symbol, that was read is '<'
            
            $is_ok   = false;         // flag is tag ok, or it is broken
            $in_quot = false;         // quotes flag (outside quotes / inside quotes)
            $token .= $str[$pos];     // add to result symbol '<'
            $pos++;
            
            for( $i = $pos; $i < $ci; $i++ ){ // read all other symbols, until '<' symbol will be found
                
                $pos++;
                
                // optimize XML-size ------------------------------------>>
                if( $xml_params['optimize_xml'] ){
                    // skip space-type symbols (if not in qoutes!!!)
                    if(    !$in_quot    // not inside quotes
                        && $i > 0       // not the 1st symbol
                        && strpos(" \n\r\t\v", $str[$i]) !== false                                // current symbol is space-type symbol
                        && (    ( $i + 1 < $ci && strpos(" \n\r\t\v=>", $str[$i + 1]) !== false ) // next symbol is space-type symbol or '=', '>'
                             || strpos(" \n\r\t\v=</", substr($token, -1)) !== false              // prev symbol is space-type symbol or '=', '<', '/'
                           )
                    ){ continue; }
                
                }
                // ------------------------------------------------------>>
                
                $sym    = $str[$i]; // read symbol
                $token .= $sym;     // add symbol to result
                
                
                if( $sym === '"' ) $in_quot = !$in_quot;  // invert quotes flag
                
                if( !$in_quot ){                          // outside quotes check '<' and '>' symbols usage
                    if( $sym === '<' ) break;             // unexpected opening tag
                    if( $sym === '>' ){                   // closing tag found
                        $is_ok = true;
                        break;
                    }
                }
            }
            
            if( !$is_ok && !$xml_params['allwd_broken'] ) return false; // tag is broken (symbol '>' not found)
        }
        // -------------------------------------------------------------->
        
        
        // read text block ----------------------------------------------> 
        // (until '<' symbol or the end of the string)
        else{
            for( $i = $pos + 0; $i < $ci; $i++ ){
                if( $str[$i] === '<' ) break;
                $token .= $str[$i];
                $pos++;
            }
        }
        // -------------------------------------------------------------->
    }
    
    return $token;
}


//   read all XML tokens from string ('<...>' or text till '<' or end of string.)
// parameters:
//   $str  - string
//   &$xml_params - additional parameters
//     see description in xml_read_token()
// return:
//   array - all XML tokens (empty array if no tokens)
//   false - error
// notes:
//   - token can be empty string only if it is end of string;
//   - after you have array with token, you can parse XML and analyze XML
//     with pretty good performance. try always convert XML to array of token
//     before any other actions; 
function xml_read_tokens($str, &$xml_params = array()){
    
    if( !is_array($xml_params) || empty($xml_params) ){
        // initialize XML-params
        xml_params_init($xml_params);
    }
    
    // remove BOM
    if( strpos($str, XML_BOM) === 0 ){
        $str = substr($str, 3);
    }
    
    
    $pos = 0;
    
    $tokens = array();
    
    $read = true;
    while( $read ){
        
        // read next token
        $token = xml_read_token($str, $pos, $xml_params);
        
        if( $token === false ) return false;  // error
        if( $token === '' ) $read = false;    // end of string
        else{ $tokens[] = $token; }
    }
    
    return $tokens;
}



//   convert string to float. if string not like number, error will be returned.
// parameters:
//   $str - string
// return:
//   float - float
//   false - error
function xml_str_to_float($str){
    
    // check if string containt number (integer or float)
    if( !is_numeric($str) ) return false;
    
    // convert to float
    $float = (float)$str;
    
    // if value is still bigger that max. float (like INF) or 
    // float is smaller than min.float (like -INF)
    if( $float > 0 ){
        if( $float > C_SYS_MAX_FLOAT ) return false;
    }
    else{
        if( $float < C_SYS_MIN_FLOAT ) return false;
    }
    
    return $float;
}



//   convert string to integer. if string contain unallowed symbols it is error.
// parameters:
//   $str - string
// return:
//   int   - integer
//   false - error
function xml_str_to_int($str){
    
    if( preg_match('/^[-]?[0-9]+$/', $str) ){ // check allowed symbols
        
        // convert to int
        $int = (int)$str;
        
        // make sure that string actually contained another integer 
        // (for example bigger than maximal integer OR float value like '123.00')
        if( $int > 0 ){
            if( abs($int) >= C_SYS_MAX_INT ){ //
                if( strcmp($str, $int) ) return false;
            }
        }
        elseif( abs($int) >= abs(C_SYS_MIN_INT) ){
            if( strcmp($str, $int) ) return false;
        }
    }
    // unallowed symbols found
    else{ return false; }
    
    return $int;
}



//   get symbol in UTF8 from it's number in UNICODE
// parameters:
//   $code - int - symbol number in UNICODE table
// return:
//   string - symbol in UTF8
// notes: 
//   - in comments bits are numbered 1,2,3,... from right to left.
//     for example if $code = (bin) "01": 1 is 1st bit, and 0 - is 2nd bit;
//   - UNICODE table:
//     http://en.wikipedia.org/wiki/Unicode 
//   - UNICODE to UTF8 convertion:
//     http://ru.wikipedia.org/wiki/UTF-8
function xml_sym_decode($code){
    
    // 0x00000000 — 0x0000007F
    if( $code >= 0 && $code <= 0x7F ){
        // 0xxxxxxx
        return chr($code);
    }
    // 0x00000080 — 0x000007FF
    elseif( $code <= 0x7FF ){
        // 110xxxxx 10xxxxxx
        return chr(0xC0 + ($code >> 6)) .  // 1st byte = ( 11000000 ) + $code bits 7-11
               chr(0x80 + (0x3F & $code)); // 2nd byte = ( 11000000 ) + $code bits 1-6
    }
    // 0x00000800 — 0x0000FFFF
    elseif( $code <= 0xFFFF ){
        
        // 1110xxxx 10xxxxxx 10xxxxxx
        return chr(0xE0 + ($code >> 12)) .            // 1st byte = ( 11100000 ) + $code bits 13-16
               chr(0x80 + (0x3F & ($code >> 6))) .    // 2nd byte = ( 11000000 ) + $code bits 7-12
               chr(0x80 + (0x3F & $code));            // 3rd byte = ( 11000000 ) + $code bits 1-6
    }
    // 0x00010000 — 0x001FFFFF
    elseif( $code <= 0x1FFFFF ){
        
        // 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx 	
        return chr(0xF0 + ($code >> 18)) .            // 1st byte = ( 11100000 ) + $code bits 19-21
               chr(0x80 + (0x3F & ($code >> 12))) .   // 2nd byte = ( 11000000 ) + $code bits 13-18
               chr(0x80 + (0x3F & ($code >> 6))) .    // 3rd byte = ( 11000000 ) + $code bits 7-12
               chr(0x80 + (0x3F & $code));            // 4th byte = ( 11000000 ) + $code bits 1-6
    }
    else{
        return $code;
    }
}



//   create XML-tag.
// parameters:
//   $tag     - tag name
//   $data    - tag content (can be XML string)
//   $attribs - array - attributes array (keys=attribute names; values=attribute values)
//   $type    - tag type (see XML_TAG_XXX constants) 
// return:
//   atring - XML tag
//   false  - error
// notes:
//   - WARNING: XML-attributes are XML-escaped automatically;
//   - WARNING: tag content not XML-escaped, because can be nested XML;
function xml_tag($tag, $data = '', $attribs = NULL, $type = XML_TAG_NORMAL){
    
    $res  = '';
    $attribs_str = '';
    
    // attributes ------------------------------------------------------------->
    if( !empty($attribs) ){
        
        $keys = array_keys($attribs);
        $ci = count($keys);
        for( $i = 0; $i < $ci; $i++ ){
            $key = $keys[$i];                                 // attribute name
            $val = xml_escape($attribs[$key]);           // attribute value
            $attribs_str .= sprintf(' %s="%s"', $key, $val);  // whole attribute
        }
    }
    // ------------------------------------------------------------------------>
    
    
    // draw tag --------------------------------------------------------------->
    // if( is_array($data) ){
        // echo var_export($tag, true);
        // echo var_export($data, true);
        // echo $tag . ' is array, but in SXM-schema it is scalar! Check your schema!';
    // }
    
    
    switch( $type ){
        case XML_TAG_NORMAL:  $res = sprintf('<%s%s>%s</%s>', $tag, $attribs_str, $data, $tag); break; // <tag>...</tag>
        case XML_TAG_SHORT:   $res = sprintf('<%s%s />',      $tag, $attribs_str); break; // <tag />
        case XML_TAG_OPENING: $res = sprintf('<%s%s>',        $tag, $attribs_str); break; // <tag>
        case XML_TAG_CLOSING: $res = sprintf('</%s> ',        $tag);               break; // </tag>
        default: return false; break;
    }
    // ------------------------------------------------------------------------>
    
    return $res;
}



//   get list of tags in tokens
// parameters:
//   &$tokens       - array - XML-tokens
//   $nlevel_search - int - nest level that will be searched (NULL = all)
// return:
//   true  - ok
//   false - error
function xml_tags_list(&$tokens, $nlevel_search = 0){
    
    $res = array();
    
    $nlevel_main = 0;
    
    $ci = count($tokens);
    for( $i = 0; $i < $ci; $i++ ){
        
        // control total nesting level in received tokens
        $tag_type = xml_token_tag_type($tokens[$i]);
        switch( $tag_type ){
            case XML_TAG_OPENING: $nlevel_main++; break;
            case XML_TAG_CLOSING: $nlevel_main--; break;
            default: break;
        }
        
        // if opening/short tag
        switch( $tag_type ){
            case XML_TAG_OPENING:
            case XML_TAG_SHORT:
                // if serched nesting level match current nesting level
                if(     $nlevel_search === NULL 
                    ||  ( $tag_type == XML_TAG_SHORT   && $nlevel_main     == $nlevel_search )
                    ||  ( $tag_type == XML_TAG_OPENING && $nlevel_main - 1 == $nlevel_search )
                ){
                    // read tag name and add to list
                    $res[] = xml_read_tag_name($tokens[$i]);
                }
                break;
            default:
                break;
        } // switch   
    }
    
    return $res;
}



//   check if tag is broken
// parameters:
//   $str - string - token (tag)
// return:
//   true  - broken
//   false - not broken
// notes: 
//   - WARNING: suggested taht $str is valid token & is tag, otherwise result is undefined
function xml_token_is_broken($str){
    
    // $len = strlen($str);
    // return ( $str[0] === '<' xor $str[$len - 1] === '>' );  // tag not opened or not closed
    
    
    
    // tag not opened or not closed
    $len = strlen($str);
    if ($str[0] === '<' xor $str[$len - 1] === '>') return true;
    
    // 27.01.2015 - AL - исправил ошибку.
    // очень важно для случая когда мы частями читаем файл
    // т.к. часть строки может прчитана неполностью
    // и могут появиться битые биты и из-за этого могут быть сбои
    // (например при импорте элементов справочников)
    if (!c_dtp_is_utf8($str)) return true;
    
    
    
    return false;
    
}



//   check if $str is tag
// parameters:
//   $str - string - token (tag)
// return:
//   true  - is tag
//   false - not tag
// notes: 
//   - WARNING: suggested that $str is valid token, otherwise result is undefined
function xml_token_is_tag($str){
    
    $len = strlen($str);
     
    return (    $len >= 2               // at least two symbols 
             && $str[0]        === '<'  // 1st symbol
             && $str[$len - 1] === '>'  // last symbol
           );
}



//   check if $str is tag, & if so check is it tag with specified name
// parameters:
//   $str - string - token
//   $tag - string - tag name
// return:
//   true  - is tag with specified name
//   false - not tag or not needed tag
function xml_token_is_tag_with_name($str, $tag){

    return ( xml_token_is_tag($str) && !strcmp(xml_read_tag_name($str), $tag) );
}



//   get tag type
// parameters:
//   $str - string - token, tag
// return:
//   int   - one of XML_TAG_XXX constants
//   false - error
// notes: 
//   - WARNING: suggested that $str is valid token & is tag of any type 
function xml_token_tag_type($str){
    
    $len = strlen($str);
    
    if( $len >= 2 ){
        if( $str[0] === '<' ){
            
            if(     $str[1] === '/' )                 return XML_TAG_CLOSING;
            elseif( $str[1] === '?' )                 return XML_TAG_XML;
            elseif( $str[1] === '!' )                 return XML_TAG_COMMENT;
            elseif( substr($str, $len - 2) === '/>' ) return XML_TAG_SHORT;
            else                                      return XML_TAG_OPENING;
        }
    }
    
    return false;
}



//   unescape spec-symbols
// parameters:
//   $str - int, float, string or array
// return:
//   string - escaped string or array
// notes: 
//   - &#DEC; and &#xHEX; also will be decoded to UTF8 
function xml_unescape($str){
    
    // recursive part
    if( is_array($str) ){
        
        $keys = array_keys($str);
        $ci = count($keys);
        for( $i = 0; $i < $ci; $i++ ){
            $str[$keys[$i]] = xml_unescape($str[$keys[$i]]);
        }
        return $str;
    }
    
    // replace '&#DEC;' and '&#xHEX;' with actual symbols encoded in UTF8 
    // old variant (contain error: &#xFF; was not replaced with symbol)
    // if( $ci = preg_match_all('/&#(x?[0-9]+);/i', $str, $matches) ){ // look for '&#DEC;' and '&#xHEX;'
    // new: fixed
    if( $ci = preg_match_all('/&#((?:[0-9]+)|(?:x[0-F]+));/i', $str, $matches) ){ // look for '&#DEC;' and '&#xHEX;'
        
        // d_echo($matches);
        $search  = array();
        $replace = array();
        
        for( $i = 0; $i < $ci; $i++ ){
            $search[]  = $matches[0][$i];
            // if symbol code is given in HEX
            if( $matches[1][$i][0] === 'x' ){
                $matches[1][$i] = substr($matches[1][$i], 1); // cut 1st symbol
                $matches[1][$i] = hexdec($matches[1][$i]);    // convert to decimal
            }
            $matches[1][$i] = (int)$matches[1][$i];
            $replace[] = xml_sym_decode($matches[1][$i]);
        }
        
        // replace each '&#DEC;' or '&#xHEX;' with symbol
        $str = str_replace($search, $replace, $str);
    }
    
    // replace predefined entities
    return str_replace(array('&lt;', '&gt;', '&apos;', '&quot;', '&amp;'),
                       array( '<',   '>',    "'",      '"',      '&'), 
                       $str
           );
}

