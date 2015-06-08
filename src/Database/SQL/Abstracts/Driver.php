<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	abstract class Driver implements Database\DriverInterface
	{
		const RESULT_CLASS     = NULL;
		const QUERY_CLASS      = NULL;


		/**
		 *
		 */
		protected $platform = NULL;


		/**
		 *
		 */
		public function __construct(Database\PlatformInterface $platform = NULL)
		{
			$this->platform = $platform;
		}


		/**
		 *
		 */
		public function getPlatform()
		{
			if (!$this->platform) {
				$class = static::PLATFORM_CLASS;

				$this->platform = new $class();
			}

			return $this->platform;
		}


		/**
		 *
		 */
		public function resolve(Database\Query $query, $response, $count)
		{
			$result_class  = static::RESULT_CLASS;
			$result_object = new $result_class($response, $count);

			return $result_object;
		}


		/**
		 *
		 */
		public function run(Database\Query $query)
		{
			$statement = $this->prepare($query);
			$response  = $this->execute($statement);
			$count     = $this->count($response);

			if (!$response) {
				$this->fail($response, 'Could not execute query');
			}

			return $this->resolve($query, $response, $count);
		}
	}
}
