<?php namespace Redub\Database
{
	/**
	 * The connection is the user/developer facing interface to their database.
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	interface ConnectionInterface
	{
		/**
		 * Execute a command/operation/query on the connection using the connected driver
		 *
		 * The executable can either be a callback which will receive and can modify a Query,
		 * a Query object (directly), or a string compatible containing a native query.
		 *
		 * @access public
		 * @var mixed $executable An executable operation
		 * @var array $params The parameters to pass to the executable or those for the Query
		 * @return ResultInterface The data results
		 */
		public function execute($executable, ...$params);


		/**
		 * Get the connection alias
		 *
		 * @access public
		 * @return string The connection alias, a short name to identify the connection
		 */
		public function getAlias();


		/**
		 * Get a configuration value(s) for the connection, or return the default if not found
		 *
		 * @access public
		 * @param string $key The key for which to get the value, return all values if NULL
		 * @param mixed $default The default value to use if no value is associated with the key
		 * @return mixed The configuration value associated with the key
		 */
		public function getConfig($key = NULL, $default = NULL);


		/**
		 * Get a list of all the fields for the repository
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get fields
		 * @return array The list of all the fields for the repository
		 */
		public function getFields($repository);


		/**
		 * Get the default value for a field on a repository
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get the default
		 * @param string $field The name of the field for which to get the default
		 * @return mixed The default value for the field
		 */
		public function getFieldDefault($repository, $field);


		/**
		 * Get the data type for a field on a repository
		 *
		 * Abstracted data types include: boolean, integer, float, character, string, text,
		 * timestamp, date, time, binary.
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get the data type
		 * @param string $field The name of the field for which to get the data type
		 * @return mixed The data type of the field
		 */
		public function getFieldType($repository, $field);


		/**
		 * Get the fields representing the identity of the repository
		 *
		 * This should always return an array, even if there is only a single field/column ever.
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get the identity
		 * @return array A list of fields/columns which represent the identity of the repository
		 */
		public function getIdentity($repository);


		/**
		 * Get the list of *-to-many routes for a given repository
		 *
		 * Unique relationships represent one-to-many relationships and are to be understood as
		 * entity X has many unique Ys.  Non-unique relationships would be many-to-many.
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to get the routes
		 * @param boolean $unique Whether or not to return unique relationships
		 * @return array A list of routes, routes are keyed arrays with field mappings
		 */
		public function getRoutesToMany($repository, $unique = FALSE);


		/**
		 * Get the list of *-to-one routes for a given repository
		 *
		 * Unique relationships represent one-to-one relationships and are to be understood as
		 * entity X has one unique Y.  Non-unique relationships would be many-to-one.
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to get the routes
		 * @param boolean $unique Whether or not to return unique relationships
		 * @return array A list of routes, routes are keyed arrays with field mappings
		 */
		public function getRoutesToOne($repository, $unique = FALSE);


		/**
		 * Get a list of repositories available on this connection
		 *
		 * @access public
		 * @return array The list of repositories available on the connection
		 */
		public function getRepositories();


		/**
		 * Get the unique indexes for a repository
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get unique indexes
		 * @return array A list of unique indexes, each index constitutes an array of fields
		 */
		public function getUniqueIndexes($repository);


		/**
		 * Determine whether or not a field on a given repository is auto generated
		 *
		 * This usually maps to something like AUTO_INCREMENT in mysql or SERIAL columns and
		 * integers with sequences in postgres.  For something like mongo it would probably only
		 * ever return TRUE on `_id`.
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to check the field
		 * @param string $field The name of the field which is being checked
		 * @return boolean TRUE if the field is auto generated, FALSE otherwise
		 */
		public function isFieldAutoGenerated($repository, $field);


		/**
		 * Determine whether or not a field on a given repository is nullable
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to check the field
		 * @param string $field The name of the field which is being checked
		 * @return boolean TRUE if the field is nullable, FALSE otherwise
		 */
		public function isFieldNullable($repository, $field);


		/**
		 * Set the driver for this connection.
		 *
		 * Setting the driver should reset the handle and the platform accordingly.
		 *
		 * @access public
		 * @param DriverInterface $driver The driver to set for the connection
		 * @return void
		 */
		public function setDriver(DriverInterface $driver);


		/**
		 * Set the handle for this connection.
		 *
		 * This is public, but should really only be called by the driver when a connection
		 * attempt is made.
		 *
		 * @access public
		 * @param mixed $handle The handle for the connection to use when interface with the driver
		 * @return void
		 */
		public function setHandle($handle);
	}
}
