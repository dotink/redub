<?php namespace Redub\Database
{
	interface ConnectionInterface
	{
		/**
		 *
		 */
		public function __construct($alias, $config = array(), DriverInterface $driver);


		/**
		 *
		 */
		public function setDriver(DriverInterface $driver);


		/**
		 *
		 */
		public function execute($cmd);
	}
}
