<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	class Driver implements DriverInterface
	{
		const REGEX_IDENTIFIER = '[a-zA-Z_.]+';
		const REGEX_ALIAS      = '[a-z_]+';
		const REGEX_ACTION     = 'SELECT|DROP|INSERT|UPDATE|DELETE|ALTER|CREATE';


		/**
		 *
		 */
		public function getActionRegex()
		{
			return static::REGEX_ACTION;
		}

		/**
		 *
		 */
		public function getAliasRegex()
		{
			return static::REGEX_ALIAS;
		}


		/**
		 *
		 */
		public function getIdentifierRegex()
		{
			return static::REGEX_IDENTIFIER;
		}


		/**
		 *
		 */
		public function run(Database\ConnectionInterface $connection, $query)
		{
			throw new DatabaseException(
				'Cannot run queries on dummy driver'
			);
		}
	}
}
