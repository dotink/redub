<?php namespace Redub\Database\SQL
{
	use Redub\Database;
	use Dotink\Flourish;

	/**
	 *
	 */
	abstract class AbstractPlatform implements Database\PlatformInterface
	{
		const PLATFORM_NAME = 'SQL';
		const WILDCARD      = '%';


		/**
		 *
		 */
		static protected $extendedActions = array();


		/**
		 *
		 */
		static protected $supportedOperators = [
			'=:'     => '= %s',
			'!:'     => '!= %s',
			'>:'     => '> %s',
			'<:'     => '< %s',
			'=='     => '= %s',
			'!='     => '!= %s',
			'~~'     => 'LIKE %s',
			'~='     => 'LIKE %s',
			'=~'     => 'LIKE %s',
			'!!'     => 'NOT LIKE %s',
			'~!'     => 'NOT LIKE %s',
			'!~'     => 'NOT LIKE %s',
			'<-'     => '< %s',
			'>-'     => '> %s',
			'<='     => '<= %s',
			'>='     => '>= %s',
			'IN'     => 'IN(%s)',
			'NOT IN' => 'NOT IN(%s)'
		];


		/**
		 *
		 */
		static public function escapeIdentifier($name, $prepared = FALSE)
		{
			return $name;
		}


		/**
		 *
		 */
		static public function getPlatformName()
		{
			return static::PLATFORM_NAME;
		}


		/**
		 * Compile an executable statement for a driver
		 *
		 * @access public
		 * @param Query $query The query to compile
		 * @return mixed The executable statement for a driver using this platform
		 */
		public function compile(Database\Query $query, Database\DriverInterface $driver)
		{
			if ($query->isCompiled()) {
				return $query->getStatement();
			}

			$driver->reset();

			switch ($action = $query->getAction()) {
				case 'select':
					return $this->compileSelect($query, $driver);

				case 'delete':
					return $this->compileDelete($query, $driver);

				case 'update':
					return $this->compileUpdate($query, $driver);

				case 'insert':
					return $this->compileInsert($query, $driver);

				default:

					if (!isset(static::$extendedActions[$action])) {
						throw new Flourish\ProgrammerException(
							'Cannot compile query with unsupported action "%s"',
							$action
						);
					}

					return static::$extendedActions[$action]($query, $driver);
			}
		}


		/**
		 * Parse a query's statement an populate the query object
		 *
		 * This method should return a new query object, not the original.
		 *
		 * @access public
		 * @param Query $query The query to parse
		 * @return Query The parsed and populated query object
		 */
		public function parse(Database\Query $query)
		{
			throw new Flourish\UnexpectedException(
				'Parsing is not yet implemented on "%s"',
				get_class($this)
			);
		}


		/**
		 *
		 */
		protected function compileSelectColumns($query, $driver)
		{
			$columns = array();

			if (!count($query->getArguments())) {
				return '*';
			}

			foreach ($query->getArguments() as $key => $value) {
				if (!is_string($value)) {
					throw new Flourish\ProgrammerException(
						'Cannot compile query with columns of non-string values'
					);
				}

				if (is_int($key)) {
					$columns[] = $driver->escapeIdentifier($value);

				} else {
					$columns[] = sprintf(
						'%s as %s',
						$driver->escapeIdentifier($key),
						$driver->escapeIdentifier($value)
					);
				}
			}

			return implode(', ', $columns);
		}


		/**
		 *
		 */
		protected function compileCriteria($query, $driver, $criteria = NULL)
		{
			$conditions = '';

			if (!$criteria) {
				$criteria = $query->getCriteria();

				if (count($criteria) == 1) {
					$criteria = reset($criteria);
				}
			}

			foreach ($criteria as $condition) {
				if (is_array($condition)) {
					if (count($condition) == 1) {
						$condition = $condition[0];
					}

					if (in_array($condition[1], ['and', 'or'])) {
						$conditions .= sprintf('(%s)', $this->compileCriteria(
							$query,
							$driver,
							$condition
						));

					} elseif (isset(static::$supportedOperators[$condition[1]])) {
						$conditions .= $this->makeCondition($query, $driver, $condition);

					} else {
						throw new Flourish\ProgrammerException(
							'Unsupported operator "%s" used in query',
							$condition[1]
						);
					}


				} elseif ($condition == 'and') {
					$conditions .= ' AND ';
				} elseif ($condition == 'or') {
					$conditions .= ' OR ';

				} else {
					throw new Flourish\ProgrammerException(
						'Invalid glue operator "%s" in query',
						$condition
					);
				}
			}

			return $conditions;
		}


		/**
		 *
		 */
		protected function compileDelete($query, $driver)
		{
			return sprintf(
				"DELETE %s %s",
				$this->compileDeleteFrom($query, $driver),
				$this->compileWhere($query, $driver) ?: "WHERE true"
			);
		}


		/**
		 *
		 */
		protected function compileDeleteFrom($query, $driver)
		{
			$repository = $query->getRepository();

			if (!is_array($repository)) {
				$table = $driver->escapeIdentifier($repository);

			} else {
				throw new Flourish\ProgrammerException(
					'Cannot compile table in FROM clause with malformed repository value',
					$repository
				);
			}

			return sprintf("FROM %s", $table);
		}


		/**
		 *
		 */
		protected function compileInsert($query, $driver)
		{
			return sprintf(
				"INSERT %s (%s) %s",
				$this->compileInsertInto($query, $driver),
				$this->compileInsertColumns($query, $driver),
				$this->compileInsertValues($query, $driver)
			);
		}


		/**
		 *
		 */
		protected function compileInsertColumns($query, $driver)
		{
			$columns = array();

			foreach (array_keys($query->getArguments()) as $column) {
				if (!is_string($column)) {
					throw new Flourish\ProgrammerException(
						'Cannot compile query with columns of non-string values'
					);
				}

				$columns[] = $driver->escapeIdentifier($column);
			}

			return implode(', ', $columns);
		}


		/**
		 *
		 */
		protected function compileInsertInto($query, $driver)
		{
			$repository = $query->getRepository();

			if (is_string($repository) && trim($repository)) {
				$table = $driver->escapeIdentifier($repository);

			} else {
				throw new Flourish\ProgrammerException(
					'Cannot compile query with non-string or empty repository'
				);
			}

			return sprintf("INTO %s", $table);
		}


		/**
		 *
		 */
		protected function compileInsertValues($query, $driver)
		{
			$values = array();

			foreach ($query->getArguments() as $value) {
				$values[] = $driver->escapeValue($value, $query);
			}

			return sprintf("VALUES(%s)", implode(',', $values));
		}


		/**
		 *
		 */
		protected function compileJoins($query, $driver)
		{
			$links = $query->getLinks();
			$joins = '';
			$alias = '';

			foreach ($query->getLinks() as $join_table => $criteria) {
				$parts  = array();

				if (is_string($criteria[0])) {
						$alias = array_shift($criteria);
				}

				if (!count($criteria)) {
					throw new Flourish\ProgrammerException(
						'Criteria must exist in order to perform join on "%s"',
						$join_table
					);
				}

				foreach ($criteria as $condition) {
					$parts[] = $this->makeCondition($query, $driver, $condition);
				}

				$joins .= sprintf(' JOIN %s %s ON (%s)',
					$driver->escapeIdentifier($join_table),
					$driver->escapeIdentifier($alias),
					implode(' AND ', $parts)
				);
			}

			return $joins . ' ';
		}


		/**
		 *
		 * @access protected
		 * @param Query $query The query from which to compile the WHERE clause
		 * @param string $driver The placeholder to use for prepared statements
		 * @return string
		 */
		protected function compileLimit($query, $driver)
		{
			$limit = $query->getLimit();

			if ($limit === NULL) {
				return NULL;

			} elseif (!is_int($limit) || $limit < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot compile query with non-integer or negative LIMIT clause'
				);

			}

			return sprintf('LIMIT %s', $this->makeValue($query, $driver, $limit));
		}


		/**
		 *
		 * @access protected
		 * @param Query $query The query from which to compile the WHERE clause
		 * @param string $driver The placeholder to use for prepared statements
		 * @return string
		 */
		protected function compileOffset($query, $driver)
		{
			$offset = $query->getOffset();

			if ($offset === NULL) {
				return NULL;

			} elseif (!is_int($offset) || $offset < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot compile query with non-integer or negative OFFSET clause'
				);

			}

			return sprintf('OFFSET %s', $this->makeValue($query, $driver, $offset));
		}


		/**
		 *
		 *
		 * @access protected
		 * @param Query $query The query from which to compile the WHERE clause
		 * @param string $driver The placeholder to use for prepared statements
		 * @return string
		 */
		protected function compileFrom($query, $driver)
		{
			$action = $query->getAction();

			switch ($action) {
				case 'select':
					return $this->compileSelectFrom($query, $driver);

				case 'delete':
					return $this->compileDeleteFrom($query, $driver);

				default:
					throw new Flourish\ProgrammerException(
						'Cannot compile FROM clause on unsupported action "%s"',
						$action
					);
			}
		}


		/**
		 * Compiles a query that is performing a select
		 *
		 * @access protected
		 * @param Query $query The query from which to compile the WHERE clause
		 * @param string $driver The placeholder to use for prepared statements
		 * @return string
		 */
		protected function compileSelect($query, $driver)
		{
			return trim(sprintf(
				'SELECT %s %s %s %s %s',
				$this->compileSelectColumns ($query, $driver),
				$this->compileFrom    ($query, $driver),
				$this->compileWhere   ($query, $driver),
				$this->compileLimit   ($query, $driver),
				$this->compileOffset  ($query, $driver)
			));
		}


		/**
		 *
		 */
		protected function compileSelectFrom($query, $driver)
		{
			$repository = $query->getRepository();

			if (!is_array($repository)) {
				$table = $driver->escapeIdentifier($repository);

			} elseif (!is_numeric(key($repository))) {
				$table = $this->makeTableWithAlias(
					$query,
					$driver,
					key($repository),
					current($repository)
				);

			} else {
				throw new Flourish\ProgrammerException(
					'Cannot compile tables in FROM clause with malformed repository value',
					$repository
				);
			}

			return sprintf('FROM %s %s', $table, $this->compileJoins($query, $driver));
		}


		/**
		 *
		 */
		protected function compileUpdate($query, $driver)
		{
			$repository = $query->getRepository();

			if (!is_array($repository)) {
				$table = $driver->escapeIdentifier($repository);

			} else {
				throw new Flourish\ProgrammerException(
					'Cannot compile table in UPDATE clause with malformed repository value',
					$repository
				);
			}

			return sprintf(
				"UPDATE %s %s %s",
				$table,
				$this->compileUpdateSet($query, $driver),
				$this->compileWhere($query, $driver)
			);
		}


		/**
		 *
		 */
		protected function compileUpdateSet($query, $driver)
		{
			$assignments = array();

			foreach ($query->getArguments() as $column => $value) {
				if (is_int($column)) {
					throw new Flourish\ProgrammerException(
						'Cannot compile SET clause, invalid assignment to numeric column'
					);
				}

				$assignments[] = sprintf(
					"%s = %s",
					$driver->escapeIdentifier($column),
					$driver->escapeValue($value, $query)
				);
			}

			return sprintf("SET %s", implode(", ", $assignments));
		}


		/**
		 * Compiles an SQL WHERE clause if it is needed by the query
		 *
		 * @access protected
		 * @param Query $query The query from which to compile the WHERE clause
		 * @param string $driver The placeholder to use for prepared statements
		 * @return string The SQL WHERE clause
		 */
		protected function compileWhere($query, $driver)
		{
			return count($query->getCriteria())
				? sprintf('WHERE %s', $this->compileCriteria($query, $driver))
				: NULL;
		}


		/**
		 *
		 */
		protected function makeCondition($query, $driver, $condition)
		{
			list($field, $operator, $value) = $condition;

			if (!isset(self::$supportedOperators[$operator])) {
				throw new Flourish\ProgrammerException(
					'Cannot compile query with operator "%s", unsupported by this platform',
					$operator
				);
			}

			$identifier = $driver->escapeIdentifier($field);

			if ($value === NULL) {
				$value = $driver->escapeValue($value, $query);

				if ($operator == '!=') {
					$condition = sprintf('%s IS NOT %s', $identifier, $value);
				} elseif ($operator == '==') {
					$condition = sprintf('%s IS %s', $identifier, $value);
				} else {
					// TODO: Implement other NULL cases
				}

			} else {
				$operator  = $this->makeOperator($query, $driver, $operator, $value);
				$condition = sprintf('%s %s', $identifier, $operator);

				if (strpos($operator, '!') !== FALSE) {
					$condition = sprintf('(%s OR %s IS NULL)', $condition, $identifier);
				}
			}

			return $condition;
		}


		/**
		 * Makes an operator for comparing a particular value
		 *
		 *
		 *
		 */
		protected function makeOperator($query, $driver, $operator, $value)
		{
			$operator = trim($operator);

			if (!isset(static::$supportedOperators[$operator])) {
				throw new Flourish\ProgrammerException(
					'Cannot compile query with unsupported operator "%s"',
					$operator
				);
			}

			if ($operator[1] == ':') {
				$value = $driver->escapeIdentifier($value);

			} else {
				if (is_array($value)) {
					if ($operator == '==') {
						$operator = 'IN';
					} elseif ($operator == '!=') {
						$operator = 'NOT IN';

					} else {
						throw new Flourish\ProgrammerException(
							'Invalid operator "%s" for use with array value, must be == or !=',
							$operator
						);
					}

				} else {
					if (strpos($operator, '~') !== FALSE) {
						$value = $operator[0] == '~' ? (static::WILDCARD . $value) : $value;
						$value = $operator[1] == '~' ? ($value . static::WILDCARD) : $value;
					}
				}

				$value = $this->makeValue($query, $driver, $value);
			}

			return sprintf(static::$supportedOperators[$operator], $value);
		}


		/**
		 *
		 */
		public function makeTableWithAlias($query, $driver, $key, $value)
		{
			return sprintf(
				'%s %s',
				$driver->escapeIdentifier($key),
				$driver->escapeIdentifier($value)
			);
		}


		/**
		 * Makes a placeholder for a prepared query
		 *
		 * @access protected
		 * @param string $driver The placeholder to compile with, %d is replaced by index
		 * @return string
		 */
		protected function makeValue($query, $driver, $value)
		{
			settype($value, 'array');

			$params = array();

			foreach ($value as $param) {
				$params[] = $driver->escapeValue($param, $query);
			}

			return implode(', ', $params);
		}
	}
}
