<?php
// SPDX-License-Identifier: GPL-2.0-only

namespace KlikBCA;

use Exception;

/**
 * @author Ammar Faizi <ammarfaizi2@gmail.com> # https://www.facebook.com/ammarfaizi2
 * @license GPL-2.0-only
 * @version 3.0
 * @package KlikBCA
 */
final class KlikBCA
{
	/**
	 * @const string
	 */
	private const USER_AGENT = "Opera/9.80 (J2ME/MIDP; Opera Mini/4.2.14912/35.5706; U; id) Presto/2.8.119 Version/11.10";

	/**
	 * @const string
	 */
	private const LOCKED_UP_PATTERN = "/'(Anda dapat melakukan login kembali setelah 5 menit.+?)'/";

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
	 * @var string
	 */
	private $error = "";

	/**
	 * @var bool
	 */
	private $sessActive = false;

	/**
	 * @var ?string
	 */
	private $proxy = NULL;

	/**
	 * @var bool
	 */
	private $reuseSession = false;

	/**
	 *
	 * When an error happens while parsing the HTML page, it's hard to
	 * conclude what is going wrong. Allow the user to set a dump error
	 * file to dump the HTML page string into a file for manual
	 * investigation.
	 *
	 * @var ?string
	 */
	private $dumpHtmlErrorFile = NULL;

	/**
	 * @var int
	 */
	private $nrDumpHtml = 0;

	/**
	 * Constructor.
	 *
	 * @param string $username
	 * @param string $password
	 * @param ?string $cookieFile
	 * @throws \Exception
	 */
	public function __construct($username, $password, $cookieFile = NULL)
	{
		if (!is_string($username))
			throw Exception("Username must be a string");

		if (!is_string($password))
			throw Exception("Password must be a string");

		if (!is_string($cookieFile))
			$cookieFile = getcwd()."/klikbca_cookie.txt";

		touch($cookieFile);
		$cookieFile = realpath($cookieFile);
		if (!is_string($cookieFile))
			throw Exception("Invalid cookiefile");

		if (!is_writable($cookieFile))
			throw Exception("Cookie file is not writable: {$cookieFile}");

		$this->username = $username;
		$this->password = $password;
		$this->cookieFile = $cookieFile;
	}

	/**
	 * Destructor.
	 *
	 * Make sure we are logged out to prevent stale session.
	 */
	public function __destruct()
	{
		if ($this->reuseSession)
			/*
			 * The user explicitly tells us that they want to
			 * reuse the existing session.
			 *
			 * Do not logout and do not delete the cookie here.
			 */
			return;

		if ($this->sessActive)
			$this->logout();

		@unlink($this->cookieFile);
	}

	/**
	 * @param bool $reuse
	 * @return void
	 */
	public function setReuseSession($reuse)
	{
		$this->reuseSession = (bool)$reuse;
	}

	/**
	 * @param string $file
	 * @return void
	 */
	public function setDumpHtmlErrorFile($file)
	{
		$this->dumpHtmlErrorFile = $file;
	}

	/**
	 * @param string $url
	 * @param string $html
	 * @return void
	 */
	private function htmlDumpError($url, $html)
	{
		if (!is_string($this->dumpHtmlErrorFile))
			return;
		$html = "==========================\n".
			"URL: {$url}\n".
			"Date: ".gmdate("c")."\n".
			"==========================\n".
			"{$html}\n".
			"==========================\n\n";
		$this->nrDumpHtml++;
		file_put_contents($this->dumpHtmlErrorFile, $html, FILE_APPEND);
	}

	/**
	 * @return bool
	 */
	public function logout()
	{
		$o = $this->curl("https://m.klikbca.com/authentication.do?value(actions)=logout", [
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => ""
		]);
		if (self::isCurlErr($o)) {
			$this->err = self::buildCurlErr($o);
			return false;
		}

		$this->sessActive = false;
		return true;
	}

	/**
	 * @return bool
	 */
	public function login()
	{
		$err = "";
		$m = [];

		$url = "https://m.klikbca.com/login.jsp";
		$o = $this->curl($url);
		if (self::isCurlErr($o)) {
			$err = self::buildCurlErr($o);
			goto out_err;
		}
		$o = $o["out"];

		if (!preg_match_all("/<input.+>/Us", $o, $m)) {
			$err = "Cannot find <input> tags on the login page";
			goto out_err;
		}

		$posts = [
			"value(user_id)" => $this->username,
			"value(pswd)"    => $this->password,
			"value(Submit)"  => "LOGIN"
		];

		/*
		 * Catch all hidden inputs.
		 */
		foreach ($m[0] as $v) {
			if (!preg_match("/hidden/i", $v))
				/*
				 * This is not a hidden input, skip it!
				 */
				continue;

			if (!preg_match("/name=\"(.*)\"/Us", $v, $m)) {
				$err = "Cannot get the input tag name";
				goto out_err;
			}

			$key = html_entity_decode($m[1], ENT_QUOTES, "UTF-8");
			if (preg_match("/value=\"(.*)\"/Us", $v, $m))
				$val = html_entity_decode($m[1], ENT_QUOTES, "UTF-8");
			else
				$val = "";

			$posts[$key] = $val;
		}

		$url = "https://m.klikbca.com/authentication.do";
		$o = $this->curl($url, [
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => http_build_query($posts)
		]);
		if (self::isCurlErr($o)) {
			$err = self::buildCurlErr($o);
			goto out_err;
		}

		if (!preg_match("/accountstmt.do\?value/", $o["out"])) {
			/*
			 * Maybe wrong username / password?
			 */
			$err = "Login failed";
			goto out_err;
		}

		$this->sessActive = true;
		return true;

	out_err:
		$this->setErr($url, $err, $o);
		return false;
	}

	/**
	 * @return bool
	 */
	private function sessionCheck()
	{
		/*
		 * If the user wants to reuse the session, we are not
		 * responsible to check the value of $this->sessActive.
		 *
		 * The user is responsible to guarantee the session
		 * is still active, otherwise they will fail to
		 * execute some methods.
		 */
		if ($this->reuseSession)
			return true;

		/*
		 * The user doesn't care with reusing session, so we
		 * must take care of their session here. If they
		 * haven't been logged in, let's make them be.
		 */
		if (!$this->sessActive)
			return $this->login();

		/*
		 * We are holding an active session here, let's go!
		 */
		return true;
	}

	/**
	 * @return ?array
	 */
	public function balanceInquiry()
	{
		if (!$this->sessionCheck())
			return NULL;

		$url = "https://m.klikbca.com/balanceinquiry.do";
		$o = $this->curl($url, [
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => ""
		]);
		if (self::isCurlErr($o)) {
			$err = self::buildCurlErr($o);
			goto out_err;
		}

		$o = $o["out"];
		if (!preg_match("/<td><font size='1' color='#.+'><b>(.*)<\/td>/Us", $o, $m)) {
			$err = "Cannot find appropriate tags (1)";
			goto out_err;
		}
		$accountNum = $m[1];

		if (!preg_match("/<td width='5%'><font size='1' color='#.+'><b>(.*)<\/td>/U", $o, $m)) {
			$err = "Cannot find appropriate tags (2)";
			goto out_err;
		}
		$currency = $m[1];

		if (!preg_match("/<td align='right'><font size='1' color='#.+'><b>(.*)<\/td>/U", $o, $m)) {
			$err = "Cannot find appropriate tags (3)";
			goto out_err;
		}
		$amount = $m[1];

		return [
			"account_number"    => $accountNum,
			"currency"          => $currency,
			"available_balance" => $amount
		];

	out_err:
		$this->setErr($url, $err, $o);
		return NULL;
	}

	/**
	 * @return ?array
	 */
	public function accountStatement($startDate, $endDate = null)
	{
		if (!$this->sessionCheck())
			return NULL;

		$err = "";
		$url = "https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt";
		$o = $this->curl($url, [
			CURLOPT_POST       => true,
			CURLOPT_POSTFIELDS => ""
		]);
		if (self::isCurlErr($o)) {
			$err = self::buildCurlErr($o);
			goto out_err;
		}
		$o = $o["out"];

		if (!preg_match_all("/<input.+>/Us", $o, $m)) {
			$err = "Cannot find <input> tags on the account statement page";
			goto out_err;
		}

		$st = strtotime($startDate);
		$ed = $endDate ? strtotime($endDate) : $st;
		if ($ed < $st) {
			$tmp = $ed;
			$ed  = $st;
			$st  = $tmp;
		}

		$posts = [
			"r1"             => "1",
			"value(D1)"      => "0",
			"value(startDt)" => date("d", $st),
			"value(startMt)" => date("m", $st),
			"value(startYr)" => date("Y", $st),
			"value(endDt)"   => date("d", $ed),
			"value(endMt)"   => date("m", $ed),
			"value(endYr)"   => date("Y", $ed)
		];

		/*
		 * Catch all hidden inputs.
		 */
		foreach ($m[0] as $v) {
			if (!preg_match("/hidden/i", $v))
				/*
				 * This is not a hidden input, skip it!
				 */
				continue;

			if (!preg_match("/name=\"(.*)\"/Us", $v, $m)) {
				$err = "Cannot get the input tag name";
				goto out_err;
			}

			$key = html_entity_decode($m[1], ENT_QUOTES, "UTF-8");
			if (preg_match("/value=\"(.*)\"/Us", $v, $m))
				$val = html_entity_decode($m[1], ENT_QUOTES, "UTF-8");
			else
				$val = "";
			$posts[$key] = $val;
		}

		$url = "https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview";
		$o = $this->curl($url, [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($posts)
		]);
		if (self::isCurlErr($o)) {
			$err = self::buildCurlErr($o);
			goto out_err;
		}
		$o = $o["out"];


		if (!preg_match("/<table width=\"100%\" class=\"blue\">(.*)<\/table>/Us", $o, $m)) {
			$err = "Cannot find the table on the account statement page";
			goto out_err;
		}

		if (!preg_match_all("/<tr bgcolor='#.{6}'><td valign='top'>(.*)<\/td><td>(.*)<\/td>/Us", $m[1], $m)) {
			$err = "Cannot parse the table on the account statement page";
			goto out_err;
		}

		foreach ($m[2] as $v) {
			$v  = explode("<br>", $v);
			$cc = count($v) - 1;
			$c  = explode("<td valign='top'>", $v[$cc], 2);
			unset($v[$cc]);

			foreach ($v as &$vp)
				$vp = trim(html_entity_decode($vp, ENT_QUOTES, "UTF-8"));

			$data[] = [
				"type"   => $c[1],
				"amount" => $c[0],
				"info"   => implode(" ", $v)
			];
		}
		return $data;

	out_err:
		$this->setErr($url, $err, $o);
		return NULL;
	}

	/**
	 * A method to set proxy for the cURL request. To unset the proxy,
	 * call this function with no argument or a NULL argument.
	 *
	 * @param string $proxy
	 * @return void
	 */
	public function setProxy($proxy = NULL)
	{
		$this->proxy = $proxy;
	}

	/**
	 * @param string $url
	 * @param array  $opt
	 * @return array
	 */
	private function curl($url, $opt = [])
	{
		$ch = curl_init($url);
		$optDef = [
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_USERAGENT	=> self::USER_AGENT,
			CURLOPT_COOKIEJAR	=> $this->cookieFile,
			CURLOPT_COOKIEFILE	=> $this->cookieFile
		];

		if (is_string($this->proxy))
			$optDef[CURLOPT_PROXY] = $this->proxy;

		foreach ($opt as $k => $v)
			$optDef[$k] = $v;

		curl_setopt_array($ch, $optDef);
		$out  = curl_exec($ch);
		$info = curl_getinfo($ch);
		$err  = curl_error($ch);
		$ern  = curl_errno($ch);
		curl_close($ch);
		return [
			"out"	=> $out,
			"info"	=> $info,
			"err"	=> $err,
			"ern"	=> $ern
		];
	}

	/**
	 * @param string        $url
	 * @param string        $msg
	 * @param array|string  $o
	 * @return void
	 */
	private function setErr($url, $msg, $o)
	{
		if (isset($o["out"]))
			$o = $o["out"];

		$err_extra = "";
		if (isset($url, $o) && $o) {
			$this->htmlDumpError($url, $o);

			if (preg_match(self::LOCKED_UP_PATTERN, $o, $m))
				$err_extra = " (possible locked up: {$m[1]})";
		}
		$this->error = $msg.$err_extra;
	}

	/**
	 * @param array $o
	 * @return bool
	 */
	private static function isCurlErr($o)
	{
		if (!isset($o["ern"], $o["err"]))
			return false;

		return $o["ern"] || $o["err"];
	}

	/**
	 * @param array $o
	 * @return string
	 * @throws \Exception
	 */
	private static function buildCurlErr($o)
	{
		if (!isset($o["ern"], $o["err"]))
			throw \Exception("Unknown error");

		return sprintf("(%d): %s (nrDumpHtml = %d)", (int)$o["ern"],
				(string)$o["err"], $this->nrDumpHtml);
	}

	/**
	 * @return string
	 */
	public function getErr()
	{
		return $this->error;
	}
}
