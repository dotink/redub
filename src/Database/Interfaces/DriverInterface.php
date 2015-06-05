<?php namespace Redub\Database
{
	/**
	 *
	 */
	interface DriverInterface
	{
		public function run(ConnectionInterface $connection, $query);
	}
}
