<?php

require __DIR__."/src/KlikBCA/KlikBCA.php";

/**
 * This contains $username and $password
 */
require __DIR__."/credentials.tmp";

define("COOKIE_FILE", __DIR__."/cookie.tmp");

/**
 * Show the account balance information.
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
function show_balance($username, $password)
{
	$bca = new KlikBCA\KlikBCA($username, $password, COOKIE_FILE);
	$ret = $bca->login();
	if (!$ret)
		goto err;

	$ret = $bca->balanceInquiry();
	if (!$ret)
		goto err;

	printf("Balance information:\n%s\n\n",
               json_encode($ret, JSON_PRETTY_PRINT));
	return;

err:
	printf("Error: %s\n", $bca->getErr());
}
show_balance($username, $password);

/**
 * Show account statements given the date range.
 *
 * @param string $username
 * @param string $password
 * @param string $startDate
 * @param string $endDate
 * @return bool
 */
function show_account_statements($username, $password, $startDate, $endDate)
{
	$bca = new KlikBCA\KlikBCA($username, $password, COOKIE_FILE);
	$ret = $bca->login();
	if (!$ret)
		goto err;

	$ret = $bca->accountStatement($startDate, $endDate);
	if (!$ret)
		goto err;

	printf("Account statements for %s to %s:\n%s\n\n", $startDate, $endDate,
	       json_encode($ret, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	return;

err:
	printf("Error: %s\n", $bca->getErr());
}
$startDate = "2022-04-25";
$endDate   = "2022-05-01";
show_account_statements($username, $password, $startDate, $endDate);
