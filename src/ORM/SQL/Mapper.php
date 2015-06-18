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
		protected $configuration = NULL;


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
		protected $map = array();


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
			$this->query         = $query;
			$this->repository    = $this->query->getRepository();
			$table_name          = $this->configuration->getRepositoryMap($this->repository);
			$mapping             = $this->configuration->getMapping($this->repository);
			$table_alias         = $this->getTableAlias($table_name);

			$this->addMapping('tables', NULL, $this->repository);

			$arguments = $this->translateArguments($query->getArguments());
			$criteria  = $this->translateCriteria($query->getCriteria(FALSE));

			$query
				-> on([$table_name => $table_alias])
				-> with($arguments)
				-> where($criteria, TRUE)
			;

			return $query;
		}


		/**
		 *
		 */
		protected function addMapping($key, $path, $alias)
		{
			if (!isset($key)) {
				$this->map[$key] == array();
			}

			$this->map[$key][$path] = $alias;
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
			$data = array();

			foreach ($this->map['columns'] as $map => $alias) {
				$parts = explode('.', $map);
				$value = $source_data[$alias];

				foreach (array_reverse($parts) as $part) {
					$value = [$part => $value];
				}

				$data = array_replace_recursive($data, $value);
			}

			return $data;
		}


		/**
		 *
		 */
		protected function translateArguments($original_arguments)
		{
			$arguments = array();

			foreach ($original_arguments as $arg) {
				$parts = explode('.', $arg);
				$field = array_pop($parts);

				if (count($parts) && $parts[0] == $this->repository) {
					array_shift($parts);
				}

				$path = implode('.', $parts);

				if ($path && !$this->map['tables'][$path]) {
					$this->translatePath($path, $field);
				}

				$target  = $this->map['tables'][$path];
				$column  = $this->configuration->getMapping($target, $field);
				$table   = $this->configuration->getRepositoryMap($target);
				$select  = $this->tableAliases[$table] . '.' . $column;
				$alias   = $this->getColumnAlias($select);
				$mapping = $path
					? $path . '.' . $field
					: $field;

				$this->addMapping('columns', $mapping, $alias);

				$arguments[$select] = $alias;
			}

			return $arguments;
		}


		/**
		 *
		 */
		protected function translateCriteria($original_criteria)
		{
			$criteria = array();

			foreach ($original_criteria as $condition => $value) {
				if (!is_numeric($condition) || count($value) != 3) {
					$criteria[$condition] = $this->translateCriteria($value);
					continue;
				}

				$parts = explode('.', $value[0]);
				$field = array_pop($parts);

				if (count($parts) && $parts[0] == $this->repository) {
					array_shift($parts);
				}

				$path = implode('.', $parts);

				if ($path && !isset($this->map[$path])) {
					$this->translatePath($path, $field);
				}

				$target  = $this->map['tables'][$path];
				$column  = $this->configuration->getMapping($target, $field);
				$table   = $this->configuration->getRepositoryMap($target);
				$select  = $this->tableAliases[$table] . '.' . $column;

				$criteria[$condition] = [$select, $value[1], $value[2]];
			}

			return $criteria;
		}


		/**
		 *
		 */
		protected function translatePath($path, $field, $select = FALSE)
		{
			$target = $this->repository;
			$parts  = explode('.', $path);
			$source = $this->map['tables'][$path];

			foreach ($parts as $relation) {
				$route  = $this->configuration->getRoute($target, $relation);  // ['users' => ['id' => 'person']]
				$target = $this->configuration->getTarget($target, $relation); // 'Users'

				if (!$target) {
					throw new Flourish\ProgrammerException(
						'Criteria or arguments contain invalid path to field "%s" (%s)',
						$field,
						$path
					);
				}

				foreach ($route as $dest_table_name => $link) {
					$dest = $this->getTableAlias($dest_table_name);   // 'tX' - where X == 1+

					$this->query->link($dest_table_name, [$dest,
						$source . '.' . key($link) . ' =:' => // t0.id
						$dest   . '.' . current($link)        // t1.person
					]);

					$source = $dest; // set source to destination and continue
				}
			}

			$this->addMapping('tables', $path, $target); // Set the path to our final destination
		}
	}
}
