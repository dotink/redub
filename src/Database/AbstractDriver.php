<?php namespace Redub\Database
{
	abstract class AbstractDriver implements DriverInterface
	{
		const PLATFORM_CLASS = NULL;

		/**
		 *
		 */
		static protected $platform = NULL;


		/**
		 *
		 */
		final static public function getPlatform()
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
	}
}
