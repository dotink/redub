<?php

	use Redub\ORM;

	/**
	 *
	 */
	class People extends ORM\Repository
	{

	}

	/**
	 *
	 */
	class Person extends ORM\Model
	{
		/**
		 *
		 */
		public function getFirstName()
		{
			return $this->get('firstName');
		}


		/**
		 *
		 */
		public function getLastName()
		{
			return $this->get('lastName');
		}


		/**
		 *
		 */
		public function setFirstName($value)
		{
			return $this->set('firstName', $value);
		}


		/**
		 *
		 */
		public function setLastName($value)
		{
			return $this->set('lastName', $value);
		}
	}
