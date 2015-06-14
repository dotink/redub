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
			'=:' => '= %s',
			'!:' => '!= %s',
			'>:' => '> %s',
			'<:' => '< %s',
			'==' => '= %s',
			'!=' => '!= %s',
			'~~' => 'LIKE %s',
			'~=' => 'LIKE %s',
			'=~' => 'LIKE %s',
			'!!' => 'NOT LIKE %s',
			'~!' => 'NOT LIKE %s',
			'!~' => 'NOT LIKE %s',
			'<-' => '< %s',
			'>-' => '> %s',
			'<=' => '<= %s',
			'>=' => '>= %s',
			'IN' => 'IN(%s)'
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
		 *
		 */
		protected $placeholderIndex = NULL;


		/**
		 * Compose an executable statement for a driver
		 *
		 * @access public
		 * @param Query $query The query to compose
		 * @return mixed The executable statement for a driver using this platform
		 */
		public function compose(Database\Query $query, $placeholder = '$%d', $start = 1)
		{
			if ($query->get()) {
				return $query->get();
			}

			$this->placeholderIndex = $start;

			switch ($action = $query->getAction()) {
				case 'select':
					return $this->composeSelect($query, $placeholder);

				case 'delete':
					return $this->composeDelete($query, $placeholder);

				case 'update':
					return $this->composeUpdate($query, $placeholder);

				case 'insert':
					return $this->composeInsert($query, $placeholder);

				default:

					if (!isset(static::$extendedActions[$action])) {
						throw new Flourish\ProgrammerException(
							'Cannot compose query with unsupported action "%s"',
							$action
						);
					}

					return static::$extendedActions[$action]($query, $placeholder);
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
		protected function composeColumns($query, $placeholder)
		{
			$columns = array();

			foreach ($query->getArguments() as $key => $value) {
				if (!is_string($value)) {
					throw new Flourish\ProgrammerException(
						'Cannot compose query with columns of non-string values'
					);
				}

				if (is_int($key)) {
					$columns[] = $this->escapeIdentifier($value);

				} else {
					$columns[] = sprintf(
						'%s as %s',
						$this->escapeIdentifier($key),
						$this->escapeIdentifier($value)
					);
				}
			}

			return implode(', ', $columns);
		}


		/**
		 *
		 */
		protected function composeCriteria($query, $placeholder, $criteria = NULL)
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
						$conditions .= sprintf('(%s)', $this->composeCriteria(
							$query,
							$placeholder,
							$condition
						));

					} elseif (isset(static::$supportedOperators[$condition[1]])) {
						$conditions .= $this->makeCondition($query, $placeholder, $condition);

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
		protected function composeJoins($query, $placeholder)
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
					$parts[] = $this->makeCondition($query, $placeholder, $condition);
				}

				$joins .= sprintf(' JOIN %s %s ON (%s)',
					$this->escapeIdentifier($join_table),
					$this->escapeIdentifier($alias),
					implode(' AND ', $parts)
				);
			}

			return $joins . ' ';
		}


		/**
		 *
		 * @access protected
		 * @param Query $query The query from which to compose the WHERE clause
		 * @param string $placeholder The placeholder to use for prepared statements
		 * @return string
		 */
		protected function composeLimit($query, $placeholder)
		{
			$limit = $query->getLimit();

			if ($limit === NULL) {
				return NULL;

			} elseif (!is_int($limit) || $limit < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot compose query with non-integer or negative LIMIT clause'
				);

			}

			return sprintf('LIMIT %s', $this->makePlaceholder($query, $placeholder, $limit));
		}


		/**
		 *
		 * @access protected
		 * @param Query $query The query from which to compose the WHERE clause
		 * @param string $placeholder The placeholder to use for prepared statements
		 * @return string
		 */
		protected function composeOffset($query, $placeholder)
		{
			$offset = $query->getOffset();

			if ($offset === NULL) {
				return NULL;

			} elseif (!is_int($offset) || $offset < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot compose query with non-integer or negative OFFSET clause'
				);

			}

			return sprintf('OFFSET %s', $this->makePlaceholder($query, $placeholder, $offset));
		}

		/**
		 *
		 */
		protected function composeDeleteFrom($query, $placeholder)
		{
			//
			// TODO: Implement the simplified FROM
			//
			return NULL;
		}


		/**
		 *
		 */
		protected function composeSelectFrom($query, $placeholder)
		{
			$repository = $query->getRepository();

			if (!is_array($repository)) {
				$table = $this->escapeIdentifier($repository);

			} elseif (!is_numeric(key($repository))) {
				$table = $this->makeTableWithAlias(
					$query,
					$placeholder,
					key($repository),
					current($repository)
				);

			} else {
				throw new Flourish\ProgrammerException(
					'Cannot compose tables in FROM clause with malformed repository value',
					$repository
				);
			}

			return sprintf('FROM %s %s', $table, $this->composeJoins($query, $placeholder));
		}

		/**
		 *
		 *
		 * @access protected
		 * @param Query $query The query from which to compose the WHERE clause
		 * @param string $placeholder The placeholder to use for prepared statements
		 * @return string
		 */
		protected function composeFrom($query, $placeholder)
		{
			$action = $query->getAction();

			switch ($action) {
				case 'select':
					return $this->composeSelectFrom($query, $placeholder);

				case 'delete':
					return $this->composeDeleteFrom($query, $placeholder);

				default:
					throw new Flourish\ProgrammerException(
						'Cannot compose FROM clause on unsupported action "%s"',
						$action
					);
			}
		}


		/**
		 * Composes a query that is performing a select
		 *
		 * @access protected
		 * @param Query $query The query from which to compose the WHERE clause
		 * @param string $placeholder The placeholder to use for prepared statements
		 * @return string
		 */
		protected function composeSelect($query, $placeholder)
		{
			return trim(sprintf(
				'SELECT %s %s %s %s %s',
				$this->composeColumns ($query, $placeholder),
				$this->composeFrom    ($query, $placeholder),
				$this->composeWhere   ($query, $placeholder),
				$this->composeLimit   ($query, $placeholder),
				$this->composeOffset  ($query, $placeholder)
			));
		}


		/**
		 * Composes an SQL WHERE clause if it is needed by the query
		 *
		 * @access protected
		 * @param Query $query The query from which to compose the WHERE clause
		 * @param string $placeholder The placeholder to use for prepared statements
		 * @return string The SQL WHERE clause
		 */
		protected function composeWhere($query, $placeholder)
		{
			return count($query->getCriteria())
				? sprintf('WHERE %s', $this->composeCriteria($query, $placeholder))
				: NULL;
		}


		/**
		 *
		 */
		protected function makeCondition($query, $placeholder, $condition)
		{
			list($condition, $operator, $value) = $condition;

			if (!isset(self::$supportedOperators[$operator])) {
				throw new Flourish\ProgrammerException(
					'Cannot compose query with operator "%s", unsupported by this platform',
					$operator
				);
			}

			return sprintf(
				'%s %s',
				$this->escapeIdentifier($condition),
				$this->makeOperator($query, $placeholder, $operator, $value)
			);
		}


		/**
		 * Makes an operator for comparing a particular value
		 *
		 *
		 *
		 */
		protected function makeOperator($query, $placeholder, $operator, $value)
		{
			$operator = trim($operator);

			if (!isset(static::$supportedOperators[$operator])) {
				throw new Flourish\ProgrammerException(
					'Cannot compose query with unsupported operator "%s"',
					$operator
				);
			}

			if ($operator[1] == ':') {
				$value = $this->escapeIdentifier($value);

			} else {
				if (is_array($value)) {
					if ($operator == '==') {
						$operator = 'IN';
					} else {
						throw new Flourish\ProgrammerException();
					}

				} elseif (strpos($operator, '~') !== FALSE) {
					$value = $operator[0] == '~' ? (static::WILDCARD . $value) : $value;
					$value = $operator[1] == '~' ? ($value . static::WILDCARD) : $value;
				}

				$value = $this->makePlaceholder($query, $placeholder, $value);
			}

			return sprintf(static::$supportedOperators[$operator], $value);
		}


		/**
		 *
		 */
		public function makeTableWithAlias($query, $placeholder, $key, $value)
		{
			return sprintf(
				'%s %s',
				$this->escapeIdentifier($key),
				$this->escapeIdentifier($value)
			);
		}


		/**
		 * Makes a placeholder for a prepared query
		 *
		 * @access protected
		 * @param string $placeholder The placeholder to compose with, %d is replaced by index
		 * @return string
		 */
		protected function makePlaceholder($query, $placeholder, $value)
		{
			settype($value, 'array');

			$params = array();

			foreach ($value as $param) {
				$params[] = sprintf($placeholder, $this->placeholderIndex);

				$query->using($param, $this->placeholderIndex);

				$this->placeholderIndex++;
			}

			return implode(', ', $params);
		}
	}
}
