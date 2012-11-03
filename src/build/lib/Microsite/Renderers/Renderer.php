<?php

namespace Microsite\Renderers;

abstract class Renderer
{
	protected $template_dirs = array();

	public function __construct($template_dirs) {
		if(!is_array($template_dirs)) {
			$template_dirs = array($template_dirs);
		}
		$this->template_dirs = $template_dirs;
	}

	public static function create($template_dirs) {
		$class = get_called_class();
		$args = func_get_args();
		$r_class = new \ReflectionClass($class);
		return $r_class->newInstanceArgs($args);
	}

	public abstract function render($template, $vars);
}