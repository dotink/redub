<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database\Query;
	use Redub\Database;
	use Redub\ORM;

	include __DIR__ . '/include/common.php';

	$start = microtime();

	$connection = new Redub\Database\Connection('default', [
		'name' => 'redub_test'
	]);

	$driver = new Redub\Database\PDO\Pgsql();

	$driver->connect($connection);

	$connection->execute(function($query) {
		$query->bindStatement("
			CREATE TABLE people(
				id SERIAL PRIMARY KEY,
				first_name VARCHAR NOT NULL,
				last_name VARCHAR NOT NULL,
				date_of_birth DATE,
				biography TEXT
			)
		");
	});

	register_shutdown_function(function() use ($connection) {
		$connection->execute("DROP TABLE people");
	});

	$connection->execute(function($query) {
		$query
			-> perform('insert', [
				'first_name'    => 'Matthew',
				'last_name'     => 'Sahagian',
				'date_of_birth' => '1984-04-28',
				'biography'     => 'A PHP Developer'
			])
			-> on('people');
	});

	$results = $connection->execute(function($query) {
		$query
			-> perform('select')
			-> on('people');
	});

	foreach ($results as $result) {
		echo 'First Name: ' . $result['first_name'] . PHP_EOL;
	}

	$person = $results->get(0);

	$connection->execute(function($query) use ($person) {
		$query
			-> perform('update', [
				'first_name' => 'Matt'
			])
			-> on('people')
			-> where([
				'id ==' => $person['id']
			]);
	});

	$connection->execute(function($query) {
		$query
			-> perform('delete')
			-> on('people')
			-> where([
				'id ==' => 1
			]);
	});

	$results = $connection->execute(function($query) {
		$query
			-> perform('select')
			-> on('people');
	});

	foreach ($results as $result) {
		echo 'First Name: ' . $result['first_name'] . PHP_EOL;
	}

	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
