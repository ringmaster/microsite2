<?php

namespace Microsite;

class Response extends \ArrayObject
{
	private $properties = [];

	public function __construct($properties) {
		$this->properties = $properties;
	}

	public function set_renderer($renderer) {
		$this->properties['renderer'] = $renderer;
	}

	public function get_vars() {
		return $this->getArrayCopy();
	}

	public function get_props() {
		return $this->properties;
	}

	public function partial($view, $vars) {
		$default_template_dirs = array(__DIR__ . '/Views');
		$renderer = isset($this->properties['renderer']) ? $this->properties['renderer'] : \Microsite\Renderers\PHPRenderer::create($default_template_dirs);

		$result = $renderer->render($view, $vars);

		return $result;
	}

	public function render($view = null) {
		$vars = $this->getArrayCopy();
		$vars['_response'] = $this;

		if(!isset($view)) {
			$view = 'No view or output method was set for this request.';
		}
		return $this->partial($view, $vars);
	}

	public function __toString() {
		return $this->render();
	}

	public function redirect($url) {
		header('location: ' . $url);
		exit();
	}

}