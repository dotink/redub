<?php namespace Redub\Database\PDO
{
	use Redub\Database;

	/**
	 *
	 */
	class Pgsql extends AbstractDriver
	{
		const PLACEHOLDER_START = 1;
		const PLATFORM_CLASS    = 'Redub\Database\SQL\Pgsql';
		const DEFAULT_USER      = 'postgres';


		/**
		 * Creates a DSN from the connection
		 *
		 * @access protected
		 * @param ConnectionInterface $connection The connection from which to get DSN settings
		 * @return string The constructed DSN from connection settings
		 */
		protected function createDSN(Database\ConnectionInterface $connection)
		{
			return sprintf(
				'pgsql:host=%s;port=%s;dbname=%s',
				$connection->getConfig('host', 'localhost'),
				$connection->getConfig('port', 5432),
				$connection->getConfig('dbname')
			);
		}
	}
}
