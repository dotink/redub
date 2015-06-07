<?php namespace Redub\ORM
{
	use Dotink\Flourish;

	abstract class Model
	{
		/**
		 *
		 */
		private $data = array();


		/**
		 *
		 */
		private $history = array();


		/**
		 *
		 */
		protected function get($property)
		{
			$this->checkProperty($property, 'Cannot get property');
		}


		/**
		 *
		 */
		protected function set($property, $value)
		{
			$this->checkProperty($property, 'Cannot set property');

			if (!isset($this->history[$property])) {
				$this->history[$property] = array();
			}

			$this->history[$property][] = $this->data[$property];
			$this->data[$property]      = $value;
		}


		/**
		 *
		 */
		protected function hasChanged($property = NULL)
		{
			if ($property) {
				$this->checkProperty($property, 'Cannot get history');

				if (!isset($this->history[$property])) {
					return FALSE;
				}

				return $this->data[$property] != end($this->history[$property]);

			} else {
				return (bool) $history;
			}
		}


		/**
		 *
		 */
		private function checkProperty($property, $error_message)
		{
			if (!array_key_exists($property, $this->data)) {
				$class = get_class($this);

				throw new Flourish\ProgrammerException(
					'%s: The property "%s" does not exist on model %s',
					$error_message,
					$property,
					$class
				);
			}
		}
	}
}
