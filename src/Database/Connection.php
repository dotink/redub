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
		public function __construct($alias, $config = array(), DriverInterface $driver = NULL)
		{
			$this->alias  = $alias;
			$this->config = $config;
			$this->driver = $driver;
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

			if (!$this->getHandle()) {
				throw new Flourish\ConnectivityException(
					'Unable to connect to database "%s" on connection "%s"',
					$this->getConfig('dbname'),
					$this->alias
				);
			}

			if (is_callable($executable)) {
				$executable = $executable(new Query());
			}

			$result = $this->driver->run($this->handle, !($executable instanceof Query)
				? new Query((string) $executable, ...$params)
				: $executable
			);

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
		public function getHandle()
		{
			if (!$this->handle) {
				$this->handle = $this->driver->connect($this);
			}

			return $this->handle;
		}


		/**
		 *
		 */
		public function setDriver(DriverInterface $driver)
		{
			$this->driver = $driver;
		}
	}
}
