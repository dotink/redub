<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	abstract class Driver implements Database\DriverInterface
	{
		const PLATFORM_CLASS   = NULL;
		const RESULT_CLASS     = NULL;
		const QUERY_CLASS      = NULL;


		/**
		 *
		 */
		protected $platform = NULL;


		/**
		 *
		 */
		public function prepareQuery($cmd)
		{
			$query_class = static::QUERY_CLASS;

			if (!is_a($cmd, $query_class)) {
				$query = new $query_class((string) $cmd, $this->getPlatform());
			}

			return $query;
		}


		/**
		 *
		 */
		public function resolve(Query $query, $reply, $count)
		{
			$result_class  = static::RESULT_CLASS;
			$result_object = new $result_class($reply, $count);

			return $result_object;
		}


		/**
		 *
		 */
		public function run($cmd)
		{
			$query = $this->prepareQuery($cmd);
			$reply = $this->executeQuery($query);
			$count = $this->executeCount($reply);

			if (!$reply) {
				$this->executeFailure($query, $reply, sprintf('Could not execute (%s)', $cmd));
			}

			return $this->resolve($query, $reply, $count);

		}


		/**
		 *
		 */
		protected function getPlatform()
		{
			if (!$this->platform) {
				$class = static::PLATFORM_CLASS;

				$this->platform = new $class();
			}

			return $this->platform;
		}
	}
}
