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
		public function executeFailure(Query $query, $reply, $message);


		/**
		 *
		 */
		public function executeQuery(Query $query);


		/**
		 *
		 */
		public function prepareQuery($cmd);


		/**
		 *
		 */
		public function resolve(Query $query, $reply, $count);
	}
}
