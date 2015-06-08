<?php namespace Redub\Database
{
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
