<?php namespace Redub\ORM
{
	use Dotink\Flourish;

	/**
	 *
	 */
	abstract class AbstractConfiguration implements ConfigurationInterface
	{
		/**
		 *
		 */
		static protected $defaultFieldConfig = [
			'type'     => 'string',
			'default'  => NULL,
			'nullable' => FALSE,
			'unique'   => FALSE,
			'mapTo'    => NULL,

			//
			// These are for related fields
			//

			'target'   => NULL,
			'order'    => NULL,
			'where'    => NULL,
			'route'    => NULL
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
			'ordering'   => array(),
			'mapTo'      => NULL,
		];


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
		protected $identities = array();


		/**
		 *
		 */
		protected $map = array();


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

			$this->mappings[$class][$field] = ($mapping === NULL)
				? $this->convert($class, $field, 'FieldToMapping', strtolower($field))
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
		public function addOrdering($class, $ordering)
		{
			$this->ordering[$class] = $ordering;
		}


		/**
		 *
		 */
		public function addRepositoryMap($class, $source)
		{
			$this->map[$class] = $source;
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
		public function getDefaults($class)
		{
			return isset($this->defaults[$class])
				? $this->defaults[$class]
				: array();
		}


		/**
		 *
		 */
		public function getIdentity($class)
		{
			return isset($this->identities[$class])
				? $this->identities[$class]
				: NULL;
		}


		/**
		 *
		 */
		public function getMapping($class, $field = NULL)
		{
			if (isset($this->mappings[$class])) {
				if ($field) {
					return isset($this->mappings[$class][$field])
						? $this->mappings[$class][$field]
						: NULL;
				}

				return $this->mappings[$class];
			}

			return array();
		}


		/**
		 *
		 */
		public function getModel($class)
		{
			return isset($this->models[$class])
				? $this->models[$class]
				: NULL;
		}


		/**
		 *
		 */
		public function getTyping($class, $field = NULL)
		{
			return isset($this->fields[$class])
				? $this->fields[$class]
				: array();
		}


		/**
		 *
		 */
		public function getRepository($class)
		{
			return array_search($class, $this->models);
		}


		/**
		 *
		 */
		public function getRepositoryMap($class)
		{
			return isset($this->map[$class])
				? $this->map[$class]
				: NULL;
		}


		/**
		 *
		 */
		public function setIdentity($class, $identity)
		{
			$this->identities[$class] = $identity;
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
		public function load(Cache $cache)
		{
			// TODO: Load from Cache
		}


		/**
		 *
		 */
		protected function addConfig($config)
		{
			foreach ($config as $class => $class_config) {
				if (isset($class_config['identity'])) {
					$this->addModelConfig($class, $class_config);

				} elseif (isset($class_config['convention']) || isset($class_config['model'])){
					$this->addRepositoryConfig($class, $class_config);

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
			$this->setNullable($class, $field, $config['nullable']);

			if ($config['type'] == 'hasOne') {

			} elseif ($config['type'] == 'hasMany') {

				$this->addDefault($class, $field, new Collection());

			} elseif (!$config['target']) {
				$this->addMapping($class, $field, $config['mapTo']);
				$this->addDefault($class, $field, $config['default']);

				if ($config['unique']) {
					$this->addUniqueOn($class, [$config['mapping']]);
				}
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
			$this->addOrdering($class, $config['ordering']);
			$this->addRepositoryMap($class, $config['mapTo']);
		}


		/**
		 *
		 */
		private function convert($class, $value, $type, $default)
		{
			// TODO: implemention converters... you should probably attempt to lookup
			// and cache the converter during repository configuration, then just see if it's
			// in the cache, if so ... use it ... if not, don't.
			//
			// The manager will allow for converters to be added.

			return $default;
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
