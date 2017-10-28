<?php

namespace KlikBCA;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license MIT
 * @version 0.0.1
 */
final class KlikBCA
{	

	/**
	 * @var string
	 */
	private $user;

	/**
	 * @var string
	 */
	private $pass;

	/**
	 * Constructor.
	 *
	 * @param string $user
	 * @param string $pass
	 */
	public function __construct($user, $pass, $cookiefile = null)
	{
		$this->user = $user;
		$this->pass = $pass;
		$this->cookiefile = $cookiefile ? $cookiefile : realpath(".")."/cookie.txt";
	}

	/**
	 * @param string
	 */
	public function login()
	{
		$a = $this->exec("https://m.klikbca.com/login.jsp");
		file_put_contents("b.tmp", $a);
		// $a = file_get_contents("b.tmp");
		// POSTDATA=value%28user_id%29=qwe&value%28pswd%29=qwezxc&value%28Submit%29=LOGIN&value%28actions%29=login&value%28user_ip%29=141.92.132.114&user_ip=141.92.132.114&value%28mobile%29=true&mobile=true
		$b = explode("<input ", $a) xor $build = [];
		unset($b[0]);
		foreach ($b as $val) {
			$c = explode("name=\"", $val, 2);
			if (isset($c[1])) {
				$c = explode("\"", $c[1], 2);
				if ($c[0] === "value(user_id)") {
					$d[0] = $this->user;
				} elseif ($c[0] === "value(pswd)") {
					$d[0] = $this->pass;
				} else {
					$d = explode("value=\"", $val, 2);
					if (isset($d[1])) {
						$d = explode("\"", $d[1], 2);
					}
				}
			}
			$build[$c[0]] = $d[0];
		}
		var_dump($build);


		die;
	}

	/**
	 * compact opt
	 *
	 * @param array $opt
	 */
	private function compactXorCurlOpt(&$opt)
	{
		$defopt = [
				CURLOPT_RETURNTRANSFER 	=> true,
				CURLOPT_SSL_VERIFYPEER 	=> false,
				CURLOPT_SSL_VERIFYHOST 	=> false,
				CURLOPT_FOLLOWLOCATION 	=> true,
				CURLOPT_CONNECTTIMEOUT	=> 15,
				CURLOPT_COOKIEFILE 		=> $this->cookiefile,
				CURLOPT_COOKIEJAR  		=> $this->cookiefile,
				CURLOPT_USERAGENT		=> "Opera/9.80 (Android; Opera Mini/19.0.2254/37.9389; U; en) Presto/2.12.423 Version/12.16",
				
				CURLOPT_TIMEOUT 		=> 15
			];
		if ($opt === null) {
			$opt = $defopt;
			return true;
		} elseif (is_array($opt)) {
			foreach ($opt as $key => $value) {
				$defopt[$key] = $value;
			}
			$out = $defopt;
			return true;
		}
		throw new \Exception("option invalid!", 1);
	}

	/**
	 * @param string $url
	 * @param array  $opt
	 */
	private function exec($url, $opt = null)
	{
		$ch = curl_init($url);
		$this->compactXorCurlOpt($opt);
		curl_setopt_array($ch, $opt);
		$out = curl_exec($ch);
		$no  = curl_errno($ch) and $out = "Error ({$no}) : ".curl_error($ch);
		return $out;
	}

	/**
	 * @return null
	 */
	public function __debugInfo()
	{
		return null;
	}
}