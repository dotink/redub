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
		protected $driver = NULL;


		/**
		 *
		 */
		protected $driverAlias = NULL;


		/**
		 *
		 */
		public function __construct($config = array(), DriverInterface $driver = NULL)
		{
			if (!isset($config['driver']) && !isset($config['dbname'])) {
				throw new Flourish\ProgrammerException(
					'Cannot create connection with missing driver or database name.'
				);
			}

			$this->config = $config;
			$this->driver = $driver;
		}


		/**
		 *
		 */
		public function execute($cmd)
		{
			if (!$this->driver) {
				throw new Exception(
					'Unable to execute (%s), no driver configured',
					$cmd
				);
			}

			if (!$this->driver->connect($this)) {
				throw new Exception(
					'Unable to connect to database "%s" using driver "%s"',
					$this->getConfig('dbname'),
					$this->getConfig('driver')
				);
			}

			return $this->driver->run($cmd);
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
				return $default;

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
		public function setDriver(DriverInterface $driver)
		{
			$this->driver = $driver;
		}
	}
}
