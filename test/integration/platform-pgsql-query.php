<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database;
	use Redub\ORM;

	include __DIR__ . '/include/common.php';

	$start = microtime();

	$driver	    = new Database\PDO\Pgsql();
	$query      = new Database\Query();
	$connection = new Database\Connection('default', [
		'dbname' => 'redub_test'
	]);

	$connection->setDriver($driver);

	$connection->execute("CREATE TABLE people(
		id SERIAL PRIMARY KEY,
		first_name VARCHAR NOT NULL,
		last_name VARCHAR,
		age INT
	)");

	register_shutdown_function(function() use ($connection) {
		$connection->execute("DROP TABLE people");
	});

	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Matthew','Sahagian')");
	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Allison', NULL)");
	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Jeff', 'Turcotte')");

	//
	// Basic Select
	//

	$query
		-> perform('select', [
			'id', 'first_name', 'last_name', 'age'
		])
		-> on('people');

	echo $driver->getPlatform()->compose($query) . PHP_EOL;

	//
	// Full Identifiers
	//

	$query
		-> perform('select', [
			'people.id', 'people.first_name'
		])
		-> on('people');

	echo $driver->getPlatform()->compose($query) . PHP_EOL;

	//
	// Alias
	//

	$query
		-> perform('select', [
			'p.id', 'p.first_name', 'p.last_name'
		])
		-> on(['people' => 'p']);

	echo $driver->getPlatform()->compose($query) . PHP_EOL;

	var_dump($connection->execute($query)->get(0));

	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
