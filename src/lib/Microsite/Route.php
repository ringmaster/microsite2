<?php

namespace Microsite;

class Route 
{
	private $url;
	private $handlers = array();
	private $validators = array();
	private $fluid_url = false;
	public $name = '';

	public function __construct($url) {
		if(is_string($url)) {
			$url = new Segment($url);
		}
		$this->url = $url;
	}

	public function add_handler($handler) {
		$this->handlers[] = $handler;
		if($handler instanceof App) {
			$this->fluid_url = true;
		}
	}

	public function validate($validator) {
		$this->validators[] = $validator;
		return $this;
	}

	public function validate_fields($validation) {
		$this->url->validate_fields($validation);
		return $this;
	}

	public function post() {
		return $this->via('POST');
	}

	public function get() {
		return $this->via('GET');
	}

	public function via($method) {
		if(is_string($method)) {
			$method = explode(',', $method);
		}
		return $this->validate(function() use($method) { return in_array($_SERVER['REQUEST_METHOD'], $method); });
	}

	public function build($vars) {
		return $this->url->build($vars);
	}

	public function convert($var, $fn) {
		$this->url->convert($var, $fn);
		return $this;
	}

	public function match(Request $request) {
		$match = false;

		$match_url = $request['url'];
		if(isset($request['match_url'])) {
			$match_url = $request['match_url'];
		}

		if(is_string($this->url)) {
			if($this->url == $match_url || ($this->fluid_url && strpos($match_url, $this->url) === 0)) {
				$match = true;
				$match_part = $this->url;
			}
		}
		elseif($this->url instanceof RouteMatcher && $matches = $this->url->match($match_url)) {
			foreach($matches as $key => $value) {
				$request[$key] = $value;
			}
			$match = true;
			$match_part = $matches[0];
		}
		if($match) {
			foreach($this->validators as $validator) {
				$match &= $validator($request, $match_url, $this);
				if(!$match) break;
			}
		}
		if($match && $this->fluid_url) {
			$request['match_url'] = str_replace($match_part, '', $match_url);
		}
		return $match;
	}

	public function run(Response $response, Request $request, App $app) {
		foreach($this->handlers as $handler) {
			$result = false;
			ob_start();
			if(is_callable($handler)) {
				$result = $handler($response, $request, $app);
			}
			elseif($handler instanceof App) {
				$result = $handler->run($request, $response, $app);
			}
			$output = ob_get_clean();
			if(!$result) {
				$result = $output;
			}

			if($result) {
				return $result;
			}
		}
	}
}

?>
