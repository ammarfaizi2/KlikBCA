<?php

namespace KlikBCA;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com>
 * @license MIT
 * @version 2.0.1
 * @package KlikBCA
 */
final class KlikBCA
{
	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $password;

	/**
	 * @var string
	 */
	private $cookieFile;

	/**
	 *
	 * Constructor.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $cookieFile
	 * @return void
	 */
	public function __construct($username, $password, $cookieFile = null)
	{
		$this->username = $username;
		$this->password = $password;
		$this->cookieFile = is_null($cookieFile) ? getcwd()."/cookie.txt" : $cookieFile;
	}

	/**
	 * @return bool
	 */
	public function login()
	{
		$s = $this->exec("https://m.klikbca.com/login.jsp");
		$s = $s["out"];
		if (preg_match_all("/<input.+>/Us", $s, $m)) {
			$posts["value(user_id)"] = $this->username;
			$posts["value(pswd)"] = $this->password;
			$posts["value(Submit)"] = "LOGIN";
			foreach ($m[0] as $v) {
				if (preg_match("/hidden/i", $v) && preg_match("/name=\"(.*)\"/Us", $v, $m)) {
					if (preg_match("/value=\"(.*)\"/Us", $v, $n)) {
						$posts[html_entity_decode($m[1], ENT_QUOTES, "UTF-8")] = html_entity_decode($n[1], ENT_QUOTES, "UTF-8");
					} else {
						$posts[html_entity_decode($m[1], ENT_QUOTES, "UTF-8")] = "";
					}
				}
			}
			$s = $this->exec("https://m.klikbca.com/authentication.do",
				[
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => http_build_query($posts)
				]
			);
		}
	}

	/**
	 * @return array
	 */
	public function accountStatement($startDate, $endDate = null)
	{

		$s = $this->exec("https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt",
				[
					CURLOPT_POST => true
				]
			);
		$s = $s["out"];
		
		// file_put_contents("c.tmp", $s);die;
		// $s = file_get_contents("aaaa.tmp");
		
		preg_match("/<input type=\"hidden\" name=\"as_fid\" value=\"(.*)\"/Us", $s, $m);
		$st = strtotime($startDate);
		$ed = $endDate ? strtotime($endDate) : $st;
		$posts = [
			"r1" => "1",
			"value(D1)" => "0",
			"value(startDt)" => date("d", $st),
			"value(startMt)" => date("m", $st),
			"value(startYr)" => date("Y", $st),
			"value(endDt)" => date("d", $ed),
			"value(endMt)" => date("m", $ed),
			"value(endYr)" => date("Y", $ed)
		];

		if (isset($m[1])) {
			$posts["as_fid"] = $m[1];
		}

		$s = $this->exec("https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview",
				[
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => http_build_query($posts)	
				]
			);
		$s = $s["out"];

		// file_put_contents("b.tmp", $s["out"]);
		// $s = file_get_contents("b.tmp");

		$data = [];
		if (preg_match("/<table width=\"100%\" class=\"blue\">(.*)<\/table>/Us", $s, $m)) {
			preg_match_all("/<tr bgcolor='#.{6}'><td valign='top'>(.*)<\/td><td>(.*)<\/td>/Us", $m[1], $m);
			foreach ($m[2] as $v) {
				$v = explode("<br>", $v);
				$c = explode("<td valign='top'>", $v[$cc = count($v) - 1], 2);
				unset($v[$cc]);
				$data[] = [
					"type" => $c[1],
					"amount" => $c[0],
					"info" => implode(array_map(function($v){
						return trim(html_entity_decode($v, ENT_QUOTES, "UTF-8"));
					}, $v)," ")
				];
			}
		}
		return $data;
	}

	/**
	 * @return array
	 */
	public function balanceInquiry()
	{
		$s = $this->exec("https://m.klikbca.com/balanceinquiry.do",
				[
					CURLOPT_POST => true
				]
			);
		$s = $s["out"];
		preg_match("/<td><font size='1' color='#0000a7'><b>(.*)<\/td>/Us", $s, $m);
		$accountNum = $m[1];
		preg_match("/<td width='5%'><font size='1' color='#0000a7'><b>(.*)<\/td>/U", $s, $m);
		$currency = $m[1];
		preg_match("/<td align='right'><font size='1' color='#0000a7'><b>(.*)<\/td>/U", $s, $m);
		$amount = $m[1];
		return [
			[
				"account_number" => $accountNum,
				"currency" => $currency,
				"amount" => $amount
			]
		];
	}

	/**
	 * @param string $url
	 * @param array  $opt
	 * @return array
	 */
	private function exec($url, $opt = [])
	{
		$ch = curl_init($url);
		$optf = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT => "Opera/9.80 (J2ME/MIDP; Opera Mini/4.2.14912/35.5706; U; id) Presto/2.8.119 Version/11.10",
			CURLOPT_COOKIEFILE => $this->cookieFile,
			CURLOPT_COOKIEJAR => $this->cookieFile
		];
		foreach ($opt as $key => $value) {
			$optf[$key] = $value;
		}
		curl_setopt_array($ch, $optf);
		$out = curl_exec($ch);
		$info = curl_getinfo($ch);
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		return [
			"out" => $out,
			"info" => $info,
			"error" => $error,
			"errno" => $errno
		];
	}
}
