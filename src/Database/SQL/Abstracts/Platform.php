<?php namespace Redub\Database\SQL
{
	/**
	 *
	 */
	abstract class Platform implements PlatformInterface
	{
		const TOKEN_FORMAT     = '{#T-%d#}';

		const REGEX_ACTION     = '(SELECT|DROP|INSERT|UPDATE|DELETE|ALTER|CREATE)';
		const REGEX_ALIAS      = '([a-z_]+)';
		const REGEX_IDENTIFIER = '([a-zA-Z_.]+)';
		const REGEX_LIMIT      = 'LIMIT\s+(\d+)(?:$|\s+OFFSET\s+\d+$)';
		const REGEX_OFFSET     = 'OFFSET\s+(\d+)(?:$|\s+LIMIT\s+\d+$)';


		/**
		 *
		 */
		public function compose(Query $query)
		{
			if ($sql = $query->getSql()) {
				return $sql;
			}
		}


		/**
		 *
		 */
		public function parse(Query $query)
		{
			/*

			This need to be completed, for now we won't parse anything.

			$tokenized_query = $query->getSql();
			$position        = 0;
			$tokens          = array();

			while (preg_match_all('#(\([^\(]*\))#U', $tokenized_query, $matches)) {
				foreach ($matches[1] as $match) {
					$tokens[$position] = $match;
					$tokenized_query   = substr_replace(
						$tokenized_query,
						sprintf(static::TOKEN_FORMAT, $position),
						strpos($tokenized_query, $match),
						strlen($match)
					);

					$position++;
				}
			}

			$action = $this->parseAction($query, $tokenized_query);


			while(preg_match_all('/(\{#T-(\d+)#\})/', $string, $matches)) {
				foreach ($matches[1] as $i => $match) {
					$tnumber = (int) $matches[2][$i];
					$string  = str_replace($match, $tokens[$tnumber], $string);
				}
			}


			*/
		}


		/**
		 *
		 */
		protected function parseAction($query, $tokenized_query)
		{
			$regex = sprintf('#^%s\s+#i', static::REGEX_ACTION);

			if (!preg_match($regex, $tokenized_query, $matches)) {
				throw new SyntaxException(
					'Invalid action specified in query "%s"',
					$query->getSql()
				);
			}

			return $matches[1];
		}


		/**
		 *
		 */
		public function parseLimit()
		{
			$regex = sprintf('#\s+%s#i', $this->driver->getLimitRegex());

			if (preg_match($regex, $this->query, $matches)) {
				return $matches[1];
			}

			return NULL;
		}


		/**
		 *
		 */
		protected function parseOffset()
		{
			$regex = sprintf('#\s+%s#i', $this->driver->getOffsetRegex());

			if (preg_match($regex, $this->query, $matches)) {
				return $matches[1];
			}

			return NULL;
		}
	}
}
