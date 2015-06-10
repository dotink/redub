<?php namespace Redub\ORM\SQL
{
	use Redub\Database\Query;
	use Redub\ORM;

	class Mapper implements ORM\MapperInterface
	{

		protected $columnAliases = array();

		protected $tableAliases = array();


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
		public function loadDefaultValues($entity, $data)
		{
			$model    = get_class($entity);
			$config   = $this->manager->getConfiguration();
			$defaults = $config->getDefaults($model);

			$data->setValue($entity, $defaults);

			return TRUE;
		}


		/**
		 *
		 */
		public function loadEntityFromKey($entity, $key, $data)
		{
			$model = get_class($entity);

			$this->validateKey($model, $key);
			$this->begin();

			$query      = new Query();
			$config     = $this->manager->getConfiguration();
			$connection = $this->manager->getConnection($model);

			$mapping    = $config->getMapping($model);
			$identity   = $config->getIdentity($model);
			$repository = $config->getRepository($model);
			$collection = $config->getCollection($repository);

			$aliases    = $this->makeColumnAliases($mapping);

			$query
				-> perform('select', $aliases)
				-> on([$collection => $this->makeTableAlias($collection)])
				-> where($identity . ' ==', $key);

			$entity_data = $data->getValue($entity);
			$query_data  = $connection->execute($query)->get(0);
			$lookup   = array_flip($aliases);
			$reverse  = array_flip($mapping);

			foreach ($query_data as $alias => $value) {
				if (isset($reverse[$lookup[$alias]])) {
					$entity_data[$reverse[$lookup[$alias]]] = $value;
				}
			}

			var_dump($entity_data);
		}



		protected function begin()
		{
			$this->tableAliases  = array();
			$this->columnAliases = array();
		}

		/**
		 *
		 */
		protected function makeColumnAlias($column)
		{
			return $this->columnAliases[$column] = 'c' . count($this->columnAliases);
		}


		/**
		 *
		 */
		protected function makeColumnAliases($columns)
		{
			return array_combine($columns, array_map([$this, 'makeColumnAlias'], $columns));
		}


		/**
		 *
		 */
		protected function makeTableAlias($table)
		{
			return $this->tableAliases[$table] = 't' . count($this->tableAliases);
		}


		/**
		 *
		 */
		protected function validateKey($model, $key)
		{

		}
	}
}
