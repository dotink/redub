<?php namespace Redub\ORM\SQL
{
	use Redub\ORM;
	use Redub\Database;

	class Mapper implements ORM\MapperInterface
	{
		/**
		 *
		 */
		protected $columnAliases = array();


		/**
		 *
		 */
		protected $data = NULL;


		/**
		 *
		 */
		protected $tableAliases = array();


		/**
		 *
		 */
		protected $configuration = NULL;


		/**
		 *
		 */
		public function loadEntityDefaults($entity)
		{
			$model = get_class($entity);

			$this->data->setValue($entity, $this->configuration->getDefaults($model));

			return TRUE;
		}


		/**
		 *
		 */
		public function loadEntityFromKey($connection, $entity, $key)
		{
			$this->begin();

			$model    = get_class($entity);
			$mapping  = $this->getMapping($model);
			$table    = $this->getTable($model);
			$criteria = $this->makeKeyCriteria($model, $key);

			$this->makeColumnAliases($mapping);
			$this->makeTableAlias($table);

			$result = $connection->execute(function($query) use ($criteria) {
				return $query
					-> perform('select', $this->columnAliases)
					-> on(reset($this->tableAliases))
					-> using($criteria);
			})->get(0);

			$this->data->setValue($entity, $this->reduce($mapping, $result));

			return TRUE;
		}


		/**
		 *
		 */
		public function setConfiguration($configuration)
		{
			$this->configuration = $configuration;
		}


		/**
		 *
		 */
		public function setData($data)
		{
			$this->data = $data;
		}


		/**
		 *
		 */
		protected function begin()
		{
			$this->tableAliases  = array();
			$this->columnAliases = array();
		}

		/**
		 *
		 */
		protected function getTable($model)
		{
			return $this->configuration->getRepositoryMap(
				$this->configuration->getRepository($model)
			);
		}

		/**
		 *
		 */
		protected function getMapping($model)
		{
			return $this->configuration->getMapping($model);
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
		protected function makeKeyCriteria($model, $key)
		{
			$criteria = new Database\Criteria();

			$criteria->where('id ==', $key);

			return $criteria;
		}


		/**
		 *
		 */
		protected function makeTableAlias($table)
		{
			return $this->tableAliases[] = [$table => 't' . count($this->tableAliases)];
		}


		/**
		 *
		 */
		protected function reduce($mapping, $values, $lookup = NULL)
		{
			$data = array();

			if (!$lookup) {
				$lookup = $this->columnAliases;
			}

			foreach (array_keys($mapping) as $field) {
				$data[$field] = $values[$lookup[$mapping[$field]]];
			}

			return $data;
		}
	}
}
