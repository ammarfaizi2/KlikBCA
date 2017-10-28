<?php
require "vendor/autoload.php";

use KlikBCA\KlikBCA;

$cred = json_decode(file_get_contents("a.tmp"), true);


$st = new KlikBCA($cred['user'], $cred['pass']);
// $st->login();
print_r(json_encode($st->mutasi(), 128 | JSON_UNESCAPED_SLASHES));
