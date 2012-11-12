<?php

namespace Microsite;

class Handler
{
	protected static $instances = array();

	/**
	 * Queue a method to be used to handle a request within a Route
	 * Note regarding two string parameters:  It is annoying that these can't be a class name and method outside of a string,
	 *   but referencing them as actual PHP language values instead of strings causes them to be loaded and not late-bound,
	 *   which defeats the purpose of this class, which is to prepare grouped methods in a class that aren't loaded unless
	 *   they're required for execution.
	 * @param string $method The Handler class that contains the handler method
	 * @param string $method The method within the descendant class to call to handle this request
	 * @return callable A Closure used in a call to App::Route() that is used as a handler
	 */
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