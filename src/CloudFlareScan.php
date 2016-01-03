<?php

/**
 * CloudFlareScan
 * 
 * This a simple library to scan a given host for outside of CloudFlare IPs
 * attempting to obtain the real IP of a server behind the CloudFlare CDN.
 * 
 * Notice that this script will not necessarily reveal the real IP of the server.
 * It will just check for some common (sub)domains that don't go trough the
 * CloudFlare network and obtain the IPs. It attempts to take advantage of
 * badly configured DNS.
 *
 * For examples of usage, check the 'examples.php' file.
 *
 * @author Dragomir Yordanov <bad.stupid.monkey@gmail.com>
 * @version  1.2
 * @licence The MIT License (MIT)
 */

class CloudFlareScan {

    public $result = array();
    protected $reg = array();

    public function __construct($cfg=null,$update=false){
        $this->buildConfig((isset($cfg) ? $cfg : null));
        if($update){ $this->updateCFList(); }
        unset($cfg,$update);
    }


    /**
     * Builds the default configuration for the script. Additional sub-domains
     * and CloudFlare IPs can be added by passing an array to this function
     * with the correct keys ands values.
     * @param  (array) $ocfg    - Array with config values to add/overwrite.
     * @return (bool)
     */
    protected function buildConfig($cfg=null){
        $ocfg = array(
            
            'IPv4' => array(
                '199.27.128.0/21',
                '173.245.48.0/20',
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17', 
                '162.158.0.0/15',
                '104.16.0.0/12'
            ),

            'IPv6' => array(
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32'
            ),

            'update' => array(
                'ipv4'=>'https://www.cloudflare.com/ips-v4',
                'ipv6'=>'https://www.cloudflare.com/ips-v6'
            ),

            'subdomains' => array(
                'direct',
                'cpanel',
                'ftp',
                'webmail',
                'mail',
                'whm',
                'www',
            )
        );

        if(isset($cfg) && is_array($cfg)){
            foreach($cfg as $index=>$value){
                $ocfg[$index]=& $value;
                unset($index, $value);
            }
        }

        $this->reg['cfg'] =& $ocfg;
        unset($cfg, $ocfg);

        return true;
    }


    /**
     * Attempts to update the CloudFlare provided IPs. Uses cURL or
     * file_get_contets if the first is not available.
     * Note: file_get_contents requires url_fopen to be enabled in php.ini.
     * @return (bool)
     */
    protected function updateCFList(){
        if(empty($this->reg['cfg']['update'])){ return false; }

        foreach($this->reg['cfg']['update'] as $index=>$url){

            switch(function_exists('curl_init')){

                case true:
                    $ch = curl_init();
                    curl_setopt_array($ch, array(
                        CURLOPT_URL => urlencode($url),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_USERAGENT => 'Mozilla 5.0 (Firefox)'
                    ));

                    $content = curl_exec($ch);
                    curl_close($ch);
                    break;

                case false: default:
                    $content = file_get_contents(urlencode($url));
                    break;
            }

            if(empty($content)){ continue; }
            $this->reg['cfg'][$index] = explode("\n", $content);
        }

        return true;
    }


    /**
     * Checks if PHP was compiled with IPv6 support. Also checks if some
     * functions related to IPv6 addresses exist.
     * @return (bool)
     */
    protected function checkIPv6Support(){
        if(!defined('AF_INET6')){
            throw new Exception('IPv6 support looks unavailable!');
            return false;
        }

        $check_functions = array('inet_pton','pack','dns_get_record');

        foreach($check_functions as $fn){
            if(!function_exists($fn)){
                throw new Exception('IPv6: '.$fn.'() seems unavailable!');
                return false;
            }
        }

        return true;
    }


    /**
     * Attempts to parse a given URL and return the host.domain (example.com).
     * @param $input (string)    - The URL (http://www.example.com)
     * @return (bool||string)
     */
    protected function parseHostname($host){
        $host = str_replace(array('https://','http://'),'',$host);
        $host = explode('.', $host);
        $host = (array_key_exists(count($host)-2,$host)?
            $host[count($host)-2]:'').'.'.$host[count($host)-1];

        $regex = '/^([a-z0-9\-]{1,63}\.[a-z\.]{2,12})$/i';
        $matches = preg_match($regex, trim($host), $output);

        if(!$matches){
            throw new Exception('Domain seems to be invalid!');
            return false;
        }

        unset($host, $regex);
        return $output[0];
    }


    /**
     * Attempts to get the IPv4 addr of a given domain.
     * Uses gethostbyname(). May be somehow slow on some systems.
     * @param $host (string)    - The domain name to query. (example.com)
     * @return (bool||string)
     *
     * Bug = https://php.net/manual/en/function.gethostbyname.php#111684
     */
    protected function getIPv4($host){
        if(!function_exists('gethostbyname')){
            throw new Exception('Function gethostbyname() unavailable!');
            return false;
        }

        if(substr($host,-1)!=='.'){ $host.='.'; } // Bug

        $ip = gethostbyname($host);
        return ((empty($ip)||$ip===$host) ? false : $ip);
    }


    /**
     *  Checks if a IPv4 addr is in a IPv4 subnet/range.
     * @param $subnet (string)    - The subnet/range (127.0.0.1/12)
     * @param $needle (string)    - The IPv4 addr to check.
     * @return (bool)
     */
    protected function compareIPv4($subnet, $needle){
        $subnet = explode('/',$subnet);
        $range = 1<<(32-$subnet[1]);
        $start = ip2long($subnet[0]);
        $end = $start+$range;
        $ip = ip2long($needle);

        return ($ip<=$end&&$ip>=$start);
    }


    /**
     * Attempts to get the IPv6 addr of a given domain.
     * @param $host (string)    - The domain name to query (ipv6.gooogle.com)
     * @return (bool||string)
     */
    protected function getIPv6($host){
        if(!isset($this->reg['ipv6'])){
            throw new Exception('IPv6 support unavailable!');
            return false;
        }

        $ip = dns_get_record($host, DNS_AAAA);

        if(is_array($ip) && !empty($ip[0]['ipv6'])){ $ip = $ip[0]['ipv6']; }
        else { $ip = null; }

        return (isset($ip) ? $ip : false);
    }


    /**
     * Checks if a IPv6 addr is in a IPv6 subnet/range.
     * Thanks to MW. for the method. (http://stackoverflow.com/a/7952169)
     * @param $subnet (string)    - The subnet/range (ffff:ffff::/64)
     * @param $needle (string)    - The IPv6 addr to check.
     * @return (bool)
     */
    protected function compareIPv6($subnet, $needle){
        if(!isset($this->reg['ipv6'])){
            throw new Exception('IPv6 support unavailable!');
            return false;
        }

        $subnet = explode('/',$subnet);
        $subnet[0]=inet_pton($subnet[0]);
        $needle = inet_pton($needle);
        $addr = str_repeat('f',$subnet[1]/4);

        switch ($subnet[1] % 4) {
            case 0: break;
            case 1: $addr .= '8'; break;
            case 2: $addr .= 'c'; break;
            case 3: $addr .= 'e'; break;
        }

        $addr = str_pad($addr,32,'0');
        $addr = pack('H*',$addr);

        return (($needle&$addr)===$subnet[0]);
    }



    /**
     * Scans a host and indicated sub-domains, compares the IPv4 and IPv6 IPs 
     * against the CloudFlare ones to determine if they belong to CloudFlare.
     * @param  (string) $host    - Host to scan (ex: google.com)
     * @return (array)   
     */
    public function scanHost($host){
        unset($this->result);
        $this->result = array();

        $host = $this->parseHostname($host);
        if(!$host){ return false; }

        $result = array();

        $subdomains = $this->reg['cfg']['subdomains'];
        $subdomains[] = $host;

        // Some providers may throw the real IP with this.
        $subdomains[] = substr(sha1(rand(0,1337)),0,15);

        foreach($subdomains as $subdomain){
            $domain = ($subdomain!==$host ? $subdomain.'.' : '').$host;

            $result[$subdomain] = array(
                'IPv4'=>array('addr'=>'Unavailable', 'CF'=>false),
                'IPv6'=>array('addr'=>'Unavailable', 'CF'=>false),
            );

            try { $ipv4 = $this->getIPv4($domain); }
            catch(Exception $e){ $ipv4 = false; }

            try { $ipv6 = $this->getIPv6($domain); }
            catch(Exception $e){ $ipv6 = false; }

            if($ipv4){
                $result[$subdomain]['IPv4']['addr'] = $ipv4;

                foreach($this->reg['cfg']['IPv4'] as $cfip){
                    if($this->compareIPv4($cfip,$ipv4)){
                        $result[$subdomain]['IPv4']['CF'] = true;
                        break;
                    }
                }
            }

            if($ipv6){
                $result[$subdomain]['IPv6']['addr'] = $ipv6;

                foreach($this->reg['cfg']['IPv6'] as $cfip){
                    if($this->compareIPv6($cfip,$ipv6)){
                        $result[$subdomain]['IPv6']['cf'] = true;
                        break;
                    }
                }
            }

            continue;
        }

        unset($host,$subdomains,$domain,$subdomain,$ipv4,$ipv6);
        $this->result = $result;
        return true;
    }
}

?>
