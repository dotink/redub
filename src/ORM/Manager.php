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
		protected $builder = NULL;


		/**
		 *
		 */
		protected $cache = NULL;


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
		public function __construct(ConfigurationInterface $config = NULL, Builder $builder = NULL)
		{
			if (!static::$reflector) {
				static::$reflector = $this->getReflection(static::MODEL_CLASS)->getProperty('data');
			}

			$this->builder       = $builder ?: new Builder();
			$this->configuration = $config  ?: new Configuration\Native();

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

				if (!$mapper || !($mapper instanceof MapperInterface)) {
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

			if (!$driver || !($driver instanceof DriverInterface)) {
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
		public function getConnection($repository)
		{
			$namespace = $this->getReflection($repository)->getNamespaceName();

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
		 * Get a completely empty entity (unconstructed) for a given respository
		 *
		 * @access public
		 * @param string $repository The repository class
		 * @return Model The entity instance prior to constructor execution, with no data
		 */
		public function getEntity($repository)
		{
			$model      = $this->resolve($repository);
			$reflection = $this->getReflection($model);

			return $reflection->newInstanceWithoutConstructor();
		}


		/**
		 * Register a connection to one or more namespaces
		 *
		 * @access protected
		 * @param ConnectionInterface $connection The connection to register
		 * @param string|array The namespace(s) with which to register the connection
		 * @return void
		 */
		protected function register(ConnectionInterface $connection, $namespaces)
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
		 * Resolve the model class for a given repository
		 *
		 * @access public
		 * @param string $repository The repository class
		 * @return string The model class for the repository
		 */
		protected function resolve($repository)
		{
			$config = $this->getConfiguration();
			$model  = $config->getModel($repository);
			$base   = 'Redub\\Base\\' . $config->getModel($repository);

			if (!class_exists($base) && $this->builder) {
				$base_path = $config->find('basePath', 'redub-' . md5(__DIR__));
				$full_path = $this->builder->getPath($base, $base_path);

				if (!file_exists($full_path) || filemtime($full_path) < strtotime('-1 second')) {
					$this->builder->build($config, $repository, $base, $full_path);
				}

				include($full_path);

				if (!class_exists($model)) {
					$class_parts = explode('\\', $model);
					$class_name  = array_pop($class_parts);
					$namespace   = implode('\\', $class_parts);
					$code        = "namespace $namespace { class $class_name extends $base {} }";

					eval($code);

					return $model;
				}
			}

			if (class_exists($model)) {
				return $model;
			}

			throw new Flourish\ProgrammerException(
				'Could not resolve model class for repository "%s", class "%s" not found',
				$repository,
				$model
			);
		}
	}
}
