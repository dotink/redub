<?php namespace Redub\ORM\SQL
{
	use Redub\ORM;
	use Redub\Database;
	use Dotink\Flourish;

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
			$model      = get_class($entity);
			$repository = $this->configuration->getRepository($model);
			$defaults   = $this->configuration->getDefaults($repository);

			$this->data->setValue($entity, $defaults);

			return TRUE;
		}


		/**
		 *
		 */
		public function loadEntityFromKey($connection, $entity, $key)
		{
			$model      = get_class($entity);
			$repository = $this->configuration->getRepository($model);
			$identity   = $this->configuration->getIdentity($repository);
			$criteria   = $this->makeKeyCriteria($repository, $key);
			$mapping    = $this->getMapping($repository);
			$table      = $this->getTable($repository);

			if (!$criteria) {
				return FALSE; // Key did not match a surrogate ID or a unique constraint, error
			}

			// TODO: Figure out if we're in the identity map

			$this->begin();

			$this->makeColumnAliases($mapping);
			$this->makeTableAlias($table);

			$result = $connection->execute(function($query) use ($criteria) {
				return $query
					-> perform('select', $this->columnAliases)
					-> on(reset($this->tableAliases))
					-> using($criteria);
			});

			if ($result->count() == 1) {
				$this->data->setValue($entity, $this->reduce($mapping, $result->get(0)));

				return $entity;
			}

			return $result->count() > 1
				? FALSE // Return on error, i.e. we somehow got more than one result
				: NULL; // Return on not found, got 0 results
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
		protected function getTable($repository)
		{
			return $this->configuration->getRepositoryMap($repository);
		}

		/**
		 *
		 */
		protected function getMapping($repository)
		{
			return $this->configuration->getMapping($repository);
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
		protected function makeKeyCriteria($repository, $key)
		{
			$criteria    = new Database\Criteria();
			$identity    = $this->configuration->getIdentity($repository);
			$uniques     = $this->configuration->getUniqueConstraints($repository);
			$constraints = [$identity] + $uniques;

			if (!is_array($key)) {
				$key = count($identity) == 1
					? [$identity[0] => $key]
					: array();
			}

			foreach ($constraints as $constraint) {
				if (!count(array_diff($constraint, array_keys($key)))) {
					return $criteria->where($this->makeMappedConditions($repository, $key));
				}
			}

			return NULL;
		}


		/**
		 *
		 */
		protected function makeMappedConditions($repository, $key)
		{
			$mapping    = $this->configuration->getMapping($repository);
			$conditions = array();

			foreach ($key as $field => $value) {
				if (!isset($mapping[$field])) {
					throw new Flourish\ProgrammerException(
						'Unknown field "%s" used in criteria or query conditions',
						$field
					);
				}

				$conditions[$mapping[$field] . ' =='] = $value;
			}

			return $conditions;
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
			if (!$lookup) {
				$lookup = $this->columnAliases;
			}

			$data = array();
			$fill = array_flip($mapping);

			foreach ($lookup as $column => $alias) {
				$data[$fill[$column]] = $values[$alias];
			}

			return $data;
		}
	}
}
