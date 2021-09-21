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
	private static function handleArgv(array $argvs)
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
		echo "It's starting replacements..." . PHP_EOL;
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
		$var_names = ["DB_HOST", "DB_NAME", "DB_USER", "DB_PASSWORD", "CLEANER"];
		foreach($var_names as $var_name)
		{
			if(!isset($_ENV[$var_name]))
			{
				throw new EnvironmentException($var_name . " environment's variables don't exist");
			}

			if($var_name === "CLEANER")
			{
				if($_ENV[$var_name] === "TRUE")
					continue;
				throw new EnvironmentException("You can't start this script. Change 'CLEANER' statement on .env file to TRUE");
			}
		}
	}

	/**
	 * (ru) Создаёт подключение к базе данных
	 * 
	 */
	private static function connectDb() 
	{
		static::$connection = new \mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], 
			$_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
		 
		if(static::$connection->connect_error)
		{
			throw new ConnectionException("Some problem appeared in connection process", 0, $connection->connect_error);
		}
	}

	/**
	 * (ru) Возвращает массив используемых в базе данных таблиц
	 * return array
	 */ 
	private static function getWpTables(): array
	{
		$array_of_tables = array();
		$query = "SELECT * FROM information_schema.tables";
		# инициализируем объект класса подготовленных запросов
		$stmt = static::$connection->stmt_init();
		if
		(
			$stmt->prepare($query) === false
			or
			# Выполняем запрос
			$stmt->execute() === false
			or
			# Небуферизированное получение данных
			($result = $stmt->get_result()) === false
			or
			$stmt->close() === false

		)
		{
			throw new ConnectionException("Some problems appeared in 'getting Tables' process", 0, $stmt->error);
		}
		# Получаем по порядку данные
		while($result_row = $result->fetch_object())
		{
			if($result_row->TABLE_SCHEMA == $_ENV['DB_NAME'])
			{
				array_push($array_of_tables, $result_row->TABLE_NAME);
			}
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
		$stmt = static::$connection->stmt_init();
		$query = "SELECT COLUMN_NAME as c_name FROM information_schema.columns WHERE TABLE_NAME=?";
		if(
			$stmt->prepare($query) === false
			or
			$stmt->bind_param('s', $table) === false
			or
			$stmt->execute() === false
			or
			($stmt_result = $stmt->get_result()) === false
			or
			$stmt->close() === false
		)
		{
			throw new ConnectionException("Some problems appeared in 'getting Columns' process", 0, $stmt->error);
		}
		while($row=$stmt_result->fetch_object())
		{
			array_push($array_of_columns, $row->c_name);
		}
		return $array_of_columns;
	}

	/**
	 * @param string $table
	 * @param string $column
	 */
	private static function updateColumn($table, $column)
	{
		$pattern = "'%" . static::$search . "%'";
		$query = "SELECT id, $column as feature FROM $table WHERE $column LIKE $pattern";
		$stmt = static::$connection->stmt_init();
		if
		(
			$stmt->prepare($query) === false
			or
			$stmt->execute() === false
			or
			($stmt_result = $stmt->get_result()) === false
		)
		{
			return;
		}
		if($stmt->close() === false) {throw new ConnectionException("Some problems on updating", 0);}
		while($row = $stmt_result->fetch_object())
		{
			$new_string = str_replace(static::$search, static::$replacement, $row->feature);
			static::updateFeatureByNewValue($table, $row->id, $column, $new_string);
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
		$query = "UPDATE $table SET $feature=? WHERE id=?";
		$stmt = static::$connection->stmt_init();
		if
		(
			$stmt->prepare($query) === false
			or
			$stmt->bind_param('si', $new_value, $id) === false
			or
			$stmt->execute() === false
			or
			$stmt->close() === false
		)
		{
			throw new ConnectionException("Some problems appeared in 'Insert new value' process", 0, $stmt->error);
		}
	}
}