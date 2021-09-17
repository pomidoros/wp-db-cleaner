<?php

namespace slovenberg\WpDbCleaner;
use slovenberg\WpDbCleaner\exceptions\HandleArgvException;
use slovenberg\WpDbCleaner\exceptions\MainException;
use slovenberg\WpDbCleaner\exceptions\EnvironmentException;
use slovenberg\WpDbCleaner\exceptions\ConnectionException;


class Main {

	// store a connection to database
	private static $connection;

	// store the replacement value
	private static $replacement;

	// store the value which we want to replace
	private static $search;


	/**
	 * Insert needle values into the static properties
	 * @param array $argvs argv in console
	 */ 
	private static function handleArgv(array $argvs): null
	{
		unset($argvs[0]);
		$argvs = array_slice($argvs, 0);
		if(count($argvs) != 2)
		{
			throw new HandleArgvException("The count of the args should be equal to 2");
		}
		static::$search = $argvs[0];
		static::$replacement = $argvs[1];

	}

	/**
	 * Reset all static properties
	 * 
	 */ 
	private static function resetAll()
	{
		static::$replacement = null;
		static::$search = null;
		static::$connection = null;
		echo "All processes have been completed" . PHP_EOL;
	}

	/**
	 * Main function
	 */ 
	public static function start() {
		global $argv;
		try 
		{
			static::handleArgv($argv);
			static::checkEnvVars();
			static::connectDb();
			$wp_tables = static::getWpTables();
			static::handleTables($wp_tables);
		} catch(\Exception $exc)
		{
			throw new MainException("", 0, $exc);
		} finally
		{
			static::resetAll();
		}
	}

	private static function checkEnvVars()
	{
		$var_names = ["DB_HOST", "DB_NAME", "DB_USER", "DB_PASSWORD"];
		foreach($var_names as $var_name)
		{
			if(!isset($_ENV[$var_name]))
			{
				throw new EnvironmentException("Some environment's variables don't exist");
			}
		}
	}

	/**
	 * (ru) Создаёт подключение к базе данных
	 * 
	 */
	private static function connectDb() 
	{
		try 
		{
			static::$connection = new \PDO("mysql:dbname=$_ENV[DB_NAME];host=$_ENV[DB_HOST]",
				$_ENV['DB_USER'], $_ENV['DB_PASSWORD']);
			static::$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch(\PDOExcetion $exc) 
		{
			throw new ConnectionException("Some problem appeared in connection process", 0, $exc);
		}
	}

	/**
	 * (ru) Возвращает массив используемых в базе данных таблиц
	 * return array
	 */ 
	private static function getWpTables(): array
	{
		$query = "SELECT * FROM information_schema.tables";
		try {
			$PST = static::$connection->prepare($query);
			$PST->execute();
			$PST->setFetchMode(\PDO::FETCH_OBJ);
			$array_of_tables = [];
			while($row = $PST->fetch())
			{
				if($row->TABLE_SCHEMA == $_ENV['DB_NAME'])
				{
					array_push($array_of_tables, $row->TABLE_NAME);
				}
			}
		} catch(\PDOException $exc)
		{
			throw new ConnectionException("Some problems appeared in 'getting Tables' process", 0, $exc);
		}
		return $array_of_tables;
	}

	/**
	 * (ru) Основная функция, которая проходится по отношениям и их признакам для замены значений
	 * @param array $tables
	 */
	private static function handleTables($tables)
	{
		foreach($tables as $table)
		{
			$columns = static::getTableColumns($table);
			foreach($columns as $column)
			{
				static::updateColumn($table, $column);
			}
		}
	}

	/**
	 * (ru) Возвращает список признаков отношения(таблицы)
	 * @param string $table
	 * return array
	 */
	private static function getTableColumns($table): array
	{
		$array_of_columns = [];
		try 
		{
			$query = "SELECT COLUMN_NAME as c_name FROM information_schema.columns WHERE TABLE_NAME=:table_name";
			$PST = static::$connection->prepare($query);
			$PST->bindParam(':table_name', $table);
			$PST->setFetchMode(\PDO::FETCH_OBJ);
			$PST->execute();
			
			while($row=$PST->fetch())
			{
				array_push($array_of_columns, $row->c_name);
			}
		} catch(\PDOException $exc)
		{
			throw new ConnectionException("Some problems appeared in 'getting Columns' process", 0, $exc);
		}
		return $array_of_columns;
	}

	/**
	 * @param string $table
	 * @param string $column
	 */
	private static function updateColumn($table, $column)
	{
		try 
		{
			$pattern = "'%" . static::$search . "%'";
			$query = "SELECT id, $column as feature FROM $table WHERE $column LIKE $pattern";
			$PST = static::$connection->prepare($query);
			$PST->execute();
			$PST->setFetchMode(\PDO::FETCH_OBJ);
			while($row = $PST->fetch())
			{
				$new_string = str_replace(static::$search, static::$replacement, $row->feature);
				static::updateFeatureByNewValue($table, $row->id, $column, $new_string);
			}
		} catch(\PDOException $exc)
		{
			throw new ConnectionException("Some problems appeared in 'Updating Column' process", 0, $exc);
		}
	}

	/**
	 * (ru) Функция обновляет в нужной таблице нужный признак для нужного id
	 * @param $table
	 * @param $id
	 * @param $feature
	 * @param $new_value
	 */
	private static function updateFeatureByNewValue($table, $id, $feature, $new_value)
	{
		try 
		{
			$query = "UPDATE $table SET $feature=:value WHERE id=:id";
			$PST = static::$connection->prepare($query);
			$PST->bindParam(':id', $id);
			$PST->bindParam(':value', $new_value);
			$PST->execute();
		}
		catch(\PDOException $exc)
		{
			throw new ConnectionException("Some problems appeared in 'Insert new value' process", 0, $exc);
		}
	}
}