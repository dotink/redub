<?php namespace Redub\ORM\convention
{
	use ICanBoogie\Inflector;

	/**
	 *
	 */
	class Redub
	{
		public function __construct(Inflector $inflector = NULL)
		{
			$this->inflector = $inflector ?: Inflector::get();
		}
	}
}
