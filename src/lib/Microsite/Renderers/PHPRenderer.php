<?php

namespace Microsite\Renderers;

use \Microsite\Renderers\Renderer;

class PHPRenderer extends Renderer
{
	public function render($template, $vars = []) {
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