# KlikBCA
This is the KlikBCA scraper library written in PHP by Ammar Faizi.

# Features
1. Get account balance information.
2. Get account statements given the date range.

# Examples
### 1. Get account balance information.
```php
<?php
// SPDX-License-Identifier: GPL-2.0-only

require __DIR__."/src/KlikBCA/KlikBCA.php";
define("COOKIE_FILE", __DIR__."/cookie.tmp");

/*
 * Uncomment this define() to use proxy.
 */
// define("PROXY", "socks5://139.180.140.164:1080");

$username = "your_klikbca_username";
$password = "your_klikbca_password";

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

	/*
	 * Use proxy if the PROXY constant is defined.
	 */
	if (defined("PROXY"))
		$bca->setProxy(PROXY);

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
```

### 2. Get account statements given the date range.
```php
<?php
// SPDX-License-Identifier: GPL-2.0-only

require __DIR__."/src/KlikBCA/KlikBCA.php";
define("COOKIE_FILE", __DIR__."/cookie.tmp");

/*
 * Uncomment this define() to use proxy.
 */
// define("PROXY", "socks5://139.180.140.164:1080");

$username = "your_klikbca_username";
$password = "your_klikbca_password";

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

	/*
	 * Use proxy if the PROXY constant is defined.
	 */
	if (defined("PROXY"))
		$bca->setProxy(PROXY);

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
```

# License
This project is licensed under the GNU GPL v2.

# Contributing
I welcome pull request through the GitHub repository:
https://github.com/ammarfaizi2/KlikBCA
