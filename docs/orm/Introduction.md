# Object Relational Mapper

**PLEASE NOTE THAT THE ORM IS IN DEVELOPMENT, DOCUMENTATION FOUND HERE IS NOT NECESSARILY WORKING
FUNCTIONALITY AND MAY REPRESENT PLANNED INTERFACES AND FEATURES**

## Manager

```php
$config     = new Redub\ORM\Configuration\Jin($path_to_configs);
$manager    = new Redub\ORM\Manager($config, /* optional cache */);
$driver     = new Redub\Database\SQL\PDOPgsql();
$mapper     = new Redub\ORM\SQL\Mapper();

$manager->bind('pgsql', $database, $mapper);
$manager->connect(new Redub\Database\Connection('default', [
	'dbname'  => 'redub_test',
	'binding' => 'pgsql'
]));
