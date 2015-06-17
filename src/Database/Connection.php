<?php namespace Redub\Database
{
	use Dotink\Flourish;

	/**
	 * The connection is the user/developer facing interface to their database.
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	class Connection implements ConnectionInterface
	{
		/**
		 * The connection alias, a short name to identify the connection
		 *
		 * @access protected
		 * @var string
		 */
		protected $alias = NULL;


		/**
		 * The driver which this connection uses
		 *
		 * @access protected
		 * @var Database\DriverInterface
		 */
		protected $driver = NULL;


		/**
		 * The connection handle.  The type will vary depending on the driver connected.
		 *
		 * @access protected
		 * @var mixed
		 */
		protected $handle = NULL;


		/**
		 * Create a new connection instance
		 *
		 * @access public
		 * @var string $alias The connection alias, a short name to identify the connection
		 * @var array $config The connection configuration data
		 * @return void
		 */
		public function __construct($alias, array $config = array())
		{
			$this->alias  = $alias;
			$this->config = $config;
		}


		/**
		 * Execute a command/operation/query on the connection using the connected driver
		 *
		 * The executable can either be a callback which will receive and can modify a Query,
		 * a Query object (directly), or a string compatible containing a native query.
		 *
		 * @access public
		 * @var mixed $executable An executable operation
		 * @var array $params The parameters to pass to the executable or those for the Query
		 * @return Database\ResultInterface The data results
		 */
		public function execute($executable, ...$params)
		{
			if (!$this->driver) {
				throw new Flourish\ConnectivityException(
					'Unable to execute (%s), no driver configured',
					$executable
				);
			}

			if (!$this->setup()) {
				throw new Flourish\ConnectivityException(
					'Unable to connect to database "%s" on connection "%s"',
					$this->getConfig('name'),
					$this->alias
				);
			}

			if (is_callable($executable)) {
				$query = $this->resolveCallable($executable, ...$params);

			} elseif (!($executable instanceof Query)) {
				$query = $this->resolveStringType($executable, ...$params);

			} else {
				$query = $executable;
			}

			$result = $this->driver->run($this->handle, $query);

			if (!($result instanceof ResultInterface)) {
				throw new Flourish\ProgrammerException(
					'Invalid result returned by driver "%s", must implemente ResultInterface',
					get_class($this->driver)
				);
			}

			return $result;
		}


		/**
		 * Get the connection alias
		 *
		 * @access public
		 * @return string The connection alias, a short name to identify the connection
		 */
		public function getAlias()
		{
			return $this->alias;
		}


		/**
		 * Get a configuration value(s) for the connection, or return the default if not found
		 *
		 * @access public
		 * @param string $key The key for which to get the value, return all values if NULL
		 * @param mixed $default The default value to use if no value is associated with the key
		 * @return mixed The configuration value associated with the key
		 */
		public function getConfig($key = NULL, $default = NULL)
		{
			if (!$key) {
				return $this->config;

			} elseif (array_key_exists($key, $this->config)) {
				return $this->config[$key];

			} elseif (func_num_args() == 2) {
				return $this->config[$key] = $default;

			} else {
				throw new Flourish\ProgrammerException(
					'Missing configuration key "%s" (used by driver) in connection config',
					$key
				);
			}
		}


		/**
		 * Get a list of all the fields for the repository
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get fields
		 * @return array The list of all the fields for the repository
		 */
		public function getFields($repository)
		{
			return $this->platform->resolveFields($this, $repository);
		}


		/**
		 * Get the default value for a field on a repository
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get the default
		 * @param string $field The name of the field for which to get the default
		 * @return mixed The default value for the field
		 */
		public function getFieldDefault($repository, $field)
		{
			return $this->platform->resolveFieldInfo($this, $repository, $field, 'default');
		}


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
		public function getFieldType($repository, $field)
		{
			return $this->platform->resolveFieldInfo($this, $repository, $field, 'type');
		}


		/**
		 * Get the fields representing the identity of the repository
		 *
		 * This should always return an array, even if there is only a single field/column ever.
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get the identity
		 * @return array A list of fields/columns which represent the identity of the repository
		 */
		public function getIdentity($repository)
		{
			return $this->platform->resolveIdentity($this, $repository);
		}


		/**
		 * Get the list of *-to-many routes for a given repository
		 *
		 * Unique relationships represent one-to-many relationships and are to be understood as
		 * entity X has many unique Ys.
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to get the routes
		 * @param boolean $unique Whether or not to return unique relationships
		 * @return array A list of routes, routes are keyed arrays with field mappings
		 */
		public function getRoutesToMany($repository, $unique = FALSE)
		{
			return $this->platform->resolveRoutesToMany($this, $repository, $unique);
		}


		/**
		 * Get the list of *-to-one routes for a given repository
		 *
		 * Unique relationships represent one-to-one relationships and are to be understood as
		 * entity X has one unique Y.
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to get the routes
		 * @param boolean $unique Whether or not to return unique relationships
		 * @return array A list of routes, routes are keyed arrays with field mappings
		 */
		public function getRoutesToOne($repository, $unique = FALSE)
		{
			return $this->platform->resolveRoutesToOne($this, $repository, $unique);
		}


		/**
		 * Get a list of repositories available on this connection
		 *
		 * @access public
		 * @return array The list of repositories available on the connection
		 */
		public function getRepositories()
		{
			return $this->platform->resolveRepositories($this);
		}


		/**
		 * Get the unique indexes for a repository
		 *
		 * @access public
		 * @param string $repository The name of the repository for which to get unique indexes
		 * @return array A list of unique indexes, each index constitutes an array of fields
		 */
		public function getUniqueIndexes($repository)
		{
			return $this->platform->resolveUniqueIndexes($this, $repository);
		}


		/**
		 * Determine whether or not a field on a given repository is auto generated
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to check the field
		 * @param string $field The name of the field which is being checked
		 * @return boolean TRUE if the field is auto generated, FALSE otherwise
		 */
		public function isFieldAutoGenerated($repository, $field)
		{
			return $this->platform->resolveFieldInfo($this, $repository, $field, 'auto');
		}


		/**
		 * Determine whether or not a field on a given repository is nullable
		 *
		 * @access public
		 * @param string $repository The name of the repository on which to check the field
		 * @param string $field The name of the field which is being checked
		 * @return boolean TRUE if the field is nullable, FALSE otherwise
		 */
		public function isFieldNullable($repository, $field)
		{
			return $this->platform->resolveFieldInfo($this, $repository, $field, 'nullable');
		}


		/**
		 * Set the driver for this connection.
		 *
		 * Setting the driver should reset the handle and the platform accordingly.
		 *
		 * @access public
		 * @param DriverInterface $driver The driver to set for the connection
		 * @return void
		 */
		public function setDriver(DriverInterface $driver)
		{
			$this->handle   = NULL;
			$this->driver   = $driver;
			$this->platform = $driver->getPlatform();
		}


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
		public function setHandle($handle)
		{
			$this->handle = $handle;
		}


		/**
		 * Resolve a callable executable into a query
		 *
		 * @access protected
		 * @param callable $executable The callable executable to resolve
		 * @param array $params The parameters for the query
		 * @return Query The resolved query
		 */
		protected function resolveCallable($executable, ...$params)
		{
			$query  = new Query();
			$return = $executable($query, ...$params);

			if ($return) {
				$query = $return;
			}

			return $query;
		}


		/**
		 * Resolve a string type executable into a query
		 *
		 * Note that string types can be objects but they will be converted to strings and
		 * therefore must implement the `__toString()` method.  This is used for anything that's
		 * not a callable or Query object.
		 *
		 * @access protected
		 * @param string $executable The string type executable to resolve
		 * @param array $params The parameters for the query
		 * @return Query The resolved query
		 */
		protected function resolveStringType($executable, ...$params)
		{
			$token  = sprintf($this->driver->getPlaceholder(), '$1');
			$string = preg_replace('#\{\{(\d+)\}\}#', $token, (string) $executable);
			$query  = new Query($string, ...$params);

			return $query;
		}


		/**
		 * Setup the handle by calling on the database to connect.
		 *
		 * The database will be responsible for setting it's own handle on the connection.
		 *
		 * @access protected
		 * @return mixed The setup handle
		 */
		protected function setup()
		{
			if (!$this->handle) {
				$this->driver->connect($this, TRUE);
			}

			return $this->handle;
		}
	}
}
