# CloudFlareScan [![Build Status](https://travis-ci.org/drvy/CloudFlareScan.svg)](https://travis-ci.org/drvy/CloudFlareScan)

A simple library able to scan a given host for outside of CloudFlare IPs in a attempt to obtain the real IP of server behind the CloudFlare CDN.

> This script will not necessarily reveal the real IP of the server. 
> It will just check the IP address of some common (also provided by the user)
> (sub)domains to verify if their IP address is outside of the CloudFlare's
> network. **The idea is to take advantage of a badly configured DNS**.

## Usage
Basic usage of the Class.
```php
require 'src/CloudFlareScan.php';
$scan = new CloudFlareScan();

try {

    $scan->scanHost('http://example.net');
    var_dump($scan->result);
    
} catch(Exception $e) { echo $e->getMessage(),PHP_EOL; }

unset($scan);
```

The result will be an array organized like this:
```
array(
    [subdomain] => array(
        [IPv4] => array(
            [addr] => '127.0.0.1',
            [CF] => (boolean)
        )
        [IPv6] => array(
            [addr] => '::1',
            [CF] => (boolean)
        )
    [subdomain] => ...
    )
)
```
Where 'CF' would be a boolean representing TRUE (It's under CloudFlare's Network) and FALSE (It's not).

**For more examples check the 'examples.php' file.**

## ChangeLog
**Current Version: 1.2**

- Big part of the code rewrited.
- Better formating and completly commented.
- Change TABS to SPACES.
- Rewrite getHostname() due to bugs.
- Add examples.
- Add CLI client.
- Most errors will now result into an Exception instead of just returning false.

