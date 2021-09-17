<?php

namespace slovenberg\WpDbCleaner;


class Main {
	private static function findEnv() {

	}

	public static function start() {
		$basic_dir = __DIR__ . "/../../../../";
		var_dump(scandir($basic_dir));
	}
}