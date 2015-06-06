<?php namespace Redub\Database\SQL
{
	use Redub\Database;
	use Dotink\Flourish;
	use PDO;

	class PDOPgsql extends Driver
	{
		const PLATFORM_CLASS   = 'Redub\Database\SQL\Pgsql';
		const QUERY_CLASS      = 'Redub\Database\SQL\Query';
		const RESULT_CLASS     = 'Redub\Database\PDOResult';

		/**
		 *
		 */
		private $pdo = NULL;


		/**
		 * Enables the connection if it's not enabled
		 *
		 * @access public
		 * @param ConnectionInterface $connection The connection from which to get config settings
		 * @return boolean TRUE if the connection is enabled, FALSE otherwise
		 */
		public function connect(Database\ConnectionInterface $connection)
		{
			if (!$this->pdo) {
				try {
					$this->pdo = new PDO(
						$this->createDSN($connection),
						$connection->getConfig('user', 'postgres'),
						$connection->getConfig('pass', NULL)
					);

				} catch (PDOException $e) {
					return FALSE;
				}
			}

			return TRUE;
		}


		/**
		 *
		 */
		public function executeCount($result)
		{
			return $result
				? $result->rowCount()
				: 0;
		}


		/**
		 *
		 */
		public function executeFailure($query, $result, $message)
		{
			$error_info = $this->pdo->errorInfo();

			throw new Database\Exception(
				'%s: [%s,%s] %s',
				$message,
				$error_info[0],
				$error_info[1],
				$error_info[2]
			);
		}


		/**
		 *
		 */
		public function executeQuery($query)
		{
			$platform  = $this->getPlatform();
			$statement = $platform->compose($query);

			return $this->pdo->query($statement);
		}


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
