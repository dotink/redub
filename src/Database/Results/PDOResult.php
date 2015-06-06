<?php namespace Redub\Database
{
	use Dotink\Flourish;

	use PDOStatement;
	use PDO;

	/**
	 * A PDOResult
	 *
	 */
	class PDOResult implements ResultInterface
	{
		/**
		 *
		 */
		protected $count = 0;


		/**
		 *
		 */
		protected $result = NULL;


		/**
		 *
		 */
		protected $rows = array();


		/**
		 *
		 */
		public function __construct(PDOStatement $result, $count = 0)
		{
			$this->result         = $result;
			$this->count          = $count;

			$this->fetchRow();
		}


		/**
		 *
		 */
		public function count()
		{
			return $this->count;
		}


		/**
		 * Gets the row currently pointed to by the cursor
		 */
 		public function current()
		{
			return current($this->rows);
		}


		/**
		 * Gets the row at a given index.
		 */
		public function get($index)
		{
			if (!is_int($index) || $index < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot get element at index "%s", only positive integer indexes are supported',
					$index
				);
			}

			if ($index >= $this->count) {
				throw new Flourish\ProgrammerException(
					'Cannot get element at index "%s", only "%s" results returned',
					$index,
					$this->count
				);
			}

			if (!isset($this->rows[$index])) {
				while (count($this->rows) < $index + 1) {
					$this->rows[] = $this->result->fetch(PDO::FETCH_ASSOC);
				}
			}

			return $this->rows[$index];
		}


		/**
		 * Gets the index of the row currently pointed to by the cursor
		 */
		public function key()
		{
			return key($this->rows);
		}


		/**
		 * Increments the cursor position in the result set
		 */
		public function next()
		{
			if ($this->key() < ($this->count - 1)) {
				$this->fetchRow();
			}

			next($this->rows);
		}


		/**
		 *
		 */
		public function rewind()
		{
			reset($this->rows);
		}


		/**
		 *
		 */
		public function valid()
		{
			return $this->current() !== FALSE;
		}


		/**
		 *
		 */
		protected function fetchRow()
		{
			if ($row = $this->result->fetch(PDO::FETCH_ASSOC)) {
				$this->rows[] = $row;
			}
		}
	}
}
