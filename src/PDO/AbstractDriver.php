<?php namespace Redub\Database\PDO
{
	use Redub\Database\SQL;
	use Redub\Database;
	use Dotink\Flourish;
	use PDO;

	abstract class AbstractDriver extends SQL\AbstractDriver
	{
		const DEFAULT_USER      = NULL;
		const PLACEHOLDER_START = 1;

		/**
		 * Creates a DSN from the connection
		 *
		 * @access protected
		 * @param ConnectionInterface $connection The connection from which to get DSN settings
		 * @return string The constructed DSN from connection settings
		 */
		abstract protected function createDSN(Database\ConnectionInterface $connection);


		/**
		 *
		 */
		static public function getPlaceHolder()
		{
			return '?';
		}

		/**
		 * Enables the connection if it's not enabled
		 *
		 * @access public
		 * @param ConnectionInterface $connection The connection from which to get config settings
		 * @return boolean TRUE if the connection is enabled, FALSE otherwise
		 */
		public function connect(Database\ConnectionInterface $connection, $immediate = FALSE)
		{
			if (!$connection->getConfig('name')) {
				throw new Flourish\ProgrammerException(
					'Cannot complete connection "%s", missing database name',
					$connection->getAlias()
				);
			}

			try {
				$connection->setDriver($this);

				if ($immediate) {
					$connection->setHandle(
						new PDO(
							$this->createDSN($connection),
							$connection->getConfig('user', static::DEFAULT_USER),
							$connection->getConfig('pass', NULL),
							[
								PDO::ATTR_PERSISTENT => TRUE
							]
						)
					);
				}

				return TRUE;

			} catch (PDOException $e) {
				return FALSE;
			}
		}


		/**
		 *
		 */
		public function count($handle, $result)
		{
			return $result
				? $result->rowCount()
				: 0;
		}


		/**
		 *
		 */
		public function execute($handle, $statement)
		{
			if ($statement->execute() !== FALSE) {
				return $statement;

			} else {
				$this->fail($handle, $statement, 'Could not execute query');
			}
		}


		/**
		 *
		 */
		public function fail($handle, $response, $message)
		{
			$error_info = $handle->errorInfo();

			throw new Flourish\ProgrammerException(
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
		public function prepare($handle, Database\Query $query)
		{
			$statement = $this->getPlatform()->compose($query, '?', static::PLACEHOLDER_START);
			$statement = $handle->prepare($statement);

			foreach ($query->getParams() as $index => $value) {
				if ($value === NULL) {
					$statement->bindValue($index, $value, PDO::PARAM_NULL);
				} else {
					$statement->bindValue($index, $value);
				}
			}

			return $statement;
		}


		/**
		 *
		 */
		public function resolve(Database\Query $query, $response, $count)
		{
			return new Result($response, $count);
		}
	}
}
