<?php namespace Redub\ORM\Configuration
{
	use Redub\ORM;
	use Dotink\Flourish;
	use Dotink\Jin\Parser;

	/**
	 * Jin configuration encapsulation
	 *
	 */
	class Jin extends AbstractConfiguration
	{
		/**
		 * The filesystem path to the configuration
		 *
		 * @access protected
		 * @var string
		 */
		protected $configPath = NULL;


		/**
		 * Create a new Jin based configuration
		 *
		 * @access public
		 * @param string $config_path The path to a Jin config file or directory containing many
		 * @param Parser $parser The Jin parser to use
		 * @return void
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
		 * @return integer The new expiration time
		 */
		public function read()
		{
			if (is_file($this->configPath)) {
				$this->addConfig($this->readFile($this->configPath));

			} elseif (is_dir($this->configPath)) {
				foreach ($this->readDirectory($this->configPath) as $configuration) {
					$this->addConfig($configuration);
				}

			} else {
				throw new Flourish\EnvironmentException(
					'Invalid configuration path "%s", must be a file or directory',
					$this->configPath
				);
			}

			return time() + (60 * 15);
		}


		/**
		 * Reads a directory recursively
		 *
		 * @access protected
		 * @param string $directory The directory from which to read
		 * @return void
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
		 * Reads a Jin configuration file
		 *
		 * @access protected
		 * @param string $file The file from which to read
		 * @return array The configuration array
		 */
		protected function readFile($file)
		{
			return $this->parser->parse(file_get_contents($file), TRUE)->get();
		}
	}
}
