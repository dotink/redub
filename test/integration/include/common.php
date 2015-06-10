<?php

	include __DIR__ . '/../../../vendor/autoload.php';

	/**
	 *
	 */
	function microtime_diff($start, $end = null)
	{
		if (!$end) {
			$end = microtime();
		}
		list($start_usec, $start_sec) = explode(" ", $start);
		list($end_usec, $end_sec) = explode(" ", $end);
		$diff_sec = intval($end_sec) - intval($start_sec);
		$diff_usec = floatval($end_usec) - floatval($start_usec);

		return floatval($diff_sec) + $diff_usec;
	}
