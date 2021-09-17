<?php


namespace slovenberg\WpDbCleaner\exceptions;


class HandleArgvException extends \Exception
{
	public function __construct($message="", $code=0, $parent=null)
	{
		parent::__construct($message, $code, $parent);
	}
}