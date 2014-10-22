<?php

namespace Microsite;

class Config
{
	protected static $configs = [];
	protected static $cache = null;

	public static function load($filename) {
		self::$configs[] = $filename;
	}

	protected static function init() {
		if(is_null(self::$cache)) {
			self::$cache = [];
			foreach(self::$configs as $filename) {
				$config = [];
				include $filename;

				self::$cache = array_merge(self::$cache, $config);
			}
		}
	}

	public static function get($key, $default = null) {
		self::init();
		return isset(self::$cache[$key]) ? self::$cache[$key] : $default;
	}

	public static function get_all() {
		self::init();
		return self::$cache;
	}

	public static function middleware(App $app) {
		$app->middleware('config', function(Response $response){
			$response['config'] = Config::get_all();
		});
	}
}