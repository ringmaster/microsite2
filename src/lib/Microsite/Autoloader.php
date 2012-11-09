<?php

namespace Microsite;

class Autoloader
{
	static $namespace = [];

	static function register($namespace, $directory) {
		self::$namespace[$namespace] = $directory;
	}

	static function init() {
		if(substr(__DIR__, 0, 7) == 'phar://') {
			$lib_path = 'phar://microsite.phar/lib/';
		}
		else {
			$lib_path = dirname(__DIR__) . '/';
		}
		self::register('Microsite', $lib_path);
		spl_autoload_register(['\Microsite\Autoloader', 'load']);
	}

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