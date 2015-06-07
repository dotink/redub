<?php namespace Redub\ORM
{
	use Dotink\Flourish;
	use Redub\Database;

	use ReflectionClass;

	class Manager
	{
		const MODEL_CLASS = 'Redub\ORM\Model';

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
			$reflection_class    = new ReflectionClass(static::MODEL_CLASS);
			$reflection_data     = $reflection_class->getProperty('data');

			$reflection_data->setAccessible(TRUE);

			$this->reflectionData = $reflection_data;
			$this->configuration  = $configuration;
			$this->cache          = $cache;
		}


		/**
		 *
		 */
		public function setup(Database\DriverInterface $driver, $alias)
		{
			$this->drivers[$alias] = $driver;
		}


		/**
		 *
		 */
		public function connect(Database\ConnectionInterface $connection, $namespace)
		{
			$driver_alias  = $connection->getConfig('driver');
			$connection_ns = trim($namespace, '\\');

			if (!in_array($connection, $this->connections)) {
				if (isset($this->drivers[$driver_alias])) {
					$connection->setDriver($this->drivers[$driver_alias]);

				} else {
					throw new Flourish\ProgrammerException(
						'No valid driver could be found for the connection, driver "%s"',
						$driver_alias
					);
				}
			}

			$this->connections[$connection_ns] = $connection;
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

			if (!$reflection_class->isSubclassOf(static::MODEL_CLASS)) {
				throw new Flourish\ProgrammerException(
					'Cannot instantiate class which does not extend model class %s for class %s',
					static::MODEL_CLASS,
					$class
				);
			}

			if ($reflection_class->hasMethod('__construct')) {
				$instance = $reflection_class->newInstanceWithoutConstructor();
				$instance->__construct(...$params);

			} else {
				$instance = new $class(...$params);
			}

			$this->reflectionData->setValue($instance, $this->configuration->getDefaults($class));

			return $instance;
		}


		/**
		 *
		 */
		public function getDefaultOrdering($class)
		{
			return $this->configuration->getOrdering($class);
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
				$this->configurationExpiration = $this->configuration->read();
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

			return $this->reflectionClasses[$class];
		}
	}
}
