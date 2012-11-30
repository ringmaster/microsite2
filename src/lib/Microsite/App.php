<?php

namespace Microsite;

class App
{
	private $routes = array();
	public $parent = null;
	private $objects = array();
	public $template_dirs = array();
	protected $defaults = array();
	public $route = null;

	/**
	 * Constructor for App
	 */
	public function __construct() {
		$this->share('renderer', function() {
				$template_dirs = $this->template_dirs;
				if(!is_array($template_dirs)) {
					$template_dirs = [$template_dirs];
				}
				$template_dirs = array_merge($template_dirs, [__DIR__ . '/Views']);
				return \Microsite\Renderers\PHPRenderer::create($template_dirs, $this);
			}
		);
		$this->demand('header', function($header, $replace = null, $http_response_code = null){
				header($header, $replace, $http_response_code);
			}
		);
		$this->share('request', function($request = null) {
			if(is_null($request)) {
				return new Request([
					'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
				]);
			}
			return $request;
		});
		$this->share('response', function($response = null) {
			if(is_null($response)) {
				return new Response([
					'renderer' => $this->renderer(),
					'app' => $this,
				]);
			}
			return $response;
		});
	}


	/**
	 * Create a new named route and handle it with a callback
	 * @param string $name Name of the route
	 * @param string|Regex|Segment $url The URL to match
	 * @param Callable $handler One or more callbacks (as additional parameters) to execute to handle this route
	 * @return Route The route that is created to handle this request
	 */
	public function route($name, $url, $handler) {
		$args = func_get_args();
		$name = array_shift($args);
		$url = array_shift($args);
		$route = new Route($url);
		foreach($args as $arg) {
			$route->add_handler($arg);
		}
		$this->routes[$name] = $route;
		return $route;
	}

	/**
	 * Return the Route object for a named route
	 * @param string $name The name of the route to return
	 * @return Route The route object associated to this name
	 */
	public function get_route($name) {
		$result = isset($this->routes[$name]) ? $this->routes[$name] : null;
		return $result;
	}

	/**
	 * Return a built URL string for a named route and parameters
	 * @param string $name The name of a route
	 * @param array $args An array of optional parameters for the route build
	 * @return string The URL built from the provided parameters
	 */
	public function get_url($name, $args = []) {
		$result = isset($this->routes[$name]) ? $this->routes[$name] : null;
		return isset($result) ? $result->build($args) : '';
	}

	/**
	 * Run the app, parsing the requested URL and dispatching to the appropriate Route
	 * @param App|null $parent
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function run() {
		try {
			$do_output = false;
			$has_output = false;

			$request = $this->request();
			$response = $this->response();

			if(!$response->did_output) {
				$response->did_output = true;
				$do_output = true;
			}

			$output = false;
			foreach($this->routes as $route) {
				/** @var Route $route */
				if($route->match($request)) {
					$this->route = $route;
					$result = $route->run($this);
					if($result) {
						$output = (string) $result;
						$has_output = true;
						break;
					}
				}
			}

			if($do_output) {
				if($has_output) {
					echo $output;
				}
				else {
					$this->header('HTTP/1.1 404 Not Found');
					echo $response->render('404.php');
				}
			}
			return $output;
		}
		catch(\Exception $e) {
			$response['error'] = $e;
			$this->header('HTTP/1.1 500 Internal Server Error');
			if($response instanceof Response) {
				echo $response->render('error.php');
			}
			else {
				var_dump($e);
			}
		}
	}

	/**
	 * Allow this object to be executed directly
	 * Example:  $app = new App();  $app();
	 * @param App|null $parent
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function __invoke() {
		$request = $this->request();
		if(isset($request['match_url'])) {
			$request['url'] = $request['match_url'];
		}
		return $this->run();
	}

	/**
	 * Simulate a specific URL request
	 * @param string $url The URL request to simulate
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function simulate_request($url) {
		$_SERVER['REQUEST_URI'] = $url;
		return $this->run();
	}

	/**
	 * Register a shared object (return the result of the first call on subsequent calls) as a dependency injection
	 * @param string $object_name The name of the object
	 * @param Callable $callback A function the returns the object to create upon reference
	 */
	public function share($object_name, Callable $callback) {
		$this->objects[$object_name] = new DIObject($callback, true);
	}

	/**
	 * Register an on-demand object (execute the function on every call) as a dependency injection
	 * @param string $object_name The name of the object
	 * @param Callable $callback A function the returns the object to create upon reference
	 */
	public function demand($object_name, Callable $callback) {
		$this->objects[$object_name] = new DIObject($callback);
	}

	/**
	 * Get the Request object to use with this App
	 * @return Request The request object for this App
	 */
	public function request() {
		return $this->dispatch_object('request', []);
	}

	/**
	 * Get the Response object to use with this App
	 * @return Response The response object for this App
	 */
	public function response() {
		return $this->dispatch_object('response', []);
	}

	/**
	 * Internal method to invoke registered dependency injections
	 * @see App::share
	 * @see App::demand
	 * @param string $name The name of the method that was called on this object instance
	 * @param array $args The arguments that were used in the call
	 * @return mixed The result of the dependency injection or the default methods (added in App::__construct)
	 */
	protected function dispatch_object($name, $args) {
		if(isset($this->objects[$name])) {
			$call_object = $this->objects[$name];
			return $call_object->invoke($args);
		}
		return null;
	}

	/**
	 * Magic method __call(), used to invoke registered dependency injections
	 * @see App::share
	 * @see App::demand
	 * @param string $name The name of the method that was called on this object instance
	 * @param array $args The arguments that were used in the call
	 * @return mixed The result of the dependency injection or the default methods (added in App::__construct)
	 */
	public function __call($name, $args) {
		return $this->dispatch_object($name, $args);
	}

}
