<?php namespace Redub\Database
{
	/**
	 * A database driver interface that provides sufficient abstraction to any possible database
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	interface DriverInterface
	{
		/**
		 * Get the platform for the driver
		 *
		 * Most drivers will only ever work with a single platform.  While the PDO driver is an
		 * exception to this, class inheritance can be an easy solution to changing the
		 * platform class which will be instantiated.
		 *
		 * @static
		 * @access public
		 * @var PlatformInterface The platform which the driver uses
		 */
		static public function getPlatform();


		/**
		 * Connect a connection
		 *
		 * An immediate connection should establish an active handle with the connection as with
		 * this method call.  A non-immediate collection can set the driver only on the connection
		 * and wait for a future setup call.
		 *
		 * @access public
		 * @param ConnectionInterface $connection The connection from which to get config settings
		 * @param boolean $immediate Whether or not the connection should be established immediately
		 * @return boolean TRUE if the connection is enabled, FALSE otherwise
		 */
		public function connect(ConnectionInterface $connection, $immediate = FALSE);


		/**
		 * Escape a collection/column identifier for command string output.
		 *
		 * The driver should not attempt to return placeholders for this information even if it is
		 * supported by the driver's execution model.
		 *
		 * @access public
		 * @param string $name The name of the identifier to escape
		 * @return string The escaped identifier
		 */
		public function escapeIdentifier($name);


		/**
		 * Escape a value for command string output.
		 *
		 * Note that the driver may return a placeholder and add the parameter to the `$query`
		 * object instead.
		 *
		 * @access public
		 * @param mixed $value The value to escape
		 * @param Query $query The query, for optional parameter amendment
		 * @return mixed $value The escaped value for command string output
		 */
		public function escapeValue($value, Query $query);


		/**
		 * Reset any driver indexes, caches, etc.
		 *
		 * @access public
		 * @return void
		 */
		public function reset();


		/**
		 * Run a query on a given connection handle
		 *
		 * @access public
		 * @param mixed $handle The connection handle on which to run the query
		 * @param Query $query The query which we are to execute, not necessarily compiled
		 * @return ResultInterface The result from running the query
		 */
		public function run($handle, Query $query);
	}
}
