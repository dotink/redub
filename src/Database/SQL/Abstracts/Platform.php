<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	abstract class Platform implements Database\PlatformInterface
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
		public function compose(Database\Query $query)
		{
			if ($sql = $query->getSql()) {
				return $sql;
			}
		}


		/**
		 *
		 */
		public function parse(Database\Query $query)
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
	}
}
