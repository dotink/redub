<?php namespace Redub\ORM\Configuration
{
	use Redub\ORM;
	use Dotink\Flourish;
	use Dotink\Jin\Parser;

	/**
	 *
	 */
	class Native extends ORM\AbstractConfiguration
	{
		/**
		 *
		 * @return integer The new expiration time
		 */
		public function read(array $configuration = array())
		{
			$this->addConfiguration($configuration);

			return time() + (60 * 15);
		}
	}
}
