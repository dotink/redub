<?php namespace Redub\Database
{
	use Dotink\Flourish;

	/**
	 *
	 */
	class Connection implements ConnectionInterface
	{
		/**
		 *
		 */
		protected $alias = NULL;


		/**
		 *
		 */
		protected $driver = NULL;


		/**
		 *
		 */
		protected $handle = NULL;


		/**
		 *
		 */
		public function __construct($alias, $config = array())
		{
			$this->alias  = $alias;
			$this->config = $config;
		}


		/**
		 *
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
					$this->getConfig('dbname'),
					$this->alias
				);
			}

			if (is_callable($executable)) {
				$query  = new Query();
				$return = $executable($query, ...$params);

				if ($return) {
					$query = $return;
				}

			} elseif (!($executable instanceof Query)) {
				$token = sprintf($this->driver->getPlaceholder(), '$1');
				$query = preg_replace('#\{\{(\d+)\}\}#', $token, (string) $executable);
				$query = new Query($query, ...$params);

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
		 *
		 */
		public function getAlias()
		{
			return $this->alias;
		}


		/**
		 *
		 */
		public function getConfig($key = NULL, $default = NULL)
		{
			if (!$key) {
				return $this->config;

			} elseif (isset($this->config[$key])) {
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
		 *
		 */
		public function getFields($repository)
		{
			return $this->platform->resolveFields($this, $repository);
		}


		/**
		 *
		 */
		public function getLinks($repository)
		{
			return $this->platform->resolveLinks($connection, $repository);
		}


		/**
		 *
		 */
		public function getRepositories()
		{
			return $this->platform->resolveRepositories($this);
		}


		/**
		 *
		 */
		public function getUniqueConstraints($repository)
		{
			return $this->platform->resolveUniqueConstraints($connection, $repository);
		}


		/**
		 *
		 */
		public function setDriver(DriverInterface $driver)
		{
			$this->handle   = NULL;
			$this->driver   = $driver;
			$this->platform = $driver->getPlatform();
		}


		/**
		 *
		 */
		public function setHandle($handle)
		{
			$this->handle = $handle;
		}


		/**
		 *
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
