<?php namespace Redub\ORM\SQL
{
	use Redub\ORM;

	class Mapper implements ORM\MapperInterface
	{
		/**
		 *
		 */
		public function setDriver($driver)
		{
			$this->driver = $driver;
		}


		/**
		 *
		 */
		public function setManager($manager)
		{
			$this->manager = $manager;
		}


		/**
		 *
		 */
		public function loadDefaultValues($class, $entity, $data)
		{
			$configuration  = $this->manager->getConfiguration();
			$default_values = $configuration->getDefaults($class);

			$data->setValue($entity, $default_values);
		}
	}
}
