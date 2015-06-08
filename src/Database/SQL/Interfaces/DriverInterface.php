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
		public function executeCount($reply);


		/**
		 *
		 */
		public function executeFailure($response, $message);


		/**
		 *
		 */
		public function execute($statement);


		/**
		 *
		 */
		public function prepare(Database\Query $query);


		/**
		 *
		 */
		public function resolve(Query $query, $reply, $count);
	}
}
