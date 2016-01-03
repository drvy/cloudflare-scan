<?php

require 'src/CloudFlareScan.php';

/* ----------------------------------------------
    Basic usage
---------------------------------------------- */
$scan = new CloudFlareScan();

try {

    $scan->scanHost('http://example.net');
    var_dump($scan->result);

} catch(Exception $e) { echo $e->getMessage(),PHP_EOL; }

unset($scan);


/* ----------------------------------------------
    Setting specific sub-domains for host.
---------------------------------------------- */
$cfg = array(
    'subdomains' => array(
        'hello',
        'world',
        'www',
        'admin'
    )
);

$scan = new CloudFlareScan($cfg);

try {

    $scan->scanHost('http://example.net');
    var_dump($scan->result);

} catch(Exception $e) { echo $e->getMessage(),PHP_EOL; }

unset($scan,$cfg);


/* ----------------------------------------------
    Auto-update CloudFlare IPs list.
---------------------------------------------- */
$scan = new CloudFlareScan(null,true);

try {

    $scan->scanHost('http://example.net');
    var_dump($scan->result);

} catch(Exception $e) { echo $e->getMessage(),PHP_EOL; }

unset($scan);