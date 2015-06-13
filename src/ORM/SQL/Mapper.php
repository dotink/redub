<?php namespace Redub\ORM\SQL
{
	use Redub\ORM;
	use Redub\Database;
	use Dotink\Flourish;
	use Redub\Database\Query;

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
			$mapping    = $this->configuration->getMapping($repository);
			$criteria   = $this->makeKeyCriteria($repository, $key);

			if (!$criteria) {
				return FALSE; // Key did not match a surrogate ID or a unique constraint, error
			}

			$result = $connection->execute(
				function($query, $repository, $criteria, $mapping) {
					$this->translate($query
						-> perform('select', array_keys($mapping))
						-> on($repository)
						-> using($criteria)
					);
				},
				$repository,
				$criteria,
				$mapping
			);

			if ($result->count() == 1) {
				$this->data->setValue($entity, $this->reduce($result->get(0)));

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
		public function translate(Query $query)
		{
			$this->map           = array();
			$this->tableAliases  = array();
			$this->columnAliases = array();
			$repository          = $query->getRepository();
			$table_name          = $this->configuration->getRepositoryMap($repository);
			$mapping             = $this->configuration->getMapping($repository);

			$query->setRepository([$table_name => $this->getTableAlias($table_name)]);
			$this->addMapping($repository, $this->getTablealias($table_name));

			$this->translateArguments($repository, $query);
			$this->translateCriteria($repository, $query);

			return $query;
		}


		/**
		 *
		 */
		protected function addMapping($path, $alias)
		{
			$this->map[$path] = $alias;
		}


		/**
		 *
		 */
		protected function getColumnAlias($column_name)
		{
			if (!isset($this->columnAliases[$column_name])) {
				$this->columnAliases[$column_name] = 'c' . count($this->columnAliases);
			}

			return $this->columnAliases[$column_name];

		}


		/**
		 *
		 */
		protected function getTableAlias($table_name)
		{
			if (!isset($this->tableAliases[$table_name])) {
				$this->tableAliases[$table_name] = 't' . count($this->tableAliases);
			}

			return $this->tableAliases[$table_name];
		}


		/**
		 *
		 */
		protected function makeKeyConditions($repository, $key)
		{
			$mapping    = $this->configuration->getMapping($repository);
			$conditions = array();

			foreach ($key as $field => $value) {
				$conditions[$field . ' =='] = $value;
			}

			return $conditions;
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
					return $criteria->where($this->makeKeyConditions($repository, $key));
				}
			}

			return NULL;
		}


		/**
		 *
		 */
		protected function reduce($source_data)
		{
			$data       = array();
			$lookup     = array_flip($this->map);
			$repository = reset($lookup);
			$mapping    = $this->configuration->getMapping($repository);

			foreach ($lookup as $alias => $map) {
				$parts = explode('.', $map);
				$value = $alias[0] == 'c'
					? $source_data[$alias]
					: array();

				foreach (array_reverse($parts) as $part) {
					$value = [$part => $value];
				}

				$data = array_replace_recursive($data, $value);
			}

			return $data[$repository];
		}


		/**
		 *
		 */
		protected function translateArguments($repository, Query $query)
		{
			$arguments = array();

			foreach ($query->getArguments() as $argument) {
				$parts = explode('.', $argument);
				$field = array_pop($parts);

				if (!count($parts) || $parts[0] != $repository) {
					array_unshift($parts, $repository);
				}

				$path   = implode('.', $parts);
				$column = $this->translatePath($path, $field, $query);

				if (!$column) {
					//
					// blow shit up
					//
				}

				$cpath = $path . '.' . $field;
				$alias = $this->getColumnAlias($cpath);

				$this->addMapping($cpath, $alias);

				$arguments[$this->map[$path] . '.' . $column] = $alias;
			}

			$query->setArguments($arguments);
		}


		/**
		 *
		 */
		protected function translateCriteria($repository, Query $query)
		{
			$criteria = $query->getCriteria();

			foreach ($criteria as $i => $conditions) {
				if (!is_array($conditions)) {
					continue;
				}

				foreach ($conditions as $j => $condition) {
					$parts  = explode('.', $condition[0]);
					$field  = array_pop($parts);

					if (!count($parts) || $parts[0] != $repository) {
						array_unshift($parts, $repository);
					}

					$path   = implode('.', $parts);
					$column = $this->translatePath($path, $field, $query);

					if (!$column) {
						//
						// Throw a fit
						//
					}

					$criteria[$i][$j][0] = $this->map[$path] . '.' . $column;
				}
			}

			$query->setCriteria($criteria);
		}


		/**
		 *
		 */
		protected function translatePath($path, $field, Query $query)
		{
			$parts  = explode('.', $path);
			$target = array_shift($parts);
			$src    = $this->map[$target];

			if (!isset($this->map[$path])) {
				foreach ($parts as $relation) {
					$target  = $this->configuration->getTarget($target, $relation); // 'Users'
					$route   = $this->configuration->getRoute($target, $relation);  // ['users' => ['id' => 'person']]

					if (!$target) {
						throw new Flourish\ProgrammerException(
							'Criteria or arguments contain invalid path to field "%s" (%s)',
							$field,
							$path
						);
					}

					foreach ($route as $dest_table_name => $link) {
						$dest = $this->getTableAlias($dest_table_name);   // 'tX' - where X == 1+

						$query->link($table_name, [$alias => [
							$src  . '.' . key($link), // t0.id
							$dest . '.' . current($link) // t1.person
						]]);

						$src = $dest; // set source to destination and continue
					}
				}

				$this->addMapping($path, $dest); // Set the path to our final destination
			}

			return $this->configuration->getMapping($target, $field);
		}
	}
}
