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
		public function __construct($driver_alias, $config = array())
		{
			$this->driverAlias = $driver_alias;
			$this->config      = $config;
		}


		/**
		 *
		 */
		public function getDriverAlias()
		{
			return $this->driverAlias;
		}


		/**
		 *
		 */
		public function query($query)
		{
			return $this->driver->run($this, $query);
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
					'Missing configuration key "%s" is connection config',
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
