<?php namespace Redub\ORM
{
	use Redub\Database\ConnectionInterface;
	use Redub\Database\DriverInterface;

	use Dotink\Flourish;

	use ReflectionClass as Reflector;


	/**
	 *
	 */
	class Manager
	{
		const MODEL_CLASS = 'Redub\ORM\Model';

		/**
		 *
		 */
		static protected $reflections = array();


		/**
		 *
		 */
		static protected $reflector = NULL;


		/**
		 *
		 */
		protected $bindings = array();


		/**
		 *
		 */
		protected $configuration = NULL;


		/**
		 *
		 */
		protected $configurationExpiration = 0;


		/**
		 *
		 */
		protected $connections = array();


		/**
		 *
		 */
		protected $mappers = array();


		/**
		 *
		 */
		static protected function getReflection($class)
		{
			if (!isset(static::$reflections[$class])) {
				static::$reflections[$class] = new Reflector($class);
			}

			return static::$reflections[$class];
		}


		/**
		 *
		 */
		public function __construct(ConfigurationInterface $configuration = NULL, Cache $cache = NULL)
		{
			if (!static::$reflector) {
				static::$reflector = $this->getReflection(static::MODEL_CLASS)->getProperty('data');
			}

			$this->configuration = $configuration ?: new Configuration\Native();
			$this->cache         = $cache;

			static::$reflector->setAccessible(TRUE);
		}


		/**
		 *
		 */
		public function bind($alias, $object)
		{
			if ($object instanceof MapperInterface) {
				$object->setConfiguration($this->getConfiguration());
				$object->setData(static::$reflector);
			}

			$this->bindings[$alias] = $object;
		}


		/**
		 *
		 */
		public function connect(ConnectionInterface $connection, $ns = '')
		{
			$ns     = trim($ns, '\\');
			$alias  = $connection->getAlias();

			if (isset($this->connections[$ns])) {
				throw new Flourish\ProgrammerException(
					'Connection "%s" has already been configured for namespace "%s"',
					$this->connections[$ns]->getAlias(),
					$ns
				);
			}

			if (array_search($connection, $this->connections) === FALSE) {
				$driver = $connection->getConfig('driver');
				$mapper = $connection->getConfig('mapper');

				//
				// TODO: make sure the driver and mapper are actually bound
				//

				$this->mappers[$alias]  = $this->bindings[$mapper];
				$this->connections[$ns] = $connection;

				$connection->setDriver($this->bindings[$driver]);
			}
		}


		/**
		 *
		 */
		public function create($repository, $params = array())
		{
			$entity = $this->initializeEntity('create', $repository);
			$mapper = $this->getMapper($entity);

			$mapper->loadEntityDefaults($entity);

			if (is_callable([$entity, '__construct'])) {
				$entity->__construct(...$params);
			}

			return $entity;
		}


		/**
		 *
		 */
		public function getConfiguration()
		{
			if ($this->cache) {
				// TODO: Implement caching
				// - check global key for expiration time and set to configuration expiration time
				// - if expiration time is in future call $this->configuration->load($cache);

			} elseif ($this->configurationExpiration <= time()) {
				$this->configurationExpiration = $this->configuration->read($this);

			}

			return $this->configuration;
		}


		/**
		 *
		 */
		public function getConnection($class)
		{
			$namespace = $this->getReflection($class)->getNamespaceName();

			if (!$this->connections[$namespace]) {
				throw new Flourish\ProgrammerException(
					'Could not find connection for namespace "%s"',
					$namespace ?: '\\'
				);
			}

			return $this->connections[$namespace];
		}


		/**
		 * Gets the appropriate mapper for a given class (based on namespace bindings)
		 *
		 * @return Mapper The mapper for the class
		 *
		 */
		public function getMapper($entity)
		{
			$model      = get_class($entity);
			$connection = $this->getConnection($model);

			//
			// TODO: see if mapper actually exists for that alias
			//

			return $this->mappers[$connection->getAlias()];
		}


		/**
		 * Gets the model for a repository
		 *
		 * @access public
		 * @param Repository $repository The repository for which to get the model
		 * @return string The model for the repository
		 */
		public function getRepositoryModel($repository)
		{
			$model = $this->getConfiguration()->getModel($repository);

			if (!$model) {
				return NULL;
			}

			if (class_exists($model)) {
				return $model;
			}

			//
			// TODO: Attempt to auto scaffold model
			//
		}


		/**
		 *
		 * @param string $repository The repository class
		 */
		public function loadEntity($repository, $key, $return_empty)
		{
			$connection = $this->getConnection($repository);
			$entity     = $this->initializeEntity('load', $repository);
			$mapper     = $this->getMapper($entity);

			$mapper->loadEntityDefaults($entity);

			if (!$mapper->loadEntityFromKey($connection, $entity, $key)) {

			}

			return $entity;
		}


		/**
		 * Initializes an entity (but does not construct) by using a model's reflection class
		 *
		 * @access protected
		 * @param Reflector $reflection The reflection for the model
		 * @return Model The entity instance prior to constructor execution
		 */
		protected function initializeEntity($action, $repository)
		{
			$model = $this->getRepositoryModel($repository);

			if (!$model) {
				throw new Flourish\ProgrammerException(
					'Model class for repository "%s" is not configured, could not %s entity',
					get_class($repository),
					$action
				);
			}

			return $this->getReflection($model)->newInstanceWithoutConstructor();
		}
	}
}
