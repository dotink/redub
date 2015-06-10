<?php namespace Redub\Database
{
	interface ConnectionInterface
	{
		/**
		 *
		 */
		public function __construct($alias, $config = array());


		/**
		 *
		 */
		public function execute($executable);


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
		public function getHandle();


		/**
		 *
		 */
		public function setDriver(DriverInterface $driver);

	}
}
