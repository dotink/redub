<?php namespace Redub\ORM
{
	use Redub\Database\ConnectionInterface;
	use Redub\Database\DriverInterface;

	use Dotink\Flourish;

	use ReflectionClass;


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
		protected $reflectionClasses = array();


		/**
		 *
		 */
		protected $reflectionData = NULL;


		/**
		 *
		 */
		public function __construct(Configuration $configuration, Cache $cache = NULL)
		{
			$reflection_class     = new ReflectionClass(static::MODEL_CLASS);
			$this->reflectionData = $reflection_class->getProperty('data');
			$this->configuration  = $configuration;
			$this->cache          = $cache;

			$this->reflectionData->setAccessible(TRUE);
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
		public function create($class, $params = array())
		{
			if (!class_exists($class)) {
				throw new Flourish\ProgrammerException(
					'Unable to create instance of %s, class does not exist',
					$class
				);
			}

			$mapper     = $this->getMapper($class);
			$reflection = $this->getReflectionClass($class);
			$entity     = $reflection->newInstanceWithoutConstructor();

			$mapper->loadDefaultValues($class, $entity, $this->reflectionData);

			if ($reflection->hasMethod('__construct')) {
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
		public function getMapper($class)
		{
			if (!isset($this->mappers[$class])) {
				$namespace = $this->getReflectionClass($class)->getNamespaceName();

				if (!$this->connections[$namespace]) {
					throw new Flourish\ProgrammerException(
						'Could not find connection for namespace "%s"',
						$namespace ?: '\\'
					);
				}

				$this->connections[$class] = $this->connections[$namespace];
				$connection_binding        = $this->connections[$class]->getConfig('binding');
				$this->mappers[$class]     = $this->bindings[$connection_binding]['mapper'];
			}

			return $this->mappers[$class];
		}


		/**
		 *
		 */
		public function load($class, $key, $return_empty)
		{

		}


		/**
		 *
		 */
		public function loadCollection($class, $criteria)
		{

		}


		/**
		 *
		 */
		protected function getReflectionClass($class)
		{
			if (!isset($this->reflectionClasses[$class])) {
				$this->reflectionClasses[$class] = new ReflectionClass($class);
			}

			if (!$this->reflectionClasses[$class]->isSubclassOf(static::MODEL_CLASS)) {
				throw new Flourish\ProgrammerException(
					'Cannot instantiate class which does not extend model class %s for class %s',
					static::MODEL_CLASS,
					$class
				);
			}

			return $this->reflectionClasses[$class];
		}
	}
}
