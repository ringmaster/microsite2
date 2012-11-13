<?php

namespace Microsite;

class App
{
	private $routes = array();
	public $parent = null;
	private $objects = array();
	public $template_dirs = array();
	protected $defaults = array();

	/**
	 * Constructor for App
	 */
	public function __construct() {
		$this->defaults = [
			'renderer' => new DIObject(function() {
				$template_dirs = $this->template_dirs;
				if(!is_array($template_dirs)) {
					$template_dirs = [$template_dirs];
				}
				$template_dirs = array_merge($template_dirs, [__DIR__ . '/Views']);
				return \Microsite\Renderers\PHPRenderer::create($template_dirs);
			}, true),
			'header' => new DIObject(function($header, $replace = null, $http_response_code = null){
				header($header, $replace, $http_response_code);
			}),
		];
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
	 * Run the app, parsing the requested URL and dispatching to the appropriate Route
	 * @param Request|null $request A Request to process, if null, default to $_SERVER['REQUEST_URI']
	 * @param Response|null $response A Response to render to
	 * @param App|null $parent
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function run($request = null, $response = null, $parent = null) {
		try {
			$null_response = false;
			$has_output = false;

			if(is_null($request)) {
				$request = new Request([
					'url' => $_SERVER['REQUEST_URI'],
				]);
			}

			if(is_null($response)) {
				$response = new Response([
					'renderer' => $this->renderer(),
				]);
				$null_response = true;
			}

			$this->parent = $parent;

			$output = false;
			foreach($this->routes as $route) {
				if($route->match($request)) {
					$request['_route'] = $route;
					$response['_app'] = $this;
					$result = $route->run($response, $request, $this);
					if($result) {
						$output = (string) $result;
						$has_output = true;
						break;
					}
				}
			}

			if($null_response) {
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
	 * @param Response|null $response A Response to render to
	 * @param Request|null $request A Request to process, if null, default to $_SERVER['REQUEST_URI']
	 * @param App|null $parent
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function __invoke(Response $response = null, Request $request = null, App $app = null) {
		if(isset($request['match_url'])) {
			$request = new Request([
				'url' => $request['match_url'],
			]);
		}
		return $this->run($request, $response, $app);
	}

	/**
	 * Simulate a specific URL request
	 * @param string $url The URL request to simulate
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function request($url) {
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
	 * Magic method __call(), used to invoke registered dependency injections
	 * @see App::share
	 * @see App::demand
	 * @param string $name The name of the method that was called on this object instance
	 * @param array $args The arguments that were used in the call
	 * @return mixed The result of the dependency injection or the default methods (added in App::__construct)
	 */
	public function __call($name, $args) {
		if(isset($this->objects[$name])) {
			$call_object = $this->objects[$name];
		}
		elseif(isset($this->defaults[$name])) {
			$call_object = $this->defaults[$name];
		}
		return $call_object->invoke($args);
	}

}
