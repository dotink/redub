<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	interface DriverInterface extends Database\DriverInterface
	{
		public function getIdentifierRegex();
		public function getAliasRegex();
	}
}
