<?php namespace Redub\Database
{
	use Dotink\Flourish;

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
			if (is_array($conditions)) {
				$this->criteria = array_merge($this->criteria, $conditions);
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
