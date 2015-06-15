<?php namespace Redub\Database
{
	interface ConnectionInterface
	{
		/**
		 *
		 */
		public function __construct($alias, array $config = array());


		/**
		 *
		 */
		public function execute($executable, ...$params);


		/**
		 *
		 */
		public function getAlias();


		/**
		 *
		 */
		public function getConfig($key = NULL, $default = NULL);


		/**
		 *
		 */
		public function setDriver(DriverInterface $driver);

	}
}
