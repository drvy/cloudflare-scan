<?php

if(php_sapi_name()!=='cli'){ die('This should be run in a terminal.'); }
require '../src/CloudFlareScan.php';

/* ----------------------------------------------
    Arguments manager.
---------------------------------------------- */
$args = array('url:','update::','list::','help::');
$args = getopt(null,$args);

if(empty($args) || isset($args['help']) || empty($args['url'])){
    die(displayHelp());
}

$update = (isset($args['update']));
$list = (!empty($args['list']) ? $args['list'] : false);

/* ----------------------------------------------
    Functions
---------------------------------------------- */

/**
 * Displays a help screen with indications of how to use this script.
 * @return (null)
 */
function displayHelp(){
    echo PHP_EOL,'CloudFlareScan 1.2',PHP_EOL,PHP_EOL;
    echo 'Usage: php -f cloudflarescan.php --url=http://example.com',PHP_EOL,PHP_EOL;
    echo 'Options:',PHP_EOL;
    echo ' --url     Specify the host to scan (example.com)',PHP_EOL;
    echo ' --help    Shows this information',PHP_EOL;
    echo ' --update  Get CloudFlare IPs online.',PHP_EOL;
    echo ' --list    File with specific sub-domains to check.',PHP_EOL;
    echo PHP_EOL;
    echo 'Notice: ',PHP_EOL;
    echo ' --list argument requires a plain-text file with the', PHP_EOL;
    echo ' names of the sub-domains to check. Those sub-domains must', PHP_EOL;
    echo ' be separated by a new line each and cannot contain symbols,', PHP_EOL;
    echo ' dots or similar characters. An example for such a file would be:', PHP_EOL;
    echo PHP_EOL;
    echo "\t",'customsubdomain',PHP_EOL;
    echo "\t",'www',PHP_EOL;
    echo "\t",'webmail',PHP_EOL;
    echo "\t",'cpanel',PHP_EOL;
    echo PHP_EOL;
    return null;
}


/**
 * Loads a file and parses it into an array for further usage.
 * @param  (string) $file - File path.
 * @return (bool)
 */
function loadList($file){
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if(!$lines){ die('[!] Can\'t open '.$file.'!'.PHP_EOL); }
    return $lines;
}


/* ----------------------------------------------
    Scan the host
---------------------------------------------- */

if($list){ $cfg = array('subdomains' => loadList($args['list'])); }
else { $cfg=null; }

$scan = new CloudFlareScan($cfg, $update);

try {
    $scan->scanHost($args['url']);
    $result = $scan->result;
    if(empty($result)){ die('[!] Final result is empty!'.PHP_EOL); }

} catch(Exception $e) { die('[!] '.$e->getMessage().PHP_EOL); }

echo 'Scanning: '.$args['url'],PHP_EOL;

foreach($result as $subdomain=>$array){
    echo '[+] ',$subdomain,' -> ';

    echo '[IPv4: '.$array['IPv4']['addr'];
    if($array['IPv4']['addr']!=='Unavailable'){
        echo ' - CloudFlare: ',($array['IPv4']['CF']? 'YES' : 'No');
    }

    echo '] [IPv6: '.$array['IPv6']['addr'];
    if($array['IPv6']['addr']!=='Unavailable'){
        echo ' - CloudFlare: ',($array['IPv6']['CF']? 'YES' : 'No');
    }

    echo ']',PHP_EOL;
}