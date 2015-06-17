<?php namespace Redub\Database\SQL
{
	use Redub\Database;
	use Dotink\Flourish;

	/**
	 *
	 */
	abstract class AbstractDriver implements Database\DriverInterface
	{
		const PLATFORM_CLASS = NULL;

		/**
		 *
		 */
		static protected $platform = NULL;


		/**
		 *
		 */
		abstract public function count($handle, $response);


		/**
		 *
		 */
		abstract public function fail($handle, $response, $message);


		/**
		 *
		 */
		abstract public function execute($handle, $statement);


		/**
		 *
		 */
		abstract public function prepare($handle, Database\Query $query);


		/**
		 *
		 */
		abstract public function resolve(Database\Query $query, $response, $count);


		/**
		 *
		 */
		static public function getPlatform()
		{
			if (!static::$platform) {
				if (!static::PLATFORM_CLASS) {
					throw new Flourish\ProgrammerException(
						'Cannot get platform for driver "%s", no platform class defined',
						get_called_class()
					);
				}

				$platform_class   = static::PLATFORM_CLASS;
				static::$platform = new $platform_class();
			}

			return static::$platform;
		}


		/**
		 *
		 */
		public function run($handle, Database\Query $query)
		{
			$statement = $this->prepare($handle, $query);
			$response  = $this->execute($handle, $statement);
			$count     = $this->count($handle, $response);

			return $this->resolve($query, $response, $count);
		}
	}
}
