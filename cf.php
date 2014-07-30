<?php

/*


*/

class drvyCFScan {
	private $reg = array();
	public $result = null;

	public function __construct($host=null,$cfg=null,$update=false){
		if(!isset($host)){return false;}
		if(isset($cfg)){ $this->build_config($cfg); } else { $this->build_config(); }
		if(!empty($update)){ $this->update_CFIPs(); }

		$this->result = $this->scan_host($host);
		unset($this->reg,$host,$cfg,$update);
	}

	/**
	 * Builds the default config. Set's $this->reg['cfg']
	 * cf4 = CloudFlare's IPv4 subs/range. | cf6 = CloudFlare's IPv6 subs/range.
	 * @param $ocfg (array)	- Array with the configuration to overwrite.
	 * @return (bool)			- Always returns true (·_·)
	 */
	private function build_config($ocfg=null){
		$cfg = array();

		$cfg['cf4'] = array('199.27.128.0/21', '173.245.48.0/20','103.21.244.0/22',
			'103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
			'190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
			'162.158.0.0/15', '104.16.0.0/12'
		);

		$cfg['cf6'] = array('2400:cb00::/32','2606:4700::/32','2803:f800::/32','2405:b500::/32','2405:8100::/32');

		$cfg['update'] = array('cf4'=>'https://www.cloudflare.com/ips-v4','cf6'=>'https://www.cloudflare.com/ips-v6');

		$cfg['subs'] = array('direct','cpanel','ftp','webmail','mail','whm','www');

		if(isset($ocfg)&&is_array($ocfg)){
			foreach($ocfg as $i=>$v){ $cfg[$i]=$v; }
		}

		$this->reg['cfg'] =& $cfg;
		return true;
	}

	/**
	 * Attempts to update the CloudFlare subs/range.
	 * Uses file_get_contents() so PHP.ini must be allow_url_fopen configured.
	 * @return (bool)		- True || False
	 */
	protected function update_CFIPs(){
		if(!isset($this->reg['cfg']['update'])){ return false; }
		foreach($this->reg['cfg']['update'] as $index=>$url){
			$content = file_get_contents($url);
			if(empty($content)){ continue; }
			$this->reg['cfg'][$index] = explode("\n",$content);
		}
		return true;
	}

	/**
	 * Checks if PHP was compiled with IPv6 support and other
	 * functions necessary for the script's IPv6 managment.
	 * @return (bool)	- True || False
	 */
	protected function check_IPv6(){
		if(!defined('AF_INET6')){return false;}
		$c = array('inet_pton','pack','dns_get_record');
		foreach($c as $f){ if(!function_exists($f)){ return false; } continue; }
		return true;
	}

	/**
	 * Attempts to parse a given URL and return only the host.domain (example.com).
	 * @param $input (string)	- The URL (http://www.example.com)
	 * @return (bool)				- False (If unable to find the host).
	 * @return (string)			- The host.domain (example.com)
	 */
	protected function parse_hostname($input=null){
		if(!isset($input)){ return false; }
		$host = parse_url($input);
		$host = (isset($host['host']) ? $host['host'] : '');
		$m = preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i',$host,$reg);
		return ($m ? $reg['domain'] : false);
	}

	/**
	 * Attempts to get the IPv4 addr of a given domain.
	 * Uses gethostbyname(). May be somehow slow on some systems.
	 * @param $domain (string)	- The domain name to query. (example.com)
	 * @return (bool)				- False (If failed to retrieve IP).
	 * @return (string)			- The IPv4 addr.
	 *
	 * Bug = http://es1.php.net/manual/en/function.gethostbyname.php#111684
	 */
	protected function get_IPv4($domain=null){
		if(!isset($domain)||!function_exists('gethostbyname')){ return false; }
		if(substr($domain,-1)!=='.'){$domain.='.';} // Bug
		$ip = gethostbyname($domain);
		return ((empty($ip)||$ip===$domain) ? false : $ip);
	}

	/**
	 * Attempts to get the IPv6 addr of a given domain.
	 * Uses dns_get_record(). Will return false if no IPv6 support is detected.
	 * @param $domain (string)	- The domain name to query. (ipv6.gooogle.com)
	 * @return (bool)				- False (If no IPv6 addr found ^).
	 * @return (string)			- The IPv6 addr.
	 */
	protected function get_IPv6($domain=null){
		if(!isset($domain)||!isset($this->reg['ipv6'])){ return false; }
		$ip = dns_get_record($domain,DNS_AAAA);
		return ((empty($ip[0]['ipv6'])||!is_array($ip)) ? false : $ip[0]['ipv6']);
	}

	/**
	 * Checks if a IPv6 addr is in a IPv6 subnet/range.
	 * Thanks to MW. for the method. (http://stackoverflow.com/a/7952169)
	 * @param $subnet (string)	- The subnet/range (ffff:ffff::/64)
	 * @param $needle (string)	- The IPv6 addr to check.
	 * @return (bool) 			- True || False
	 */
	protected function compare_IPv6($subnet=null,$needle=null){
		if(!isset($subnet,$needle)||!isset($this->reg['ipv6'])){ return false; }
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
	 *	Checks if a IPv4 addr is in a IPv4 subnet/range.
	 * @param $subnet (string)	- The subnet/range (127.0.0.1/12)
	 * @param $needle (string)	- The IPv4 addr to check.
	 * @return (bool)				- True || False
	 */
	protected function compare_IPv4($subnet=null,$needle=null){
		if(!isset($subnet,$needle)){ return false; }
		$subnet = explode('/',$subnet);
		$range = 1<<(32-$subnet[1]);
		$start = ip2long($subnet[0]);
		$end = $start+$range;
		$ip = ip2long($needle);
		return ($ip<=$end&&$ip>=$start);
	}

	/**
	 * The dorrer...
	 *
	 */
	protected function scan_host($input=null){
		if(!isset($input)||empty($this->reg['cfg']['subs'])){ return false; }

		$host = $this->parse_hostname($input);
		if(!$host){ return false; }

		$result = array();
		$this->reg['cfg']['subs'][] = $host;
		$this->reg['cfg']['subs'][] = md5(rand(0,99)); // Some providers may throw the real IP with this.

		foreach($this->reg['cfg']['subs'] as $subdomain){
			$domain = ($subdomain!==$host ? $subdomain.'.' : '').$host;

			$result[$subdomain] = array(
				'ipv4'=>array('addr'=>'Unknown','cf'=>false),
				'ipv6'=>array('addr'=>'Unknown','cf'=>false),
			);

			$ipv4 = $this->get_IPv4($domain);
			$ipv6 = $this->get_IPv6($domain);

			if($ipv4){
				$result[$subdomain]['ipv4']['addr'] = $ipv4;

				foreach($this->reg['cfg']['cf4'] as $cfip){
					if($this->compare_IPv4($cfip,$ipv4)){
						$result[$subdomain]['ipv4']['cf'] = true;
						break;
					}
				}
			}

			if($ipv6){
				$result[$subdomain]['ipv6']['addr'] = $ipv6;

				foreach($this->reg['cfg']['cf6'] as $cfip){
					if($this->compare_IPv6($cfip,$ipv6)){
						$result[$subdomain]['ipv6']['cf'] = true;
						break;
					}
				}
			}

			continue;
		}
		return $result;
	}

}

?>
