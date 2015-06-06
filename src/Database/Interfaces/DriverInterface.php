<?php namespace Redub\Database
{
	/**
	 *
	 */
	interface DriverInterface
	{
		/**
		 *
		 */
		public function connect(ConnectionInterface $connection);


		/**
		 *
		 */
		public function run($cmd);
	}
}
