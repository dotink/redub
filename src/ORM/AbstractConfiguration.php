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
		static protected $defaultRepositoryConfig = [
			'model'      => NULL,
			'fields'     => array(),
			'identity'   => NULL,
			'uniqueOn'   => array(),
			'ordering'   => array(),
			'mapTo'      => NULL,
			'convention' => NULL
		];


		/**
		 *
		 */
		public $bindings = array();


		/**
		 *
		 */
		public $config = [
			'connections' => array()
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
			$this->models[$class] = !$model
				? $class . 'Model'
				: $model;
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
		public function addUniqueOn($class, array $constraint)
		{
			$this->uniqueConstraints[$class][] = $constraint;
		}


		/**
		 *
		 */
		public function bind($alias, $object)
		{
			$this->bindings[$alias] = $object;
		}


		/**
		 *
		 */
		public function find($key)
		{
			return isset($this->config[$key])
				? $this->config[$key]
				: NULL;
		}


		/**
		 *
		 */
		public function getBinding($alias)
		{
			return isset($this->bindings[$alias])
				? $this->bindings[$alias]
				: NULL;
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
			if ($field) {
				return isset($this->fields[$class][$field])
					? $this->fields[$class][$field]
					: NULL;
			}

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
		public function getUniqueConstraints($class)
		{
			return isset($this->uniqueConstraints[$class])
				? $this->uniqueConstraints[$class]
				: array();
		}


		/**
		 *
		 */
		public function setIdentity($class, $identity)
		{
			$this->identities[$class] = (array) $identity;

			return $this;
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
		public function setUniqueOn($class, array $constraints = array())
		{
			$this->uniqueOn[$class] = $constraints;
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
			foreach ($config as $class => $config) {
				if (strtolower($class) == 'redub') {
					$this->config = $config;

				} else {
					$this->addRepositoryConfig($class, $config);
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
		protected function addRepositoryConfig($class, $config)
		{
			$config = $this->merge(static::$defaultRepositoryConfig, $config);

			$this->addModel($class, $config['model']);
			$this->addOrdering($class, $config['ordering']);
			$this->addRepositoryMap($class, $config['mapTo']);

			foreach ($config['fields'] as $field => $field_config) {
				$this->addFieldConfig($class, $field, $field_config);
			}

			$this->setIdentity($class, $config['identity']);
			$this->setUniqueOn($class, array());

			foreach ($config['uniqueOn'] as $unique_on => $constraint) {
				$this->addUniqueOn($class, $constraint);
			}
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
