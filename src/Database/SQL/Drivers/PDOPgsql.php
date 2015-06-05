<?php namespace Redub\Database\SQL
{
	use Redub\Database;
	use Dotink\Flourish;
	use PDO;

	class PDOPgsql extends Driver
	{
		const RESULT_CLASS     = 'Redub\Database\SQL\PDOResult';
		const QUERY_CLASS      = 'Redub\Database\SQL\Query';

		/**
		 *
		 */
		private $pdo = NULL;


		/**
		 *
		 */
		public function run(Database\ConnectionInterface $connection, $query)
		{
			if (!$this->enableConnection($connection)) {
				throw new Exception(
					'Unable to connect to database with DSN "%s"',
					$this->createDSN($connection)
				);
			}

			$query_class  = self::QUERY_CLASS;
			$result_class = self::RESULT_CLASS;
			$query        = new $query_class($query, $this, FALSE);

			if (!($result = $this->runQuery($query))) {
				$this->throwFailure('Could not execute query');
			}

			$limited_count   = $result->rowCount();
			$unlimited_count = $query->checkAction('SELECT') && $query->checkLimit()
				? $this->countAll($query)
				: $limited_count;

			return new $result_class($result, $limited_count, $unlimited_count);
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
				$connection->getConfig('name')
			);
		}


		/**
		 * Enables the connection if it's not enabled
		 *
		 * @access protected
		 * @param ConnectionInterface $connection The connection from which to get config settings
		 * @return boolean TRUE if the connection is enabled, FALSE otherwise
		 */
		protected function enableConnection(Database\ConnectionInterface $connection)
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
		protected function countAll($query)
		{
			$count_query = clone $query;

			$count_query->setSelect('count(' . $query->getSelect(0) . ')');
			$count_query->setLimit(NULL);

			if (!($count_result = $this->runQuery($count_query))) {
				$this->throwFailure('Failed to get non-limited count');
			}

			return $count_result->fetchColumn(0);
		}


		/**
		 *
		 */
		protected function runQuery(Query $query)
		{
			return $this->pdo->query($query->compose());
		}


		/**
		 *
		 */
		protected function throwFailure($message, $class = __NAMESPACE__ . '\DatabaseException')
		{
			$error_info = $this->pdo->errorInfo();

			throw new $class(
				'%s: [%s,%s] %s',
				$message,
				$error_info[0],
				$error_info[1],
				$error_info[2]
			);
		}

	}
}
