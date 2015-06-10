<?php namespace Redub\Database
{
	/**
	 * A basic passthrough platform which can be extended and overloaded.
	 *
	 */
	abstract class AbstractPlatform implements PlatformInterface
	{
		/**
		 * Compose an executable statement for a driver
		 *
		 * @access public
		 * @param Query $query The query to compose
		 * @return mixed The executable statement for a driver using this platform
		 */
		public function compose(Query $query)
		{
			return $query->get();
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
		abstract public function parse(Query $query);
	}
}
