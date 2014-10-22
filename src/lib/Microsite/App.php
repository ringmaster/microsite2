<?php

namespace Microsite;

/**
 * Class App
 * @package Microsite
 * @method array template_dirs() Return an array of potential template directories
 * @method Renderers\PHPRenderer renderer() Obtain the default/active renderer for the app
 * @method void header() Output an HTTP header
 */
class App
{
	private $routes = array();
	public $parent = null;
	private $objects = array();
	public $template_dirs = array();
	protected $defaults = array();
	/** @var Route $route The route that the current request matched */
	public $route = null;
	private $middleware = array();

	/**
	 * Constructor for App
	 */
	public function __construct() {
		$this->share('template_dirs', function() {
			$template_dirs = $this->template_dirs;
			if(!is_array($template_dirs)) {
				$template_dirs = [$template_dirs];
			}
			$template_dirs = array_merge($template_dirs, [__DIR__ . '/Views']);
			return $template_dirs;
		});
		$this->share('renderer', function() {
				$template_dirs = $this->template_dirs();
				return Renderers\PHPRenderer::create($template_dirs, $this);
			}
		);
		$this->demand('header', function($header, $replace = null, $http_response_code = null){
				header($header, $replace, $http_response_code);
			}
		);
		$this->share('request', function($request = null) {
			if(is_null($request)) {
				$request_uri = explode('?', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '')[0];
				return new Request([
					'url' => $request_uri,
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
	 * @param Callable|Controller|Handler $handler One or more callbacks (as additional parameters) to execute to handle this route
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
	 * Create a new named middleware and handle it with a callback
	 * @param string $name Name of the route
	 * @param Callable $handler One or more callbacks (as additional parameters) to execute to handle this route
	 * @return App This App instance
	 */
	public function middleware($name, $handler) {
		static $ct = 0;
		$args = func_get_args();
		$name = array_shift($args);
		foreach($args as $handler) {
			$this->middleware["{$name}.{$ct}"] = $handler;
			$ct++;
		}
		return $this;
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
		if(isset($this->routes[$name])) {
			$result = $this->routes[$name];
			if($result instanceof Route) {
				return $result->build($args);
			}
		}
		return '';
	}

	/**
	 * Run the app, parsing the requested URL and dispatching to the appropriate Route
	 * @internal \Microsite\App|null $parent
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function run() {
		try {
			$do_output = false;
			$has_output = false;

			$response = $this->response();

			if(!$response->did_output) {
				$response->did_output = true;
				$do_output = true;
			}

			$output = false;
			foreach($this->routes as $route) {
				/** @var Route $route */
				if($route->match($this) && ($this->route == null || (
					$this->route->match_type() < $route->match_type() &&
					$this->route->get_url() == $route->get_url()
				))) {
					$this->route = $route;
				}
			}

			if(isset($this->route)) {
				foreach($this->middleware as $middleware) {
					$this->exec_params($middleware);
				}
				$result = $this->route->run($this);
				if($result) {
					$output = (string) $result;
					$has_output = true;
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
				$output = $response->render('error.php');
			}
			else {
				$output = var_export($e, true);
			}
		}
		echo $output;
		return $output;
	}

	/**
	 * Allow this object to be executed directly
	 * Example:  $app = new App();  $app();
	 * @param App|null $app A parent App instance with relevant properties
	 * @return bool|string Upon successful execution, the string of output produced, otherwise false
	 */
	public function __invoke($app = null) {
		if($app instanceof App) {
			$request = $app->request();
			$this->request($request);
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
	 * @param Request|null $request A default or preset Request object
	 * @return Request The request object for this App
	 */
	public function request($request = null) {
		return $this->dispatch_object('request', [$request]);
	}

	/**
	 * Get the Response object to use with this App
	 * @param Response|null $response A default or preset Response object
	 * @return Response The response object for this App
	 */
	public function response($response = null) {
		return $this->dispatch_object('response', [$response]);
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
			/** @var DIObject $call_object */
			$call_object = $this->objects[$name];
			return $call_object->invoke($args);
		}
		return null;
	}

	/**
	 * Provided with a function with an unknown parameter signature, execute with the demanded parameters based on type
	 * @param Callable|App $handler The function/method to call
	 * @return bool|mixed The result of calling the handler with the parameters it requires
	 */
	public function exec_params($handler) {
		$result = false;
		// Unwind DIObjects
		while($handler instanceof DIObject) {
			$handler = $handler($this);
		}
		if(is_string($handler) && method_exists($this, $handler)) {
			$rf = new \ReflectionMethod($this, $handler);
			$exec_params = $this->params_from_reflection($rf);
			$result = call_user_func_array([$this, $handler], $exec_params);
		}
		elseif($handler instanceof Handler) {
			$newapp = new App();
			$handler->load($newapp);
			$result = $newapp($this);
		}
		elseif(is_callable($handler)) {
			// Do some magic...
			$exec_params = [];
			if($handler instanceof App) {
				$exec_params[] = $this;
			}
			elseif(is_array($handler)) {
				list($object, $method) = $handler;
				$rm = new \ReflectionMethod($object, $method);
				$exec_params = $this->params_from_reflection($rm);
			}
			else {
				$rf = new \ReflectionFunction($handler);
				$exec_params = $this->params_from_reflection($rf);
			}
			$result = call_user_func_array($handler, $exec_params);
		}
		return $result;
	}

	/**
	 * Return a list of parameters to use based on the types indicated in the function declaration
	 * @param \ReflectionFunctionAbstract $rf The reflection object instance
	 * @return array An array of parameters to use
	 */
	protected function params_from_reflection(\ReflectionFunctionAbstract $rf) {
		$params = $rf->getParameters();
		// @todo Should probably cache these, if possible...
		$exec_params = [];
		foreach($params as $param) {
			$param_class = $param->getClass();
			$param_type = '';
			if($param_class instanceof \ReflectionClass) {
				$param_type = $param_class->getName();
			}
			switch($param_type) {
				case 'Microsite\Request':
					$exec_params[] = $this->request();
					break;
				case 'Microsite\Response':
					$exec_params[] = $this->response();
					break;
				default:
					$exec_params[] = $this;
					break 2;
			}
		}
		return $exec_params;
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
