<?php namespace Redub\Database\PDO
{
	use Redub\Database;

	/**
	 *
	 */
	class Mysql extends AbstractDriver
	{
		const PLATFORM_CLASS = 'Redub\Database\SQL\Mysql';
		const DEFAULT_USER   = 'root';

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
				'mysql:host=%s;port=%s;dbname=%s',
				$connection->getConfig('host', 'localhost'),
				$connection->getConfig('port', 3306),
				$connection->getConfig('dbname')
			);
		}
	}
}
