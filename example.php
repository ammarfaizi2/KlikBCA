<?php
// require "vendor/autoload.php";

require "src/KlikBCA/KlikBCA.php";

use KlikBCA\KlikBCA;

$cred = [
	"user" => "username",
	"pass" => "password"
];

$st = new KlikBCA($cred['user'], $cred['pass']);

$st->login();
$mutasi = $st->mutasi();

print_r(json_encode($mutasi, 128 | JSON_UNESCAPED_SLASHES));
