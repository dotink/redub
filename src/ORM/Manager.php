<?php namespace Redub\ORM
{
	use Dotink\Flourish;
	use Redub\Database;

	use ReflectionClass;

	class Manager
	{
		const MODEL_CLASS  = 'Redub\ORM\Model';
		const MAPPER_CLASS = 'Redub\ORM\Mapper';

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
			$reflection_data      = $reflection_class->getProperty('data');
			$this->reflectionData = $reflection_data;
			$this->configuration  = $configuration;
			$this->cache          = $cache;

			$reflection_data->setAccessible(TRUE);
		}


		/**
		 *
		 */
		public function bind($alias, Database\DriverInterface $driver, MapperInterface $mapper)
		{
			$this->mappers[$alias] = $mapper;
			$this->drivers[$alias] = $driver;
		}


		/**
		 *
		 */
		public function connect(Database\ConnectionInterface $connection, $namespace, $binding = NULL)
		{
			$binding   = $connection->getConfig('binding', $binding);
			$namespace = trim($namespace, '\\');

			if (isset($this->connections[$namespace])) {
				throw new Flourish\ProgrammerException(
					'Connection "%s" has already been configured for namespace "%s"',
					$this->connections[$namespace]->getAlias(),
					$namespace
				);

			} elseif (!isset($this->drivers[$binding])) {
				throw new Flourish\ProgrammerException(
					'Connection "%s" could not be registered, "%s" is not a valid binding',
					$binding
				);

			} else {
				$connection->setDriver($this->drivers[$binding]);
			}
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

			$reflection_class = $this->getReflectionClass($class);
			$default_values   = $this->getConfiguration()->getDefaults($class);
			$model_instance   = $reflection_class->newInstanceWithoutConstructor();

			$this->reflectionData->setValue($model_instance, $default_values);

			if ($reflection_class->hasMethod('__construct')) {
				$model_instance->__construct(...$params);
			}

			return $model_instance;
		}


		/**
		 *
		 */
		public function load($class, $key, $return_empty)
		{
			$model_instance = class_exists($class)
				? $this->loadFromIdentityMap($class, $key)
				: $this->create($class);

			// get driver for the class
			// prepare a query
			// create a new mapper, pass it the configuration, driver, query, class
			//
			// check if model is new instance, if so, load data from some place
			//

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
		protected function getConfiguration()
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


		/**
		 *
		 */
		protected function loadFromIdentityMap($class, $key)
		{
			//
			// Look in the identity map and if it's not here.
			// create a new one and store it with the key.
			//

			return $this->create($class);
		}
	}
}
