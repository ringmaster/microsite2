<?php

namespace Microsite\Renderers;

/**
 * Abstract class for implementing a renderer of output
 */
use Microsite\App;

abstract class Renderer
{
	/**
	 * @var array $template_dirs An array of potential directories in which templates may be found
	 */
	protected $template_dirs = array();

	/**
	 * @var \Microsite\App $app A reference to the App using this Renderer
	 */
	protected $app = null;

	/**
	 * Create a new Renderer, configuring its template directories
	 * @param string|array $template_dirs A template directory or an array of potential directories
	 */
	public function __construct($template_dirs, App $app) {
		if(!is_array($template_dirs)) {
			$template_dirs = array($template_dirs);
		}
		$this->template_dirs = $template_dirs;
		$this->app = $app;
	}

	/**
	 * Static method to return a Renderer instance, configuring its template directories
	 * @param string|array $template_dirs A template directory or an array of potential directories
	 * @return Renderer A Renderer instance of the class type used
	 */
	public static function create($template_dirs) {
		$class = get_called_class();
		$args = func_get_args();
		$r_class = new \ReflectionClass($class);
		return $r_class->newInstanceArgs($args);
	}

	/**
	 * Produce output from this Renderer using the specified template and variables
	 * @param string $template The name of a template found in the template directories
	 * @param array $vars An associative array of variables to pass into the template
	 * @return mixed The result of the rendering operation
	 */
	public abstract function render($template, $vars = []);

	/**
	 * Pass undefined methods on this renderer up to the App
	 * @param string $name A method name
	 * @param array $args An array of arguments
	 * @return mixed The result of the call on App
	 */
	public function __call($name, $args) {
		return call_user_func_array(array($this->app, $name), $args);
	}

	/**
	 * @param string $template Template filename to fetch
	 * @return bool|string Pathname of the template file or false if none found
	 */
	public function get_template_file($template) {
		foreach($this->template_dirs as $view_path) {
			if(substr($view_path, -1, 1) != '/' && $template[0] != '/') {
				$view_path .= '/';
			}
			$view_path .= str_replace('..', '', $template);
			if(file_exists($view_path)) {
				return $view_path;
			}
		}
		return false;
	}
}