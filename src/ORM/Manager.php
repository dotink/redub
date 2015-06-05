<?php namespace Redub\ORM
{
	use Dotink\Flourish;
	use Redub\Database;

	class Manager
	{

		protected $connections = array();


		protected $drivers = array();


		/**
		 *
		 */
		public function setup(Database\DriverInterface $driver, $alias)
		{
			$this->drivers[$alias] = $driver;
		}


		/**
		 *
		 */
		public function connect(Database\ConnectionInterface $connection, $namespace)
		{
			$driver_alias  = $connection->getDriverAlias();
			$connection_ns = trim($namespace, '\\');

			if (!in_array($connection, $this->connections)) {
				if (isset($this->drivers[$driver_alias])) {
					$connection->setDriver($this->drivers[$driver_alias]);

				} else {
					throw new Flourish\ProgrammerException(
						'No valid driver could be found for the connection, driver "%s"',
						$driver_alias
					);
				}
			}

			$this->connections[$connection_ns] = $connection;
		}
	}
}
