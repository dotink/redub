<?php namespace Redub\ORM
{
	use Dotink\Flourish;

	abstract class Repository
	{
		/**
		 *
		 */
		static protected $entityName = NULL;


		/**
		 *
		 */
		protected $manager = NULL;


		/**
		 *
		 */
		public function __construct(Manager $manager = NULL, Criteria $criteria = NULL)
		{
			$this->manager  = $manager  ?: new Manager();
			$this->criteria = $criteria ?: new Criteria();
		}


		/**
		 *
		 */
		public function build(callable $builder)
		{
			$criteria = clone $this->criteria;

			$builder($criteria);

			return $this->manager->createCollection(
				$this->getEntityName(),
				$criteria
			);
		}


		/**
		 *
		 */
		public function create(...$params)
		{
			return $this->manager->create($this->getEntityName(), $params);
		}


		/**
		 * Fetches a collection of objects based on an aggregate list of aliased builders
		 *
		 * @param string|array $build_aliases The build aliases with which to aggregate criteria
		 * @param integer $limit The limit to place on the number of entities in the collection
		 * @param array $order The order for the entities in the collection
		 * @return Collection The collection containing all matching entities
		 */
		public function fetch($build_aliases, $order = array(), $limit = NULL, $page = 1)
		{
			settype($build_aliases, 'array');

			$criteria = clone $this->criteria;

			foreach ($build_aliases as $build_alias_name) {
				$build_method = 'build' . ucfirst($build_alias_name);
				$builder      = [$this, $build_method];

				if (!is_callable($builder)) {
					throw new Flourish\ProgrammerException(
						'Cannot fetch "%s", build method "%s" is not available',
						$build_alias_name,
						$build_method
					);
				}

				$builder($criteria);
			}

			$criteria->order($order);
			$criteria->limit($limit);
			$criteria->page($page);

			return $this->manager->createCollection(
				$this->getEntityName(),
				$criteria
			);
		}


		/**
		 * Find an entity by primary key or unique criteria
		 *
		 * @access public
		 * @param mixed A scalar parameter matching the primary key, or a keyed array
		 * @param boolean $create_empty Whether or not to create a new entity if not found
		 * @return object The entity if it can be found, NULL otherwise
		 */
		public function find($key, $create_empty = FALSE)
		{
			$entity = $this->manager->find($this->getEntityName(), $key);

			if (!$entity && $create_empty) {
				return $this->create();
			}

			return $entity;
		}


		/**
		 * Inserts an entity into the repository
		 *
		 * @access public
		 * @param object $entity The entity to insert
		 * @return boolean TRUE if the entity can be inserted, FALSE otherwise
		 */
		public function insert($entity)
		{
			return $this->manager->insert($entity);
		}


		/**
		 * Gets the entity name for this repository
		 *
		 * @access public
		 * @return string The entity name (class) which this repository handles
		 */
		public function getEntityName()
		{
			return static::$entityName ?: $this->failEntityName();
		}


		/**
		 * Removes an entity from the repository
		 *
		 * @access public
		 * @param object $entity The entity to remove
		 * @return boolean TRUE if the object can be removed, FALSE otherwise
		 */
		public function remove(object $entity)
		{
			return $this->manager->remove($entity);
		}


		/**
		 * Fails when no entity name is configured
		 *
		 * @access private
		 * @return void
		 * @throws Flourish\ProgrammerException when the entity name is not configured
		 */
		private function failEntityName()
		{
			throw new Flourish\ProgrammerException(
				'Cannot initialize "%s", entity name is not set',
				get_called_class()
			);
		}
	}
}
