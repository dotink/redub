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
		const MODEL_CLASS  = 'Redub\ORM\Model';
		const MAPPER_CLASS = 'Redub\ORM\Mapper';

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
		protected $drivers = array();


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

				if (!static::$reflections[$class]->isSubclassOf(static::MODEL_CLASS)) {
					throw new Flourish\ProgrammerException(
						'Cannot reflect non-child of "%s", the class "%s" is not a model',
						static::MODEL_CLASS,
						$class
					);
				}
			}

			return static::$reflections[$class];
		}


		/**
		 *
		 */
		public function __construct(Configuration $configuration = NULL, Cache $cache = NULL)
		{
			if (!static::$reflector) {
				static::$reflector = (new Reflector(static::MODEL_CLASS))->getProperty('data');
				static::$reflector->setAccessible(TRUE);
			}

			$this->configuration = $configuration ?: new Configuration\Native();
			$this->cache         = $cache;
		}


		/**
		 *
		 */
		public function bind($alias, DriverInterface $driver, MapperInterface $mapper)
		{
			$mapper->setDriver($driver);
			$mapper->setManager($this);

			$this->bindings[$alias] = [
				'driver' => $driver,
				'mapper' => $mapper
			];
		}


		/**
		 *
		 */
		public function connect(ConnectionInterface $connection, $namespace = '')
		{
			$binding   = $connection->getConfig('binding');
			$namespace = trim($namespace, '\\');

			if (isset($this->connections[$namespace])) {
				throw new Flourish\ProgrammerException(
					'Connection "%s" has already been configured for namespace "%s"',
					$this->connections[$namespace]->getAlias(),
					$namespace
				);
			}

			if (!$connection->hasDriver()) {
				if (!isset($this->bindings[$binding])) {
					throw new Flourish\ProgrammerException(
						'Connection "%s" could not be registered, "%s" is not a valid binding',
						$binding
					);
				}

				$connection->setDriver($this->bindings[$binding]['driver']);
			}

			$this->connections[$namespace] = $connection;
		}


		/**
		 *
		 */
		public function create($repository, $params = array())
		{
			$entity = $this->initializeEntity('create', $repository);
			$mapper = $this->getMapper(get_class($entity));

			$mapper->loadDefaultValues($entity, static::$reflector);

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
		public function getMapper($class)
		{
			if (!isset($this->mappers[$class])) {
				$connection_binding    = $this->getConnection($class)->getConfig('binding');
				$this->mappers[$class] = $this->bindings[$connection_binding]['mapper'];
			}

			return $this->mappers[$class];
		}


		/**
		 * Gets the model for a repository
		 *
		 * @access public
		 * @param Repository $repository The repository for which to get the model
		 * @return string The model for the repository
		 */
		public function getModel($repository)
		{
			$repo_class  = get_class($repository);
			$model_class = $this->getConfiguration()->getModel($repo_class);

			if ($model_class) {
				return $model_class;
			}

			//
			// TODO: Attempt to auto scaffold model
			//
		}


		/**
		 *
		 */
		public function loadCollection($repository, $criteria)
		{

		}


		/**
		 *
		 */
		public function loadEntity($repository, $key, $return_empty)
		{
			$entity = $this->initializeEntity('load', $repository);
			$mapper = $this->getMapper(get_class($entity));

			if (!$mapper->loadEntityFromKey($entity, $key, static::$reflector)) {

			}
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
			$model = $this->getModel($repository);

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
