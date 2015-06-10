<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database;
	use Redub\ORM;

	include __DIR__ . '/include/common.php';

	$start = microtime();


	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
