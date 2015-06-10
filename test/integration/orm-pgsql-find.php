<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database;
	use Redub\ORM;

	include __DIR__ . '/include/common.php';
	include __DIR__ . '/include/people.php';

	$start = microtime();

	$config   = new ORM\Configuration\Jin(__DIR__ . '/config', new Parser());
	$driver   = new Database\PDO\Pgsql();
	$mapper   = new ORM\SQL\Mapper();
	$manager  = new ORM\Manager($config);

	$manager->bind('pdo\pgsql', $driver);
	$manager->bind('sql', $mapper);

	$manager->connect($connection = new Redub\Database\Connection('default', [
		'mapper'  => 'sql',
		'driver'  => 'pdo\pgsql',
		'dbname'  => 'redub_test'
	]));


	$connection->execute("CREATE TABLE people(
		id SERIAL PRIMARY KEY,
		first_name VARCHAR NOT NULL,
		last_name VARCHAR,
		team_rank INTEGER
	)");

	register_shutdown_function(function() use ($connection) {
		$connection->execute("DROP TABLE people");
	});

	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Matthew','Sahagian')");
	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Allison', NULL)");
	$connection->execute("INSERT INTO people (first_name, last_name) VALUES('Jeff', 'Turcotte')");

	$people = new People($manager);
	$person = $people->create();

	$person->setFirstName('Brand');
	$person->setLastName('New');

	echo $person->getFirstName()       . PHP_EOL;
	echo $person->getLastName()        . PHP_EOL;

	$person = $people->find(1);

	echo $person->getFirstName()       . PHP_EOL;
	echo $person->getLastName()        . PHP_EOL;

	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
