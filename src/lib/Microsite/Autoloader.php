<?php

namespace Microsite;

class Autoloader
{
	static $namespace = [];

	/**
	 * Register the directory to search for a namespace
	 * @param string $namespace The namespace to register
	 * @param string $directory The directory to search
	 */
	static function register($namespace, $directory) {
		$directory = str_replace('\\', DIRECTORY_SEPARATOR, $directory);
		if(substr($directory, -1) != DIRECTORY_SEPARATOR) {
			$directory .= DIRECTORY_SEPARATOR;
		}
		self::$namespace[$namespace] = $directory;
	}

	/**
	 * Initialize the Autoloader by adding the initial Microsite class path and
	 * registering ::load() as an SPL Autoload method
	 */
	static function init() {
		if(substr(__DIR__, 0, 7) == 'phar://') {
			$lib_path = 'phar://microsite.phar/lib/';
		}
		else {
			$lib_path = dirname(__DIR__) . DIRECTORY_SEPARATOR;
		}
		self::register('Microsite', $lib_path);
		spl_autoload_register(['\Microsite\Autoloader', 'load']);
	}

	/**
	 * Find a named class file and require it
	 * Used as an SPL Autoload method
	 * @param string $class_name The name of the class to load.
	 */
	static function load($class_name) {
		foreach(self::$namespace as $prefix => $dir) {
			if(substr($class_name, 0, strlen($prefix) + 1) == $prefix . '\\') {
				$class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class_name) . '.php';
				$class_path = $dir . $class_file;
				if(file_exists($class_path)) {
					require($class_path);
				}
			}
		}
	}

}