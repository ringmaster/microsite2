<?php

namespace Microsite;

/**
 * Class Handler
 * @package Microsite
 */
class Handler extends Singleton
{
	protected static $instances = array();

	/**
	 * Add routes to an App from this class by inferring details from PHPDoc @url and @method properties
	 * @param App $app
	 */
	public function load(App $app) {
		$class = get_called_class();
		$methods = get_class_methods($class);
		foreach($methods as $class_method) {
			$method_props = [];
			$rm = new \ReflectionMethod($this, $class_method);
			$phpdoc = preg_split('/\r\n|\n|\r/', preg_replace('%^\s*/?\*+((?<=\*)/|[ \t]*)%m', '', $rm->getDocComment()));
			foreach($phpdoc as $docline) {
				if(preg_match('#^@(url|method)\s+(\S+)$#', $docline, $matches)) {
					$method_props[$matches[1]] = $matches[2];
				}
			}
			if(isset($method_props['url'])) {
				$route = $app->route($class . '::' . $class_method, $method_props['url'], $rm->getClosure($this));
				if(isset($method_props['method'])) {
					$route->via($method_props['method']);
				}
			}
		}
	}


	/**
	 * Mount a Handler class at a specific route, load it only when
	 * Return a DIObject that when invoked will return an instance of the named Controller class
	 * @param string $controller_classname The name of the Controller class to create
	 * @return DIObject An object that can be invoked to create the controller of the type specified
	 */
	public static function mount($controller_classname)
	{
		return new DIObject(function(App $app) use($controller_classname) {
			/** @var Handler $controller */
			$controller = new $controller_classname($app);
			return $controller;
		}, true);
	}

	/**
	 * Queue a method to be used to handle a request within a Route
	 * Note regarding two string parameters:  It is annoying that these can't be a class name and method outside of a string,
	 *   but referencing them as actual PHP language values instead of strings causes them to be loaded and not late-bound,
	 *   which defeats the purpose of this class, which is to prepare grouped methods in a class that aren't loaded unless
	 *   they're required for execution.
	 * @param string $class The class that will handle this request
	 * @param string $method The method within the descendant class to call to handle this request
	 * @return callable A Closure used in a call to App::Route() that is used as a handler
	 */
	public static function handle($class, $method) {
		$fn = function(App $app) use($class, $method) {
			if(isset(Handler::$instances[$class])) {
				$handler_obj = Handler::$instances[$class];
			}
			else {
				$r_class = new \ReflectionClass($class);
				$handler_obj = $r_class->newInstanceArgs();
				Handler::$instances[$class] = $handler_obj;
			}
			return $handler_obj->$method($app);
		};
		return $fn;
	}
}