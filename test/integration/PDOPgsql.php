<?php

	use Dotink\Jin\Parser;
	use Dotink\Flourish;
	use Redub\Database;
	use Redub\ORM;

	include __DIR__ . '/../../vendor/autoload.php';

	/**
	 *
	 */
	function microtime_diff($start, $end = null)
	{
		if (!$end) {
			$end = microtime();
		}
		list($start_usec, $start_sec) = explode(" ", $start);
		list($end_usec, $end_sec) = explode(" ", $end);
		$diff_sec = intval($end_sec) - intval($start_sec);
		$diff_usec = floatval($end_usec) - floatval($start_usec);

		return floatval($diff_sec) + $diff_usec;
	}


	/**
	 *
	 */
	class Person extends ORM\Model
	{
		/**
		 *
		 */
		public function getFirstName()
		{
			return $this->get('firstName');
		}


		/**
		 *
		 */
		public function getLastName()
		{
			return $this->get('lastName');
		}


		/**
		 *
		 */
		public function setFirstName($value)
		{
			return $this->set('firstName', $value);
		}


		/**
		 *
		 */
		public function setLastName($value)
		{
			return $this->set('lastName', $value);
		}
	}


	$start = microtime();

	$driver	 = new Database\SQL\PDOPgsql();
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

	$connection->execute("DROP TABLE names");

	$mid = microtime();

	$config  = new ORM\Configuration\Native();

	$config->addField('Person', 'firstName', 'string');
	$config->addField('Person', 'lastName',  'string');
	$config->addField('Person', 'email',     'string');
	$config->addField('Person', 'age',       'integer');

	$parser     = new Parser(new Flourish\Collection());
	$config     = new ORM\Configuration\Jin(__DIR__ . '/config', $parser);
	$manager    = new ORM\Manager($config);

/*
	$flourish = new ORM\Convention\Flourish();

	$manager->addConvention($flourish);
*/


	$person = $manager->create('Person');
	$person->setFirstName('Matthew');
	$person->setLastName('Sahagian');


	$end = microtime();

	echo $person->getFirstName() . PHP_EOL;
	echo $person->getLastName()  . PHP_EOL;
