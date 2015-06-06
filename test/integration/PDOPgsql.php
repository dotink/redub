<?php

	use Redub\Database;

	include __DIR__ . '/../../vendor/autoload.php';

	$driver     = new Database\SQL\PDOPgsql();
	$connection = new Database\Connection([
		'driver' => 'pgsql',
		'dbname' => 'redub_test',
	], $driver);

	$connection->execute("CREATE TABLE names(
		id SERIAL PRIMARY KEY,
		name VARCHAR NOT NULL
	)");

	$connection->execute("INSERT INTO names (name) VALUES('Matthew')");
	$connection->execute("INSERT INTO names (name) VALUES('Allison')");
	$connection->execute("INSERT INTO names (name) VALUES('Jeffrey')");

	$result = $connection->execute('SELECT * FROM names');

	foreach ($result as $row) {
		var_dump($row);
	}


	$connection->execute("DROP TABLE names");
