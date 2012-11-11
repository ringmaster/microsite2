<?php

namespace Microsite\Renderers;

use Microsite\Renderers\Renderer;

class PHPRenderer extends Renderer
{
	public function get_template_file($template) {
		foreach($this->template_dirs as $view_path) {
			if(substr($view_path, -1, 1) != '/' && $template[0] != '/') {
				$view_path .= '/';
			}
			$view_path .= $template;
			if(file_exists($view_path)) {
				return $view_path;
			}
		}
		return false;
	}

	public function render($template, $vars) {
		if(is_callable($template)) {
			$result = $template($vars);
		}
		elseif($template_file = $this->get_template_file($template)) {
			extract($vars);
			ob_start();
			include $template_file;
			$result = ob_get_clean();
		}
		else {
			throw new \Exception('The template file "' . $template . '" does not exist.');
		}
		return $result;
	}

}