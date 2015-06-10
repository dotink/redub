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
		protected function composeSelect($query, $placeholder)
		{
			$this->placeholderIndex = static::PLACEHOLDER_START;

			return sprintf(
				'SELECT %s %s %s %s %s',
				$this->composeLimit   ($query, $placeholder),
				$this->composeColumns ($query, $placeholder),
				$this->composeFrom    ($query, $placeholder),
				$this->composeWhere   ($query, $placeholder),
				$this->composeOffset  ($query, $placeholder)
			);
		}


		/**
		 *
		 */
		protected function composeLimit($query, $placeholder)
		{
			$limit = $query->getLimit();

			if ($limit === NULL) {
				return NULL;

			} elseif (!is_int($limit) || $limit < 0) {
				throw new Flourish\ProgrammerException(
					'Cannot compose query with non-integer or negative LIMIT clause'
				);

			}

			return sprintf('TOP %s', $this->makePlaceholder($query, $placeholder, $limit));
		}
	}
