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
		public function getPlatform();


		/**
		 *
		 */
		public function run(Query $query);
	}
}
