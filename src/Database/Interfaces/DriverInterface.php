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
		static public function getPlatform();


		/**
		 *
		 */
		public function connect(ConnectionInterface $connection);


		/**
		 *
		 */
		public function run($handle, Query $query);
	}
}
