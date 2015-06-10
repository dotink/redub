<?php namespace Redub\Database\SQL
{
	/**
	 *
	 */
	class Pgsql extends AbstractPlatform
	{
		const PLATFORM_NAME = 'Pgsql';

		/**
		 * Quotes a string.  This does not do any additional escaping of existing quotes
		 *
		 * @static
		 * @access protected
		 * @param string $string The string to quote
		 * @return string The quoted string
		 */
		static protected function quote($string)
		{
			return '"' . $string . '"';
		}


		/**
		 *
		 */
		static public function escapeIdentifier($name, $prepared = FALSE)
		{
			if ($prepared) {
				return $name;
			}

			$parts = explode('.', $name);
			$parts = array_map([__CLASS__, 'quote'], $parts);

			return implode('.', $parts);
		}
	}
}
