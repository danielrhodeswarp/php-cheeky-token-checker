<?php

/**
 * Run PHP token analysis on the specified file to report on:
 * [] Any dangerous / uncommon / noteworthy language features used
 * [] Recommended PHP minimum and maximum versions required to run the specified file
 * [] Top 5 variables used
 * [] Top 5 identifiers used
 * {We also report on if the file lints or not}
 * 
 * @link https://www.php.net/manual/en/tokens.php
 */

//TODO: are 'messy', 'risky', 'advanced' interesting categories of language features?

//----input and set up----------------
$FILENAME = $argv[1];

$IGNORE_TOKENS = ['T_WHITESPACE', 'T_COMMENT', 'T_DOC_COMMENT'];

$SAFE_PREFIX_CHAR_TOKEN = 'char_';  //for 1-char tokens
$SAFE_PREFIX_TEXT_TOKEN = 'text_';

$source = file_get_contents($FILENAME);
$fileTokens = token_get_all($source);

$tokens = ['T_ONE_CHAR' => []]; //note 'T_ONE_CHAR' is our creation

//----build tokens array----------------
foreach ($fileTokens as $token) {
    // simple 1-character token
   if (is_string($token)) {
        $safeTokenText = "{$SAFE_PREFIX_CHAR_TOKEN}{$token}";
       
       if (!array_key_exists($safeTokenText, $tokens['T_ONE_CHAR'])) {
        $tokens['T_ONE_CHAR'][$safeTokenText] = 0;
       }
       $tokens['T_ONE_CHAR'][$safeTokenText]++;
       
   } else { // token array
       list($id, $tokenText, $lineNumber) = $token;

       $tokenName = token_name($id);

       if (in_array($tokenName, $IGNORE_TOKENS)) {
        continue;   //skip
       }

       $safeTokenText = "{$SAFE_PREFIX_TEXT_TOKEN}{$tokenText}";

       if (!array_key_exists($tokenName, $tokens)) {
        $tokens[$tokenName] = [];
       }

       if (!array_key_exists($safeTokenText, $tokens[$tokenName])) {
        $tokens[$tokenName][$safeTokenText] = 0;
       }

       $tokens[$tokenName][$safeTokenText]++;
   }
}

//----test for presence of certain token types----------------
//lint check above all else
$lastLine = exec("php -l {$FILENAME}", $lines, $returnCode);

$lintSuccess = $returnCode === 0;

$report = ['lints' => $lintSuccess];

//if multiple, it's an AND test
$tests = [
    'openingtag' => [
        'T_OPEN_TAG'
    ],
    'closingtag' => [
        'T_CLOSE_TAG'
    ],
    'namespace' => [
        'T_NAMESPACE'
    ],
    'use' => [
        'T_USE'
    ],
    'heredoc/nowdoc' => [
        'T_START_HEREDOC',
        'T_END_HEREDOC'
    ],
    'switchcase' => [
        'T_SWITCH',
        'T_CASE'
    ],
    'goto' => [
        'T_GOTO'
    ],
    'inlinehtml' => [
        'T_INLINE_HTML'
    ],
    'eval' => [
        'T_EVAL'
    ],
    'class' => [
        'T_CLASS'
    ],
    'attributes' => [
        'T_ATTRIBUTE'
    ],
];

//score the tests
foreach ($tests as $testName => $testTokens) {
    $result = true;

    foreach ($testTokens as $testToken) {
        if (!array_key_exists($testToken, $tokens)) {
            $result = false;
            continue;//skip
        }
    }

    $report[$testName] = $result;
}

//minimum version suggestions
$versions = [
    'T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG' => '8.1.0',
    'T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG' => '8.1.0',
    'T_ATTRIBUTE' => '8.0.0',
    'T_COALESCE_EQUAL' => '7.4.0',
    'T_ENUM' => '8.1.0',
    'T_FN' => '7.4.0',
    'T_MATCH' => '8.0.0',
    'T_READONLY' => '8.1.0',
];

$versionMatches = [];

foreach ($versions as $token => $version) {
    if (array_key_exists($token, $tokens)) {
        $versionMatches[$token] = $version;
    }
}

arsort($versionMatches);
$highestTwoVersionMatches = array_slice($versionMatches, 0, 2);

//TODO: *maximum* PHP version (due to deprecated functions / features etc)

//top 5 used variables
$topFiveVariables = [];

if (array_key_exists('T_VARIABLE', $tokens)) {
    $variables = $tokens['T_VARIABLE'];
    arsort($variables);
    $topFiveVariables = array_slice($variables, 0, 5);
}

//top 5 used identifiers
$topFiveIdentifiers = [];

if (array_key_exists('T_STRING', $tokens)) {
    $identifiers = $tokens['T_STRING'];
    arsort($identifiers);
    $topFiveIdentifiers = array_slice($identifiers, 0, 5);
}

//----output results----------------
//debug
//print_r($tokens);

echo "Language features::" . PHP_EOL;

foreach ($report as $test => $result) {
    $resultWord = getResultWord($result);
    echo "{$test}: $resultWord" . PHP_EOL;
}

echo PHP_EOL;
echo "Minimum PHP version suggestions::" . PHP_EOL;
if (empty($highestTwoVersionMatches)) {
    echo "{none}" . PHP_EOL;
}
foreach ($highestTwoVersionMatches as $reason => $minVersion) {
    echo "Due to {$reason}: v{$minVersion}" . PHP_EOL;
}

echo PHP_EOL;
echo "Top 5 most used variables::" . PHP_EOL;
if (empty($topFiveVariables)) {
    echo "{none}" . PHP_EOL;
}
foreach ($topFiveVariables as $safeVariableName => $count) {
    $quotedPrefix = preg_quote($SAFE_PREFIX_TEXT_TOKEN);

    $variableName = preg_replace("/^{$quotedPrefix}/", '', $safeVariableName);
    echo "{$variableName}: {$count}x" . PHP_EOL;
}

echo PHP_EOL;
echo "Top 5 most used identifiers::" . PHP_EOL;
if (empty($topFiveIdentifiers)) {
    echo "{none}" . PHP_EOL;
}
foreach ($topFiveIdentifiers as $safeIdentifierName => $count) {
    $quotedPrefix = preg_quote($SAFE_PREFIX_TEXT_TOKEN);

    $identifierName = preg_replace("/^{$quotedPrefix}/", '', $safeIdentifierName);
    echo "{$identifierName}: {$count}x" . PHP_EOL;
}

//----utility----------------
function getResultWord($bool) {
    if($bool === true) {
        return 'yes';
    }
    return 'no';
}
