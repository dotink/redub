<?php

	use Redub\Database;

	include __DIR__ . '/../../vendor/autoload.php';

	$driver     = new Database\SQL\PDOPgsql();
	$connection = new Database\Connection('pgsql', [
		'name' => 'redub_test'
	]);

	$connection->setDriver($driver);

	$connection->query("CREATE TABLE names(
		id SERIAL PRIMARY KEY,
		name VARCHAR NOT NULL
	)");

	$connection->query("INSERT INTO names (name) VALUES('Matthew')");
	$connection->query("INSERT INTO names (name) VALUES('Allison')");
	$connection->query("INSERT INTO names (name) VALUES('Jeffrey')");

	$result = $connection->query('SELECT * FROM names');

	foreach ($result as $row) {
		var_dump($row);
	}


	$connection->query("DROP TABLE names");
