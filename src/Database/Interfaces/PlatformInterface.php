<?php namespace Redub\Database
{
	/**
	 * The most basic platform interface
	 *
	 * Platforms are responsible all platform specific transformations and information gathering.
	 * They can compile/parse queries and act to provide the connection with the results needed
	 * to abstract schema reflection.
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	interface PlatformInterface
	{
		/**
		 * Compile a query into an executable statement
		 *
		 * @access public
		 * @param Query $query The query to compile
		 * @return mixed The executable statement for a driver using this platform
		 */
		public function compile(Query $query, DriverInterface $driver);


		/**
		 * Parse a query's statement an populate the query object
		 *
		 * This method should return a new query object, not the original.
		 *
		 * @access public
		 * @param Query $query The query to parse
		 * @return Query The parsed and populated query object
		 */
		public function parse(Query $query);


		//
		// TODO: Add all the public facing resolveXXX() methods
		//
	}
}
