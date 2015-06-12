<?php namespace Redub\Database
{
	/**
	 *
	 */
	class Criteria
	{
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
		public function where($conditions)
		{
			if (is_string($conditions) && func_num_args() == 2) {
				$this->criteria[] = $this->split(func_get_arg(0), func_get_arg(1));

			} elseif (is_array($conditions)) {
				foreach ($conditions as $condition => $value) {
					$this->criteria[] = $this->split($condition, $value);
				}

			} else {
				throw new Flourish\ProgrammerException(
					'Invalid where conditions specified, conditions should be an array'
				);
			}

			return $this;
		}


		/**
		 *
		 */
		public function with(array $arguments = array())
		{
			$this->arguments = $arguments;

			return $this;
		}


		/**
		 *
		 */
		protected function split($condition, $value)
		{
			if (!preg_match('#^([a-zA-Z_]+)\s+(.{2})$#', $condition, $matches)) {
				throw new Flourish\ProgrammerException(
					'Invalid criteria passed to query, malformed condition "%s"',
					$condition
				);
			}

			return [$matches[1], $matches[2], $value];
		}
	}
}
