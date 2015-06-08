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
		protected $driverAlias = NULL;


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
		public function execute($statement)
		{
			if (!$this->driver) {
				throw new Exception(
					'Unable to execute (%s), no driver configured',
					$statement
				);
			}

			if (!$this->driver->connect($this)) {
				throw new Exception(
					'Unable to connect to database "%s" on connection "%s"',
					$this->getConfig('dbname'),
					$this->alias
				);
			}

			if (!($statement instanceof Query)) {
				$query = $this->driver->getPlatform()->parse(new Query((string) $statement));
			}

			return $this->driver->run($query);
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
		public function hasDriver()
		{
			return isset($this->driver);
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
