<?php namespace Dotink\Flourish
{
	class Exception extends \Exception
	{
		public function __construct($message = '')
		{
			parent::__construct(vsprintf($message, array_slice(func_get_args(), 1)));
		}
	}

	class UnexpectedException extends Exception {};
	class ProgrammerException extends UnexpectedException {};
}
