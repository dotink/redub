<?php namespace Redub\ORM
{
	use Redub\Database\Criteria;
	use Dotink\Flourish;

	abstract class Repository
	{
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
			$this->class    = get_class($this);
		}


		/**
		 *
		 */
		public function build(callable $builder, $order = array(), $limit = NULL, $page = 1)
		{
			$criteria = clone $this->criteria;

			$builder($criteria);

			return $this->manager->loadCollection($this->class, $criteria, $order, $limit, $page);
		}


		/**
		 * Create a new entity
		 *
		 */
		public function create(...$params)
		{
			$class  = get_called_class();
			$entity = $this->manager->getEntity($class);
			$mapper = $this->manager->getMapper($class);

			$mapper->loadEntityDefaults($entity);

			if (is_callable([$entity, '__construct'])) {
				$entity->__construct(...$params);
			}

			return $entity;
		}


		/**
		 * Fetches a collection of objects based on an aggregate list of aliased builders
		 *
		 */
		public function fetch($build_methods, $order = array(), $limit = NULL, $page = 1)
		{
			$criteria = clone $this->criteria;

			settype($build_methods, 'array');

			foreach ($build_method as $build_method) {
				$builder = [$this, $build_method];

				if (!is_callable($builder)) {
					throw new Flourish\ProgrammerException(
						'Cannot fetch perform fetch, build method "%s" is not available',
						$build_method
					);
				}

				$builder($criteria);
			}

			return $this->manager->loadCollection($this->class, $criteria, $order, $limit, $page);
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
			$class      = get_called_class();
			$entity     = $this->manager->getEntity($class);
			$mapper     = $this->manager->getMapper($class);
			$connection = $this->manager->getConnection($class);
			$result     = $mapper->loadEntityFromKey($connection, $entity, $key);

			if ($result === FALSE) {
				//
				// If the result is FALSE it means we actually got more than one back
				//

				throw new Flourish\ProgrammerException(
					'Invalid key specified, does not constitute a unique constraint'
				);

			}

			return ($result === NULL && $create_empty)
				? $this->create()
				: $result;
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
	}
}
