<?php namespace Redub\Database
{
	/**
	 * Criteria are simple containers which contain an array of match conditions and arguments
	 *
	 * The criteria is the more basic form of a query.  It is designed only to provide aggregate
	 * "and" conditions for queries and finding as well as a list of arguments to affect with
	 * an action.
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	class Criteria
	{
		/**
		 * The list of arguments to affect.
		 *
		 * For performing a select, this is a list of fields to actually select.
		 *
		 * @access protected
		 * @var array
		 */
		protected $arguments = array();


		/**
		 * The list of conditions to require.
		 *
		 * Unlike the query object, this should never contain a complex set of conditions.  It
		 * is always designed to be a one-dimensional key (condition) => value list.
		 *
		 * @access protected
		 * @var array
		 */
		protected $criteria = array();


		/**
		 * Add/merge conditions into the criteria
		 *
		 * Conditions take the form of ['firstName ==' => 'Matthew']
		 *
		 * @access public
		 * @param array $conditions The conditions to add to the criteria
		 * @return Criteria The object instance for method chaining
		 */
		public function where(array $conditions)
		{
			if (is_array($conditions)) {
				$this->criteria = array_merge($this->criteria, $conditions);
			}

			return $this;
		}


		/**
		 * Add/merge arguments into the criteria
		 *
		 * Arguments take the form of ['firstName', 'lastName']
		 *
		 * @access public
		 * @param array $arguments The arguments to merge into the criteria
		 * @return Criteria The object instance for method chaining
		 */
		public function with(array $arguments = array())
		{
			$this->arguments = array_unique(array_merge($this->arguments, $arguments));

			return $this;
		}
	}
}
