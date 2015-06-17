<?php namespace Redub\Database
{
	use Iterator;
	use Countable;

	/**
	 * A normalized result interface
	 *
	 * @copyright Copyright (c) 2015 Matthew J. Sahagian, others
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 */
	interface ResultInterface extends Iterator, Countable
	{
		/**
		 * Get the row/object/data at the given index of the result
		 *
		 * @access public
		 * @param integer $index The index at which to get the data (should start from 0)
		 * @return mixed The data if it exists, NULL if it is not available
		 */
		public function get($index);
	}
}
