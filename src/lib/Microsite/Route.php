<?php

namespace Microsite;

class Route 
{
	private $url;
	private $handlers = array();
	private $validators = array();
	private $fluid_url = false;

	public function __construct($url) {
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

	public function post() {
		return $this->validate(function(){ return $_SERVER['REQUEST_METHOD'] == 'POST'; });
	}

	public function get() {
		return $this->validate(function(){ return $_SERVER['REQUEST_METHOD'] == 'GET'; });
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
		elseif($this->url instanceof Regex && $matches = $this->url->match($match_url)) {
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