<?php

namespace Microsite;

class Response extends \ArrayObject
{
	private $properties = [];

	/**
	 * Create a Response object and store its properties
	 * @param array|null $properties An array of initial properties for the response
	 */
	public function __construct($properties) {
		$this->properties = $properties;
	}

	/**
	 * Set the default rendering object
	 * @param Renderers\Renderer $renderer The default rendering object
	 */
	public function set_renderer($renderer) {
		$this->properties['renderer'] = $renderer;
	}

	/**
	 * Retreive all of the variables assigned to be rendered
	 * @return array An associative array of the assigned variables
	 */
	public function get_vars() {
		return $this->getArrayCopy();
	}

	/**
	 * Retrieve all of the properties associated with this response
	 * @return array|null An associative array of the properties
	 */
	public function get_props() {
		return $this->properties;
	}

	/**
	 * Use the default renderer to render a partial view
	 * @param string|callable $view The name of a view file or a callable function that can be used to render output in the renderer
	 * @param array $vars An associative array of variables to pass to the view file or function for rendering
	 * @return mixed The result of the rendering operation
	 */
	public function partial($view, $vars) {
		$default_template_dirs = array(__DIR__ . '/Views');
		$renderer = isset($this->properties['renderer']) ? $this->properties['renderer'] : \Microsite\Renderers\PHPRenderer::create($default_template_dirs);

		$result = $renderer->render($view, $vars);

		return $result;
	}

	/**
	 * Render all of the variables assigned to this Response using a view file or function
	 * @param string|callable $view The name of a view file or callable function that can be used to render output in the renderer
	 * @return mixed The result of the rendering operation
	 */
	public function render($view = null) {
		$vars = $this->getArrayCopy();
		$vars['_response'] = $this;
		$vars['get_route'] = function($name, $vars = []){return $this->properties['app']->get_route($name)->build($vars);};

		if(!isset($view)) {
			$view = 'No view or output method was set for this request.';
		}
		return $this->partial($view, $vars);
	}

	/**
	 * Render an array of templates in order, with an optional wrapper
	 * @param array $templates An array of template names or functions
	 * @param null|callable|string $wrapper An optional template name or function
	 * @return string The output of the built templates
	 */
	public function build($templates, $wrapper = null) {
		$output = '';
		foreach($templates as $template) {
			$content = $this->render($template);
			if(!is_null($wrapper)) {
				$content = $this->partial($wrapper, ['content' => $content]);
			}
			$output .= $content;
		}
		return $output;
	}

	/**
	 * Render this Response to a string
	 * @return string The result of the rendering operation
	 */
	public function __toString() {
		return $this->render();
	}

	/**
	 * Redirect the response via HTTP headers to a new location
	 * @param string $url The URL to redirect to
	 */
	public function redirect($url) {
		header('location: ' . $url);
		exit();
	}

}