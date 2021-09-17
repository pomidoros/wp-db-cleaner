<?php


namespace slovenberg\WpDbCleaner\exceptions;


class MainException extends \Exception
{
	public function __construct($message="", $code=0, $parent=null)
	{
		$message = "Some problems appeared";
		parent::__construct($message, $code, $parent);
	}
}