<?php namespace Redub\ORM\Configuration
{
	use Redub\ORM;
	use Dotink\Flourish;
	use Dotink\Jin\Parser;

	/**
	 *
	 */
	class Jin extends ORM\Configuration
	{
		/**
		 *
		 */
		protected $rawConfig = NULL;


		/**
		 *
		 */
		public function __construct($config_path, Parser $parser = NULL)
		{
			$this->configPath = realpath($config_path);
			$this->parser     = $parser ?: new Parser();

			if (!$this->configPath) {
				throw new Flourish\EnvironmentException(
					'Configuration path "%s" is invalid and cannot be read',
					$config_path
				);
			}
		}


		/**
		 *
		 */
		public function readConfiguration()
		{
			if (is_file($this->configPath)) {
				$this->addConfiguration($this->readFile($this->configPath));

			} elseif (is_dir($this->configPath)) {
				foreach ($this->readDirectory($this->configPath) as $configuration) {
					$this->addConfiguration($configuration);
				}

			} else {
				throw new Flourish\EnvironmentException(
					'Invalid configuration path "%s", must be a file or directory',
					$this->configPath
				);
			}
		}


		/**
		 *
		 */
		protected function readDirectory($directory)
		{
			foreach (glob($this->configPath . '/*.jin') as $jin_file) {
				yield $this->readFile($jin_file);
			}

			foreach (glob($this->configPath . '/*', GLOB_ONLYDIR) as $directory) {
				$this->readDirectory($directory);
			}
		}


		/**
		 *
		 */
		protected function readFile($file)
		{
			return $this->parser->parse(file_get_contents($file), TRUE)->get();
		}
	}
}
