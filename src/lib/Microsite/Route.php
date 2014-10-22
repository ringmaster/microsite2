<?php

namespace Microsite;

use ReflectionFunction;
use ReflectionClass;

class Route
{
	private $url;
	private $handlers = array();
	private $validators = array();
	private $orig_url;
	public $name = '';
	protected $types = null;

	/**
	 * Create a Route that can match a URL
	 * @param string|RouteMatcher $url A string or RouteMatcher that can match a URL 
	 */
	public function __construct($url) {
		$this->orig_url = $url;
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
		if($handler instanceof App || $handler instanceof RouteMatcher) {
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
	 * @return RouteMatcher
	 */
	public function get_url() {
		return $this->url;
	}

	public static function get_best_mime($mime_types = null) {
		// Values will be stored in this array
		static $accept_types = null;

		if(empty($accept_types)) {
			// Accept header is case insensitive, and whitespace isn't important
			$accept = strtolower(str_replace(' ', '', $_SERVER['HTTP_ACCEPT']));
			// divide it into parts in the place of a ","
			$accept = explode(',', $accept);
			foreach ($accept as $a) {
				// the default quality is 1.
				$q = 1;
				// check if there is a different quality
				if (strpos($a, ';q=')) {
					// divide "mime/type;q=X" into two parts: "mime/type" and "X"
					list($a, $q) = explode(';q=', $a);
				}
				// mime-type $a is accepted with the quality $q
				// WARNING: $q == 0 means, that mime-type isn't supported!
				$accept_types[$a] = $q;
			}
			arsort($accept_types);
		}

		// if no parameter was passed, just return parsed data
		if (!$mime_types) {
			return $accept_types;
		}

		$mime_types = array_map('strtolower', (array)$mime_types);

		// let's check our supported types:
		foreach ($accept_types as $mime => $q) {
			if($q) {
				if(in_array($mime, $mime_types)) {
					return [$mime => floatval($q)];
				}
				if($mime == '*/*') {
					return [reset($mime_types) => floatval($q)];
				}
			}
		}
		// no mime-type found
		return null;
	}

	public function match_type() {
		$mime_types = empty($this->types) ? ['text/html'] : $this->types;
		$type_result = self::get_best_mime($mime_types);
		$match_type = reset($type_result);
		return $match_type;
	}

	public function type($type) {
		$this->types[] = $type;
		return $this;
	}

	/**
	 * Check if this route matches the request
	 * @param App $app
	 * @return bool True if this route matches the Request
	 */
	public function match(App $app) {
		$match = false;

		$request = $app->request();

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
			ob_start();
			$result = $app->exec_params($handler);
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
