<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database\Query;
	use Redub\Database;
	use Redub\ORM;

	include __DIR__ . '/include/common.php';

	$start = microtime();

	$driver	    = new Database\PDO\Pgsql();
	$connection = new Database\Connection('default', [
		'name' => 'redub_test'
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

	foreach ($connection->getRepositories() as $table) {
		var_dump($table);
		var_dump($connection->getFields($table));
	}

	//
	// Basic Select
	//

	$query = (new Query())
		-> perform('select', [
			'id', 'first_name', 'last_name', 'age'
		])
		-> on('people');

	echo $driver->getPlatform()->compose($query) . PHP_EOL;

	//
	// Full Identifiers
	//

	$query = (new Query())
		-> perform('select', [
			'people.id', 'people.first_name'
		])
		-> on('people');

	echo $driver->getPlatform()->compose($query) . PHP_EOL;

	//
	// Criteria
	//

	$criteria = new Database\Criteria();

	$query = (new Query())
		-> perform('select', [
			'p.id', 'p.first_name', 'p.last_name'
		])
		-> on(['people' => 'p'])
		-> where(
			$criteria->where(['p.id ==' => 1])
		);

	//
	// Where
	//

	$query = (new Query())
		-> perform('select')
		-> with(['p.id', 'p.first_name', 'p.last_name'])
		-> on(['people' => 'p'])
		-> where([
			'all' => [
				'p.first_name ==' => 'Matthew',
				'p.last_name =='  => 'Sahagian',
				[
					'any' => [
						'p.owner =='  => TRUE,
						'p.banned ==' => TRUE
					]
				]
			]
		]);

	echo $driver->getPlatform()->compose($query) . PHP_EOL;


	//
	// Where
	//

	$query = (new Query())
		-> perform('select')
		-> with(['p.id', 'p.first_name', 'p.last_name'])
		-> on(['people' => 'p'])
		-> where([
			'all' => [
				'p.first_name ==' => 'Matthew',
				'p.last_name =='  => 'Sahagian'
			]
		]);


	echo $driver->getPlatform()->compose($query) . PHP_EOL;

	var_dump($connection->execute($query)->get(0));

	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
