<?php namespace Redub\Database
{
	use Dotink\Flourish;

	/**
	 * An object encapsulation of query information, arguments, parameters, etc.
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	class Query extends Criteria
	{
		/**
		 *
		 */
		protected $action = NULL;


		/**
		 *
		 */
		protected $arguments = array();


		/**
		 *
		 */
		protected $criteria = array();


		/**
		 *
		 */
		protected $limit = NULL;


		/**
		 *
		 */
		protected $links = array();


		/**
		 *
		 */
		protected $offset = NULL;


		/**
		 *
		 */
		protected $params = array();


		/**
		 *
		 */
		protected $repository = NULL;


		/**
		 *
		 */
		protected $statement = NULL;


		/**
		 *
		 */
		public function __construct($statement = NULL, array $params = array())
		{
			$this->statement = $statement;

			if (func_num_args() >= 2) {
				$this->params = $params;
			}
		}


		/**
		 *
		 */
		public function __clone()
		{
			$this->statement = NULL;
			$this->params    = array();
		}


		/**
		 *
		 */
		public function bindStatement($statement)
		{
			$this->statement = $statement;

			return $this;
		}


		/**
		 *
		 */
		public function getAction()
		{
			return $this->action;
		}


		/**
		 *
		 */
		public function getArguments()
		{
			return $this->arguments;
		}


		/**
		 *
		 */
		public function getCriteria($glue = TRUE)
		{
			return $glue
				? $this->glue($this->criteria)
				: $this->criteria;

			return $criteria;
		}


		/**
		 *
		 */
		public function getLimit()
		{
			return $this->limit;
		}


		/**
		 *
		 */
		public function getLinks()
		{
			return $this->links;
		}


		/**
		 *
		 */
		public function getOffset()
		{
			return $this->offset;
		}


		/**
		 *
		 */
		public function getParams()
		{
			return $this->params;
		}


		/**
		 *
		 */
		public function getRepository()
		{
			return $this->repository;
		}


		/**
		 *
		 */
		public function getStatement()
		{
			return $this->statement;
		}


		/**
		 *
		 */
		public function isCompiled()
		{
			return $this->statement !== NULL;
		}


		/**
		 *
		 */
		public function link($repository, array $conditions)
		{
			!$this->isCompiled() ?: $this->reject();

			$alias    = FALSE;
			$criteria = array();

			foreach ($conditions as $condition => $value) {
				if (is_numeric($condition)) {
					if ($alias) {
						throw new Flourish\ProgrammerException(
							'Invalid link format, "%s" is an alias, but one already exists',
							$value
						);
					}

					$alias = $value;
				} else {
					$criteria[] = $this->split($condition, $value);
				}
			}

			array_unshift($criteria, $alias);

			$this->links[$repository] = $criteria;

			return $this;
		}


		/**
		 *
		 */
		public function limit($count)
		{
			!$this->isCompiled() ?: $this->reject();

			$this->limit = $count;

			return $this;
		}


		/**
		 *
		 */
		public function on($repository, array $links = array())
		{
			!$this->isCompiled() ?: $this->reject();

			$this->repository = $repository;

			if (func_num_args() == 2) {
				foreach ($links as $repository => $conditions) {
					$this->link($repository, $conditions);
				}
			}

			return $this;
		}


		/**
		 *
		 */
		public function perform($action, array $args = array())
		{
			!$this->isCompiled() ?: $this->reject();

			$this->action = $action;

			if (func_num_args() == 2) {
				$this->arguments = $args;
			}

			return $this;
		}


		/**
		 *
		 */
		public function skip($offset)
		{
			!$this->isCompiled() ?: $this->reject();

			$this->offset = $offset;

			return $this;
		}


		/**
		 *
		 */
		public function using($params, $placeholderIndex = NULL)
		{
			!$this->isCompiled() ?: $this->reject();

			if ($placeholderIndex == NULL) {
				if (!is_array($params)) {
					throw new Flourish\ProgrammerException(
						'If no placeholder index is specified, parameters must be an array'
					);
				}

				$this->params = $params;

			} else {
				$this->params[$placeholderIndex] = $params;
			}

			return $this;
		}


		/**
		 *
		 */
		public function where($conditions, $preformatted = FALSE)
		{
			!$this->isCompiled() ?: $this->reject();

			if ($preformatted) {
				$this->criteria = $conditions;

			} else {
				if ($conditions instanceof Criteria) {
					if (count($conditions->arguments)) {
						$this->arguments = $conditions->arguments;
					}

					$conditions = ['all' => $conditions->criteria];

				} else {
					if (is_array($conditions)) {
						if (!in_array(key($conditions), ['any', 'all'])) {
							$conditions = ['all' => $conditions];
						}

					} else {
						throw new Flourish\ProgrammerException(
							'Invalid criteria specified'
						);
					}
				}

				$this->criteria = $this->split($conditions);
			}

			return $this;
		}


		/**
		 *
		 */
		public function with(array $arguments = array())
		{
			!$this->isCompiled() ?: $this->reject();

			$this->arguments = $arguments;

			return $this;
		}


		/**
		 *
		 */
		protected function glue($conditions, $glue = NULL)
		{
			$criteria = array();
			$count    = count($conditions);
			$x        = 1;

			foreach ($conditions as $condition => $value) {
				if (!is_numeric($condition)) {
					$criteria[] = $condition != 'any'
						? $this->glue($value, 'and')
						: $this->glue($value, 'or');

				} else {
					$criteria[] = count($value) != 3
						? $this->glue($value)
						: $value;

					if ($x < $count) {
						$criteria[] = $glue;
						$x++;
					}
				}
			}

			return $criteria;
		}


		/**
		 *
		 */
		protected function reject($msg = 'Cannot modify query, already compiled')
		{
			throw new Flourish\ProgrammerException($msg);
		}


		/**
		 *
		 */
		protected function split($conditions)
		{
			$criteria = array();

			foreach ($conditions as $condition => $value) {
				if (is_numeric($condition)) {
					if (!is_array($value)) {
						throw new Flourish\ProgrammerException(
							'Invalid criteria, non-keyed criteria values must be an array'
						);
					}

					$criteria[] = $this->split($value);

				} elseif (in_array($condition, ['any', 'all'])) {
					if (!is_array($value)) {
						throw new Flourish\ProgrammerException(
							'Invalid criteria, any / all values must be arrays'
						);
					}

					$criteria[$condition] = $this->split($value);

				} else {
					if (!preg_match('#^([a-zA-Z0-9._]+)\s+(.{2})$#', $condition, $matches)) {
						throw new Flourish\ProgrammerException(
							'Invalid criteria, condition "%s" is malformed',
							$condition
						);
					}

					$criteria[] = [$matches[1], $matches[2], $value];
				}
			}

			return $criteria;
		}
	}
}
