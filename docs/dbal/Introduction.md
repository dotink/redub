# Introduction to Database Abstraction Layer

## Basic Usage

```php
$driver     = new Redub\Database\SQL\PDOPgsql();
$connection = new Redub\Database\Connection([
	'driver' => 'pgsql',
	'dbname' => 'redub_test'
], $driver);

$connection->execute("CREATE TABLE names(
	id SERIAL PRIMARY KEY,
	name VARCHAR NOT NULL
)");

$connection->execute("INSERT INTO names (name) VALUES('Matthew')");
$connection->execute("INSERT INTO names (name) VALUES('Allison')");
$connection->execute("INSERT INTO names (name) VALUES('Jeffrey')");

$result = $connection->execute('SELECT * FROM names LIMIT 2 OFFSET 1');

foreach ($result as $row) {
	var_dump($row);
}


$connection->execute("DROP TABLE names");
```
