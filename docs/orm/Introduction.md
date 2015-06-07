# Object Relational Mapper

**PLEASE NOTE THAT THE ORM IS IN DEVELOPMENT, DOCUMENTATION FOUND HERE IS NOT NECESSARILY WORKING
FUNCTIONALITY AND MAY REPRESENT PLANNED INTERFACES AND FEATURES**

## Manager

```php
$manager    = new Redub\ORM\Manager($config);
$driver     = new Redub\Database\SQL\PDOPgsql();
$connection = new Redub\Database\Connection([
	'driver' => 'pgsql',
	'dbname' => 'redub_test'
]);

$manager->setup($driver, 'pgsql');
$manager->connect($connection);
```
