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
				$this->criteria = array_merge($this->criteria, [
					func_get_arg(0) => func_get_arg(1)
				]);

			} elseif (is_array($conditions)) {
				$this->criteria = array_merge($this->criteria, $conditions);

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
	}
}
