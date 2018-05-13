<?php

require __DIR__."/vendor/autoload.php";
require __DIR__."/credentials.tmp";

$st = new KlikBCA\KlikBCA($username, $password, __DIR__."/cookie.tmp");
// $st->login();
$st = $st->balanceInquiry();

print_r($st);
