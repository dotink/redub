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
		static public function getPlaceHolder();


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
