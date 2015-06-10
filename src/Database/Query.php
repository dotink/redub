<?php namespace Redub\Database
{
	/**
	 *
	 */
	class Query
	{
		/**
		 *
		 */
		protected $action = NULL;


		/**
		 *
		 */
		protected $actionArgs = array();


		/**
		 *
		 */
		protected $criteria = array();


		/**
		 *
		 */
		protected $criteriaJoins = array();


		/**
		 *
		 */
		protected $statement = NULL;


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
		protected $prepared = FALSE;


		/**
		 *
		 */
		public function __construct($statement = NULL, array $params = array())
		{
			$this->statement = $statement;
			$this->params    = $params;
		}


		/**
		 *
		 */
		public function __clone()
		{
			$this->statement = NULL;
		}


		/**
		 *
		 */
		public function __toString()
		{
			return $this->get();
		}


		/**
		 *
		 */
		public function andWhere($condition, $value)
		{
			$this->reset();

			$this->criteria[] = 'AND';
			$this->criteria[] = $this->makeJoinCriteria($condition, $value);

			return $this;
		}


		/**
		 *
		 */
		public function at($offset)
		{
			$this->reset();

			$this->offset = $offset;

			return $this;
		}


		/**
		 *
		 */
		public function get()
		{
			return $this->statement;
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
		public function getActionArgs()
		{
			return $this->actionArgs;
		}


		/**
		 *
		 */
		public function getCriteria()
		{
			return $this->criteria;
		}


		/**
		 *
		 */
		public function getCriteriaJoins()
		{
			return $this->criteriaJoins;
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
		public function getParams()
		{
			return $this->params;
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
		public function getCollections()
		{
			return $this->collections;
		}


		/**
		 *
		 */
		public function isPrepared()
		{
			return $this->prepared;
		}


		/**
		 *
		 */
		public function link($repository, array $route)
		{
			$this->reset();

			$this->links[$repository] = $route;

			return $this;
		}


		/**
		 *
		 */
		public function limit($count)
		{
			$this->reset();

			$this->limit = $count;

			return $this;
		}


		/**
		 *
		 */
		public function on($collections, array $links = array())
		{
			$this->reset();

			settype($collections, 'array');

			$this->collections = $collections;
			$this->links       = $links;

			return $this;
		}


		/**
		 *
		 */
		public function orWhere($condition, $value)
		{
			$this->reset();

			$this->criteria[] = 'OR';
			$this->criteria[] = $this->makeJoinCriteria($condition, $value);

			return $this;
		}


		/**
		 *
		 */
		public function perform($action, array $args = array())
		{
			$this->reset();

			$this->action     = $action;
			$this->actionArgs = $args;

			return $this;
		}


		/**
		 *
		 */
		public function addParam($value, $index = NULL)
		{
			if ($index !== NULL) {
				$this->params[$index] = $value;
			} else {
				$this->params[] = $value;
			}

		}


		/**
		 *
		 */
		public function setParams(...$params)
		{
			$this->params = $params;
		}

		/**
		 *
		 */
		public function setPrepared($value)
		{
			$this->prepared = (bool) $value;

			return $this;
		}


		/**
		 *
		 */
		public function where($condition, $value)
		{
			$this->reset();

			$this->criteria   = array();
			$this->criteria[] = $this->makeJoinCriteria($condition, $value);

			return $this;
		}


		/**
		 *
		 */
		protected function makeJoinCriteria($condition, $value)
		{
			if (is_array($value)) {
				if (!in_array($condition, ['any', 'all'])) {
					throw new Flourish\ProgrammerException(
						'Invalid criteria passed to query, conditon must be "any" or "all"'
					);
				}

				$this->criteriaJoins[count($this->criteria)] = $condition == 'all'
					? 'AND'
					: 'OR';

				return $value;

			}

			$this->criteriaJoins[count($this->criteria)] = 'AND';

			return [$condition => $value];
		}


		/**
		 *
		 */
		protected function reset()
		{
			$this->statement = NULL;
		}
	}
}
