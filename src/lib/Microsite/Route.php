<?php

namespace Microsite;

use ReflectionFunction;
use ReflectionClass;

class Route
{
	private $url;
	private $handlers = array();
	private $validators = array();
	public $name = '';

	/**
	 * Create a Route that can match a URL
	 * @param string|RouteMatcher $url A string or RouteMatcher that can match a URL 
	 */
	public function __construct($url) {
		if(is_string($url)) {
			$url = new Segment($url);
		}
		$this->url = $url;
	}

	/**
	 * Add a handler to dispatch the request
	 * @param Callable $handler A handler to dispatch the request to
	 * @return Route Fluent interface
	 */
	public function add_handler($handler) {
		$this->handlers[] = $handler;
		if($handler instanceof App) {
			$this->url->fluid = true;
		}
		return $this;
	}

	/**
	 * Add a validation function to this Route
	 * @param Callable $validator A validation function with specific parameters: Request $request, string $match_url, Route $this
	 * @return Route Fluent interface
	 */
	public function validate($validator) {
		$args = func_get_args();
		foreach($args as $validator) {
			$this->validators[] = $validator;
		}
		return $this;
	}

	/**
	 * Add validation functions to fields
	 * @param array $validation An array of validation parameters for matched fields.
	 * The key is the field name in the URL, the value can be a string used as a regex,
	 * or a callback function that returns false if the field is not valid, or true if the field is valid.
	 * The callback function signature is: string $url_field_value, array $all_matches, RouteMatcher $routematcher, string $field_name
	 * @return Route Fluent interface
	 */
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

	public function build($vars = []) {
		return $this->url->build($vars);
	}

	public function convert($var, $fn) {
		$this->url->convert($var, $fn);
		return $this;
	}

	/**
	 * Check if this route matches the request
	 * @param Request $request
	 * @return bool True if this route matches the Request
	 */
	public function match(Request &$request) {
		$match = false;

		$match_url = $request['url'];
		if(isset($request['match_url'])) {
			$match_url = $request['match_url'];
		}

		if($this->url instanceof RouteMatcher && $matches = $this->url->match($match_url)) {
			foreach($matches as $key => $value) {
				$request[$key] = $value;
			}
			$match = true;
		}
		if($match) {
			foreach($this->validators as $validator) {
				$match &= $validator($request, $match_url, $this);
				if(!$match) break;
			}
		}
		return $match;
	}

	public function run(App $app) {
		foreach($this->handlers as $handler) {
			$result = false;
			ob_start();
			if(is_callable($handler)) {
				// Do some magic...
				$rf = new ReflectionFunction($handler);
				$params = $rf->getParameters();
				$exec_params = [];
				// @todo Should probably cache these, if possible...
				foreach($params as $param) {
					$param_class = $param->getClass();
					$param_type = '';
					if($param_class instanceof ReflectionClass) {
						$param_type = $param_class->getName();
					}
					switch($param_type) {
						case 'Microsite\Request':
							$exec_params[] = $app->request();
							break;
						case 'Microsite\Response':
							$exec_params[] = $app->response();
							break;
						default:
							$exec_params[] = $app;
							break 2;
					}
				}

				$result = call_user_func_array($handler, $exec_params);
			}
//			elseif($handler instanceof App) {
//				$result = $handler->run($request, $response, $app);
//			}
			$output = ob_get_clean();
			if(!$result) {
				$result = $output;
			}

			if($result) {
				return $result;
			}
		}

		return false;
	}
}

?>
