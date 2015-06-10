<?php namespace Redub\Database\SQL
{
	use Redub\Database;

	/**
	 *
	 */
	abstract class AbstractDriver extends Database\AbstractDriver implements DriverInterface
	{
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
