<?php namespace Redub\Database
{
	interface ConnectionInterface
	{
		/**
		 *
		 */
		public function __construct($driver_alias, $config = array());


		/**
		 *
		 */
		public function setDriver(DriverInterface $driver);


		/**
		 *
		 */
		public function getDriverAlias();


		/**
		 *
		 */
		public function query($query);
	}
}
