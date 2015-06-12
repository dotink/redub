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
			'>=' => '>= %s'
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
					$columns[] = $this->escapeIdentifier($value, $query->isPrepared());

				} else {
					$columns[] = sprintf(
						'%s as %s',
						$this->escapeIdentifier($key, $query->isPrepared()),
						$this->escapeIdentifier($value, $query->isPrepared())
					);
				}
			}

			return implode(', ', $columns);
		}


		/**
		 *
		 */
		protected function composeCriteria($query, $placeholder)
		{
			$criterias  = $query->getCriteria();
			$conditions = '';

			foreach ($criterias as $index => $criteria) {
				$parts = [];

				if (in_array($criteria, ['OR', 'AND'])) {
					$conditions .= ' ' . $criteria . ' ';

					continue;
				}

				foreach ($criteria as $condition) {
					$parts[] = $this->makeCondition($query, $placeholder, $condition);
				}

				$conditions .= sprintf('(%s)',
					implode(' ' . $query->getGlue($index) . ' ', $parts)
				);
			}

			return $conditions;
		}


		/**
		 *
		 */
		protected function composeJoins($query, $placeholder)
		{

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
				$table = $this->escapeIdentifier(
					$repository,
					$query->isPrepared()
				);

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
				$this->escapeIdentifier($condition, $query->isPrepared()),
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
				$value = $this->escapeIdentifier($value, $query->isPrepared());

			} else {
				if (strpos($operator, '~') !== FALSE) {
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
				$this->escapeIdentifier($key, $query->isPrepared()),
				$this->escapeIdentifier($value, $query->isPrepared())
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
			$query->addParam($value, $this->placeholderIndex);

			return sprintf($placeholder, $this->placeholderIndex++);
		}
	}
}
