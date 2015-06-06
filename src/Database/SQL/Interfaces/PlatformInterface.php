<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	interface PlatformInterface
	{
		/**
		 *
		 */
		public function compose(Query $query);


		/**
		 *
		 */
		public function parse(Query $query);
	}
}
