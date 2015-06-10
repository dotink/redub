<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database;

	include __DIR__ . '/include/common.php';

	$start = microtime();

	$driver	    = new Database\PDO\Pgsql();
	$connection = new Database\Connection('default', [
		'dbname' => 'redub_test'
	]);

	$connection->setDriver($driver);

	$connection->execute("CREATE TABLE people(
		id SERIAL PRIMARY KEY,
		first_name VARCHAR NOT NULL,
		last_name VARCHAR
	)");

	register_shutdown_function(function() use ($connection) {
		$connection->execute("DROP TABLE people");
	});

	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Matthew','Sahagian')");
	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Allison', NULL)");
	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Jeff', 'Turcotte')");

	var_dump(
		$connection->execute("SELECT * FROM people WHERE id = ?", [1 => 1])->get(0)
	);

	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
