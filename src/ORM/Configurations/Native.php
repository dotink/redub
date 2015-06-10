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
		 */
		protected $configuration = array();


		/**
		 *
		 */
		public function __construct(array $configuration = array())
		{
			$this->configuration = $configuration;
		}

		/**
		 *
		 */
		protected function readConfiguration()
		{
			$this->addConfiguration($this->configuration);
		}
	}
}
