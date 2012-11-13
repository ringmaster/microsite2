<?php

namespace Microsite\Renderers;

use Microsite\Renderers\Renderer;

class JSONRenderer extends Renderer
{
	public function render($template, $vars) {
		header('content-type: application/json');
		foreach($vars as $key => $value) {
			if(is_object($value)) {
				$class = get_class($value);
				$r_class = new \ReflectionClass($class);
				$namespace = $r_class->getNamespaceName();
				if(preg_match('/^\\\\?Microsite(\\\\|$)/', $namespace)) {
					unset($vars[$key]);
				}
			}
		}
		return json_encode($vars, JSON_PRETTY_PRINT);
	}
}