<?php namespace Redub\Database\SQL
{
	/**
	 *
	 */
	class SqlServer extends AbstractPlatform
	{
		/**
		 *
		 */
		protected function composeSelect($query, $driver)
		{
			return sprintf(
				"SELECT %s %s %s %s %s",
				$this->composeLimit   ($query, $driver),
				$this->composeColumns ($query, $driver),
				$this->composeFrom    ($query, $driver),
				$this->composeWhere   ($query, $driver),
				$this->composeOffset  ($query, $driver)
			);
		}


		/**
		 *
		 */
		protected function composeLimit($query, $driver)
		{
			$limit = $query->getLimit();

			if ($limit === NULL) {
				return NULL;

			} elseif (!is_int($limit) || $limit < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot compose query with non-integer or negative LIMIT clause'
				);

			}

			return sprintf('TOP %s', $driver->escapeValue($limit, $query));
		}
	}
