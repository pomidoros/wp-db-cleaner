<?php

namespace slovenberg\WpDbCleaner;


class Main {
	private static function findEnv() 
	{

	}

	public static function start() {
		$basic_dir = __DIR__ . "/../../../../";
		static::checkEnvVars();
		static::connectDb();
	}

	private static function checkEnvVars()
	{
		$var_names = ["DB_HOST", "DB_NAME", "DB_USER", "DB_PASSWORD"];
		foreach($var_names as $var_name)
		{
			if(!isset($_ENV[$var_name]))
			{
				throw new \Exception("Hello");
			}
		}
	}

	private static function connectDb() 
	{
		try 
		{
			$connection = new \PDO("mysql:dbname=$_ENV[DB_NAME];host=$_ENV[DB_HOST]",
				$_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
			$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch(\Throwable $exc) 
		{
			echo $exc->getMessage() . PHP_EOL;
		}
	}
}