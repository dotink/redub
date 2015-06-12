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
		public function connect(ConnectionInterface $connection)
		{
			$alias       = $connection->getAlias();
			$config      = $this->getConfiguration();
			$connections = $config->find('connections');

			if (!isset($connections[$alias])) {
				throw new Flourish\ProgrammerException(
					'Cannot connect "%s", configuration does not exist',
					$alias
				);
			}

			if (!isset($connections[$alias]['driver'])) {
				throw new Flourish\ProgrammerException(
					'Cannot connect "%s", no driver specified',
					$alias
				);
			}

			if (isset($connections[$alias]['mapper'])) {
				$mapper = $config->getBinding($connections[$alias]['mapper']);

				if (!$mapper) {
					throw new Flourish\ProgrammerException(
						'Invalid mapper "%s" bound to configuration "%s"',
						$config['mapper'],
						$alias
					);
				}

				if (!in_array($mapper, $this->mappers)) {
					$mapper->setData(static::$reflector);
					$mapper->setConfiguration($config);
				}

				$this->mappers[$alias] = $mapper;
			}

			$driver = $config->getBinding($connections[$alias]['driver']);

			if (!$driver) {
				throw new Flourish\ProgrammerException(
					'Invalid driver "%s" bound to configuration "%s"',
					$connections[$alias]['driver'],
					$alias
				);
			}

			$connection->setDriver($driver);
			$this->register($connection, isset($connections[$alias]['namespaces'])
				? $connections[$alias]['namespaces']
				: ['\\']
			);
		}


		/**
		 *
		 */
		public function create($repository, $params = array())
		{
			$entity = $this->initializeEntity('create', $repository);
			$mapper = $this->getMapper($repository);

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
				$this->configurationExpiration = $this->configuration->read();

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
		public function getMapper($repository)
		{
			$connection = $this->getConnection($repository);

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
			$mapper     = $this->getMapper($repository);

			$mapper->loadEntityDefaults($entity);

			if (!($result = $mapper->loadEntityFromKey($connection, $entity, $key))) {
				if ($result === FALSE) {
					throw new Flourish\ProgrammerException(
						'Invalid key specified, does not constitute a unique constraint'
					);
				}

				return $return_empty
					? $entity
					: NULL;
			}

			return $result;
		}


		/**
		 *
		 */
		public function register(ConnectionInterface $connection, $namespaces)
		{
			settype($namespaces, 'array');

			foreach ($namespaces as $namespace) {
				$canonical_namespace = trim($namespace, '\\');

				if (isset($this->connections[$canonical_namespace])) {
					throw new Flourish\ProgrammerException(
						'Cannot register connection "%s" for namespace "%s", already registered',
						$namespace
					);
				}

				$this->connections[$canonical_namespace] = $connection;
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
			$model    = $this->getRepositoryModel($repository);
			$instance = $this->getReflection($model)->newInstanceWithoutConstructor();

			return $instance;
		}
	}
}
