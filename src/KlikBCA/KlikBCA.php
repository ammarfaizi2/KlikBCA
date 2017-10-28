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
		return $this->exec("https://m.klikbca.com/login.jsp");
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
				CURLOPT_COOKIEFILE 		=> $this->cookiefile,
				CURLOPT_COOKIEJAR  		=> $this->cookiefile,
				CURLOPT_USERAGENT		=> "Opera/9.80 (Android; Opera Mini/19.0.2254/37.9389; U; en) Presto/2.12.423 Version/12.16"
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
		$no  = curl_errno($ch) or $out = "Error ({$no}) : ".curl_error($ch);
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