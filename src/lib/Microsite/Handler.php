<?php

namespace Microsite;

class Handler
{
	static $instances = array();

	public static function handle($class, $method) {
		$fn = function(Response $response, Request $request, App $app) use($class, $method) {
			if(isset(Handler::$instances[$class])) {
				$handler_obj = Handler::$instances[$class];
			}
			else {
				$r_class = new \ReflectionClass($class);
				$handler_obj = $r_class->newInstanceArgs();
				Handler::$instances[$class] = $handler_obj;
			}
			return $handler_obj->$method($response, $request, $app);
		};
		return $fn;
	}
}