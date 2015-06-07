<?php namespace Redub\ORM
{
	use Dotink\Flourish;

	/**
	 *
	 */
	abstract class Configuration
	{
		/**
		 *
		 */
		static protected $defaultFieldConfig = [
			'type'     => 'string',
			'default'  => NULL,
			'mapping'  => NULL,
			'nullable' => FALSE,
			'unique'   => FALSE,

			//
			// These are for related fields
			//

			'target'   => NULL,
			'route'    => NULL,
			'order'    => NULL,
			'where'    => NULL
		];

		/**
		 *
		 */
		static protected $defaultModelConfig = [
			'convention' => NULL,
			'identity'   => NULL,
			'fields'     => array(),
			'uniqueOn'   => array()
		];


		/**
		 *
		 */
		static protected $defaultRepositoryConfig = [
			'convention' => NULL,
			'collection' => NULL,
			'ordering'   => array()
		];


		/**
		 *
		 */
		protected $collections = array();


		/**
		 *
		 */
		protected $conventions = array();


		/**
		 *
		 */
		protected $defaults = array();


		/**
		 *
		 */
		protected $fields = array();


		/**
		 *
		 */
		protected $mappings = array();


		/**
		 *
		 */
		protected $models = array();


		/**
		 *
		 */
		protected $nullables = array();


		/**
		 *
		 */
		protected $uniqueConstraints = array();


		/**
		 *
		 */
		abstract protected function readConfiguration();


		/**
		 *
		 */
		public function addDefault($class, $field, $default)
		{
			if (!isset($this->fields[$class][$field])) {
				throw new Flourish\ProgrammerException(
					'Cannot add default for %s.%s, no such field exists',
					$class,
					$field
				);
			}

			$this->defaults[$class][$field] = $default;
		}


		/**
		 *
		 */
		public function addField($class, $field, $type)
		{
			$this->init('fields',   $class);
			$this->init('defaults', $class);
			$this->init('mappings', $class);

			$this->fields[$class][$field]   = $type;
			$this->defaults[$class][$field] = NULL;
			$this->mappings[$class][$field] = strtolower($field);
		}


		/**
		 *
		 */
		public function addMapping($class, $field, $mapping)
		{
			if (!isset($this->fields[$class][$field])) {
				throw new Flourish\ProgrammerException(
					'Cannot add mapping for %s.%s, no such field exists',
					$class,
					$field
				);
			}

			$this->mappings[$class][$field] = ($mapping !== NULL)
				? $this->convert($class, $field, 'FieldToMapping')
				: $mapping;
		}


		/**
		 *
		 */
		public function addModel($class, $model)
		{
			$this->models[$class] = $model;
		}


		/**
		 *
		 */
		public function addRelatedOrdering($class, $target, $ordering)
		{

		}


		/**
		 *
		 */
		public function addRepository($class, $collection)
		{
			$this->collections[$class] = $collection;
		}


		/**
		 *
		 */
		public function addUniqueOn($class, $constraint)
		{

		}

		/**
		 *
		 */
		public function cache(Cache $cache)
		{
			// TODO: Store in Cache
		}


		/**
		 *
		 */
		public function getDefaults($class)
		{
			return isset($this->defaults[$class])
				? $this->defaults[$class]
				: array();
		}


		/**
		 *
		 */
		public function getFields($class)
		{
			return isset($this->fields[$class])
				? $this->fields[$class]
				: array();
		}


		/**
		 *
		 */
		public function setIdentity($class, $identity)
		{

		}


		/**
		 *
		 */
		public function setNullable($class, $field, $nullable)
		{
			$this->init('nullables', $class);

			$this->nullables[$class][$field] = $nullable;
		}


		/**
		 *
		 */
		public function setOrdering($class, $ordering)
		{

		}


		/**
		 *
		 */
		public function load(Cache $cache)
		{
			// TODO: Load from Cache
		}


		/**
		 *
		 * @return integer The new expiration time
		 */
		public function read(Manager $manager)
		{
			$this->manager = $manager;

			$this->readConfiguration();

			return time() + (60 * 15);
		}


		/**
		 *
		 */
		protected function addConfig($config)
		{
			foreach ($config as $class => $class_config) {
				if (isset($class_config['model']) || isset($class_config['convention'])) {
					$this->addRepositoryConfig($class, $class_config);

				} elseif (isset($class_config['identity']) || isset($class_config['convention'])){
					$this->addModelConfig($class, $class_config);

				} else {
					throw new Flourish\ProgrammerException(
						'Invalid class configuration for class %s, must provide model or identity',
						$class
					);
				}
			}
		}


		/**
		 *
		 */
		protected function addFieldConfig($class, $field, $config)
		{
			$config = $this->merge(static::$defaultFieldConfig, $config);

			$this->addField($class, $field, $config['type']);
			$this->addMapping($class, $field, $config['mapping']);
			$this->addDefault($class, $field, $config['default']);
			$this->setNullable($class, $field, $config['nullable']);

			if ($config['unique']) {
				$this->addUniqueOn($class, [$config['mapping']]);
			}
		}

		/**
		 *
		 */
		protected function addModelConfig($class, $config)
		{
			$config = $this->merge(static::$defaultModelConfig, $config);

			foreach ($config['fields'] as $field => $field_config) {
				$this->addFieldConfig($class, $field, $field_config);
			}

			$this->setIdentity($class, $config['identity']);

			foreach ($config['uniqueOn'] as $unique_on => $constraint) {
				if (!is_array($constraint)) {
					throw new Flourish\ProgrammerException(
						'Invalid model configuration using non-array constraint %s',
						$constraint
					);
				}

				$this->addUniqueOn($class, $constraint);
			}
		}


		/**
		 *
		 */
		protected function addRepositoryConfig($class, $config)
		{
			$config = $this->merge(static::$defaultRepositoryConfig, $config);

			$this->addModel($class, $config['model']);
			$this->addRepository($class, $config['collection']);
			$this->setOrdering($class, $config['ordering']);
		}


		/**
		 *
		 */
		private function convert($class, $value, $type)
		{
			// TODO: implemention converters... you should probably attempt to lookup
			// and cache the converter during repository configuration, then just see if it's
			// in the cache, if so ... use it ... if not, don't.
			//
			// The manager will allow for converters to be added.
		}

		/**
		 *
		 */
		private function init($property, $class, $field = NULL)
		{
			if (!isset($this->{$property}[$class])) {
				$this->{$property}[$class] = array();
			}
		}

		/**
		 *
		 */
		private function merge($default_config, $config)
		{
			return array_merge($default_config, $config);
		}
	}
}
