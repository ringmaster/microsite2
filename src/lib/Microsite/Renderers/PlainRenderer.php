<?php

namespace Microsite\Renderers;

use \Microsite\Renderers\Renderer;

class PlainRenderer extends Renderer
{
	public function render($template, $vars = []) {
		if(is_callable($template)) {
			$result = $template($vars);
		}
		elseif($template_file = $this->get_template_file($template)) {
			$result = file_get_contents($template_file);
		}
		else {
			throw new \Exception('The template file "' . $template . '" does not exist.');
		}
		return $result;
	}

}