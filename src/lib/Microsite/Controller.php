<?php

namespace Microsite;

class Controller extends Singleton {
	public function __construct(App $app) {
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
	 * Return a DIObject that when invoked will return an instance of the named Controller class
	 * @param string $controller_classname The name of the Controller class to create
	 * @return DIObject An object that can be invoked to create the controller of the type specified
	 */
	public static function mount($controller_classname)
	{
		return new DIObject(function(App $app) use($controller_classname) {
			return new $controller_classname($app);
		}, true);
	}
}