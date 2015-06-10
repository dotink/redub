<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	interface DriverInterface extends Database\DriverInterface
	{
		/**
		 *
		 */
		public function count($handle, $response);


		/**
		 *
		 */
		public function fail($handle, $response, $message);


		/**
		 *
		 */
		public function execute($handle, $statement);


		/**
		 *
		 */
		public function prepare($handle, Database\Query $query);


		/**
		 *
		 */
		public function resolve(Database\Query $query, $response, $count);
	}
}
