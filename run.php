<?php

require __DIR__."/vendor/autoload.php";
require __DIR__."/credentials.tmp";

$st = new KlikBCA\KlikBCA($username, $password, __DIR__."/cookiea.tmp");
$st->login();
$balance = $st->balanceInquiry();
$accountStatement = $st->accountStatement("2018-05-07", "2018-05-13");

print json_encode(
	[
		"balance_inquiry" => $balance,
		"account_statement" => $accountStatement
	]
	, 128 | JSON_UNESCAPED_SLASHES
);
