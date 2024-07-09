<?php

namespace blurt\blart;

use DateTime;

/**
 * big epic DOCKBLOCK comment
 */

#[Attribute]
class SomeOwdClass {
}

phpinfo();

$a= 'a';
$b = 'b';

$a ??= $b;

echo <<<'EOD'
Example of string spanning multiple lines
using nowdoc syntax. Backslashes are always treated literally,
e.g. \\ and \'.
EOD;

function(){};

$source = file_get_contents('source.php');
$tokens = token_get_all($source);

/*
$heredoc = <<<TEXT
<div>
<p>Some inline HTML</p>
</div>
TEXT;
*/

$summat = 'string of text';

foreach ($tokens as $token) {
   if (is_string($token)) {
       // simple 1-character token
       echo $token;
   } else {
       // token array
       list($id, $text) = $token;

       switch ($id) { 
  
         case T_COMMENT: 
           case T_DOC_COMMENT:
               
               break;

		
           default:
              
               echo $text;
               break;

       }
   }
}
?>
