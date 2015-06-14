<?php namespace Redub\ORM
{
	use Nette\PhpGenerator\ClassType;
	use Nette\PhpGenerator\PhpNamespace;

	class Builder
	{
		/**
		 *
		 */
		public function __construct($root_directory = NULL)
		{
			$this->rootDirectory = $root_directory
				? realpath($root_directory)
				: sys_get_temp_dir();

			if (!$this->rootDirectory) {
				throw new Flourish\ProgrammerException(
					'Could not determine suitable root directory for builder, try providing one'
				);
			}
		}


		/**
		 *
		 */
		public function build($config, $repository, $base, $file_path)
		{
			$this->base      = $base;
			$this->config    = $config;
			$this->namespace = new PhpNamespace($this->makeNamespace());
			$this->class     = $this->namespace->addClass($this->makeClassName());

			$this->namespace->setBracketedSyntax(TRUE);
			$this->class->setExtends(__NAMESPACE__ . '\Model');

			foreach ($config->getTyping($repository) as $field => $type) {
				if ($type == 'boolean') {
					$this->makeIsMethod($repository, $field, $type);

				} elseif (in_array($type, ['hasOne', 'hasMany'])) {
					$this->makeFetchMethod($repository, $field, $type);
					$this->makeHasMethod($repository, $field, $type);

				} else {
					$this->makeGetMethod($repository, $field, $type);
					$this->makeHasMethod($repository, $field, $type);

				}

				$this->makeSetMethod($repository, $field, $type);
			}

			$this->sortMethods();
			$this->write($file_path);
		}


		/**
		 *
		 */
		public function getPath($model, $models_base_path = NULL)
		{
			return str_replace('\\', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, [
				$this->rootDirectory,
				$models_base_path,
				$model . '.php'
			]));
		}


		/**
		 *
		 */
		public function makeClassName()
		{
			return current(array_slice(explode('\\', $this->base), -1));
		}


		/**
		 *
		 */
		protected function makeNamespace()
		{
			return implode('\\', array_slice(explode('\\', $this->base), 0, -1));
		}


		/**
		 *
		 */
		protected function makeIsMethod($repository, $field, $type)
		{
			$this->class->addMethod('is' . ucfirst($field))
				-> setVisibility("public")
				-> addDocument("Check if $field is TRUE or FALSE")
				-> addDocument("")
				-> addDocument("@access public")
				-> addDocument("@return boolean TRUE if $field is TRUE, FALSE otherwise")
				-> addBody("return (bool) \$this->get('$field');")
			;
		}


		/**
		 *
		 */
		protected function makeFetchMethod($repository, $field, $type)
		{
			$value_phrase = $type == 'hasOne' ? 'value'  : 'values';
			$type_phrase  = $type == 'hasOne' ? 'Entity' : 'Collection';
			//
			// TODO: replace the above with the actual type
			//

			$this->class->addMethod('fetch' . ucfirst($field))
				-> setVisibility("public")
				-> addDocument("Fetch the $value_phrase of the related $field from the database")
				-> addDocument("")
				-> addDocument("@access public")
				-> addDocument("@return $type_phrase The $field $value_phrase")
				-> addBody("return \$this->get('$field');")
			;
		}


		/**
		 *
		 */
		protected function makeGetMethod($repository, $field, $type)
		{
			$this->class->addMethod('get' . ucfirst($field))
				-> setVisibility("public")
				-> addDocument("Get the value of $field")
				-> addDocument("")
				-> addDocument("@access public")
				-> addDocument("@return $type The value of $field")
				-> addBody("return \$this->get('$field');")
			;
		}


		/**
		 *
		 */
		protected function makeHasMethod($repository, $field, $type)
		{
			$this->class->addMethod('has' . ucfirst($field));
		}


		/**
		 *
		 */
		protected function makeSetMethod($repository, $field, $type)
		{
			$this->class->addMethod('set' . ucfirst($field))
				-> setVisibility("public")
				-> addDocument("Set the value of $field")
				-> addDocument("")
				-> addDocument("@access public")
				-> addDocument("@param $type \$value The value to set to $field")
				-> addDocument("@return Entity The object instance for method chaining")
				-> addBody("\$this->set('$field', \$value);")
				-> addBody("")
				-> addBody("return \$this;")

				-> addParameter("value")
			;
		}


		/**
		 *
		 */
		protected function sortMethods()
		{
			$methods = $this->class->getMethods();

			usort($methods, function($a, $b) {
				return $a->getName() < $b->getName()
					? -1
					: 1;
			});

			$this->class->setMethods($methods);
		}


		/**
		 *
		 */
		protected function write($file_path)
		{
			$directory = dirname($file_path);

			if (!is_dir($directory)) {
				if (!@mkdir(dirname($file_path), 0755, TRUE)) {
					throw new Flourish\EnvironmentException(

					);
				}
			}


			return file_put_contents($file_path, '<?php ' . $this->namespace);

		}
	}
}
