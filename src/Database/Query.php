<?php namespace Redub\Database
{
	/**
	 *
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
		protected $glue = array();


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
		protected $statement = NULL;


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
		public function andWhere($condition, $value)
		{
			$this->reset();

			$this->criteria[] = 'AND';
			$this->criteria[] = $this->glue($condition, $value);

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
		public function getArguments()
		{
			return $this->arguments;
		}


		/**
		 *
		 */
		public function getCollection()
		{
			return $this->collection;
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
		public function getGlue($index)
		{
			if (isset($this->glue[$index])) {
				return $this->glue[$index];
			}

			return 'AND';
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
		public function on($collection, array $links = array())
		{
			$this->reset();

			$this->collection = $collection;
			$this->links      = $links;

			return $this;
		}


		/**
		 *
		 */
		public function orWhere($condition, $value)
		{
			$this->reset();

			$this->criteria[] = 'OR';
			$this->criteria[] = $this->glue($condition, $value);

			return $this;
		}


		/**
		 *
		 */
		public function perform($action, array $args = array())
		{
			$this->reset();

			$this->action     = $action;
			$this->arguments = $args;

			return $this;
		}


		/**
		 *
		 */
		public function setAction($action)
		{
			$this->reset();

			$this->action = $action;

			return $this;
		}


		/**
		 *
		 */
		public function setArguments(array $arguments = array())
		{
			$this->reset();

			$this->arguments = $arguments;

			return $this;
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
		public function using(Criteria $criteria)
		{
			$this->reset();

			$this->criteria   = array();
			$this->criteria[] = $criteria->criteria;

			if (count($criteria->arguments)) {
				$this->arguments = $criteria->arguments;
			}

			return $this;
		}


		/**
		 *
		 */
		public function where($condition)
		{
			$this->reset();

			if (func_num_args() != 2) {
				$condition = NULL;
				$value     = array();

			} else {
				$value = func_get_arg(1);
			}

			$this->criteria   = array();
			$this->criteria[] = $this->glue($condition, $value);

			return $this;
		}


		/**
		 *
		 */
		public function with(array $arguments = array())
		{
			$this->reset();

			$this->arguments = $args;

			return $this;
		}


		/**
		 *
		 */
		protected function glue($condition, $value)
		{
			if (is_array($value)) {
				if (!in_array($condition, ['any', 'all'])) {
					throw new Flourish\ProgrammerException(
						'Invalid criteria passed to query, conditon must be "any" or "all"'
					);
				}

				$this->glue[count($this->criteria)] = $condition == 'all'
					? 'AND'
					: 'OR';

				return $value;

			}

			$this->glue[count($this->criteria)] = 'AND';

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
