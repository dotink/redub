<?php namespace Redub\Database\SQL
{
	class Query
	{
		/**
		 *
		 */
		protected $action = NULL;


		/**
		 *
		 */
		protected $driver = NULL;


		/**
		 *
		 */
		protected $limit = NULL;


		/**
		 *
		 */
		protected $selectItems = array();


		/**
		 *
		 */
		protected $selectAliases = array();


		/**
		 *
		 */
		public function __construct($query, DriverInterface $driver = NULL, $parse_full = TRUE)
		{
			$this->driver = $driver ?: new Driver();
			$this->query  = trim($query);

			$this->parse($parse_full);
		}


		/**
		 *
		 */
		public function addSelect($select_item, $select_alias = NULL, $ignore_validation = FALSE)
		{
			$this->selectItems[]   = $select_item;
			$this->selectAliases[] = $select_alias;
		}


		/**
		 *
		 */
		public function checkAction($action)
		{
			return $this->action == trim(strtoupper($action));
		}


		/**
		 *
		 */
		public function checkLimit()
		{
			return $this->limit !== NULL;
		}


		/**
		 *
		 */
		public function compose()
		{
			if (!$this->query) {

			}

			return $this->query . ';';
		}

		/**
		 *
		 */
		public function getLimit()
		{
			return $this->limit();
		}


		/**
		 *
		 */
		public function setSelect($select_string)
		{
			$select_items = explode(',', $select_string);
			$match_regex  = sprintf(
				'#(%s)(?:\sas\s(%s))?#i',
				$this->driver->getIdentifierRegex(),
				$this->driver->getAliasRegex()
			);

			$this->resetSelect();

			foreach ($select_items as $select_item) {
				if (!preg_match($match_regex, $select_item, $matches)) {
					throw new SyntaxException(
						'Invalid select clause "%s" provided in query',
						$select_string
					);
				}

				if (!isset($matches[2])) {
					$matches[2] = NULL;
				}

				$this->addSelect($matches[1], $matches[2], TRUE);
			}

			return this;
		}


		/**
		 *
		 */
		public function setLimit($limit)
		{
			$this->limit = $limit;
		}


		/**
		 *
		 */
		protected function parse($full)
		{
			$this->parseAction();

			if ($full) {
				$this->query = NULL;
			}
		}


		/**
		 *
		 */
		protected function parseAction()
		{
			$regex = sprintf('#^(%s)\s+#i', $this->driver->getActionRegex());

			if (!preg_match($regex, $this->query, $matches)) {
				throw new SyntaxException(
					'Invalid action specified in query "%s"',
					$this->query
				);
			}

			$this->action = $matches[1];
		}

		/**
		 *
		 */
		protected function resetSelect()
		{
			$this->selectItems   = array();
			$this->selectAliases = array();
		}
	}
}
