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

	$config->bind('drivers\pgsql', $driver);
	$config->bind('mappers\sql',   $mapper);

	$manager->connect($connection = new Redub\Database\Connection('default', [
		'dbname' => 'redub_test'
	]));

	$connection->execute("CREATE TABLE teams(
		id SERIAL PRIMARY KEY,
		name VARCHAR NOT NULL
	)");

	$connection->execute("CREATE TABLE people(
		id SERIAL PRIMARY KEY,
		first_name VARCHAR NOT NULL,
		last_name VARCHAR,
		email VARCHAR UNIQUE,
		team_rank INTEGER,
		team INTEGER REFERENCES teams(id) ON DELETE CASCADE ON UPDATE CASCADE
	)");

	$connection->execute("CREATE TABLE phone_numbers(
		id SERIAL PRIMARY KEY,
		person INTEGER NOT NULL REFERENCES people(id) ON DELETE CASCADE ON UPDATE CASCADE,
		number VARCHAR NOT NULL,
		priority INTEGER,
		UNIQUE(person, priority)
	)");

	$connection->execute("CREATE TABLE users(
		person INTEGER PRIMARY KEY REFERENCES people(id) ON DELETE CASCADE ON UPDATE CASCADE,
		password VARCHAR
	)");

	$connection->execute("CREATE TABLE groups(
		id SERIAL PRIMARY KEY,
		name VARCHAR UNIQUE,
		is_default BOOLEAN DEFAULT FALSE
	)");

	$connection->execute("CREATE TABLE people_groups(
		person INTEGER REFERENCES people(id) ON DELETE CASCADE ON UPDATE CASCADE,
		\"group\" INTEGER REFERENCES groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
		PRIMARY KEY(person, \"group\")
	)");

	register_shutdown_function(function() use ($connection) {
		$connection->execute("DROP TABLE people_groups");
		$connection->execute("DROP TABLE groups");
		$connection->execute("DROP TABLE users");
		$connection->execute("DROP TABLE phone_numbers");
		$connection->execute("DROP TABLE people");
		$connection->execute("DROP TABLE teams");
	});

	$connection->execute("INSERT INTO people (first_name, last_name, email) VALUES('Allison', NULL, NULL)");
	$connection->execute("INSERT INTO people (first_name, last_name, email) VALUES('Matthew', 'Sahagian', 'matt@imarc.net')");
	$connection->execute("INSERT INTO people (first_name, last_name, email) VALUES('Jeff', 'Turcotte', NULL)");

	$people = new People($manager);
	$person = $people->create();

	$person->setFirstName('Brand');
	$person->setLastName('New');

	echo $person->getFirstName()       . PHP_EOL;
	echo $person->getLastName()        . PHP_EOL;

	$person = $people->find(1);

	echo $person->getFirstName()       . PHP_EOL;
	echo $person->getLastName()        . PHP_EOL;

	$person = $people->find(['email' => 'matt@imarc.net', 'People.firstName' => 'Matthew']);

	echo $person->getFirstName()       . PHP_EOL;
	echo $person->getLastName()        . PHP_EOL;

	echo microtime_diff($start, microtime()). PHP_EOL;
	echo (memory_get_usage() / 1024 / 1024) . PHP_EOL;
