<?php

namespace Microsite;

class App
{
	private $properties = array();
	private $routes = array();
	public $parent = null;
	private $objects = array();


	public function route($url, $handler) {
		$args = func_get_args();
		$url = array_shift($args);
		$route = new Route($url);
		foreach($args as $arg) {
			$route->add_handler($arg);
		}
		$this->routes[] = $route;
		return $route;
	}

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
					$result = $route->run($response, $request, $this);
					if($result) {
						$output = (string) $result;
						$has_output = true;
					}
				}
			}

			if($null_response) {
				if($has_output) {
					echo $output;
				}
				else {
					echo $response->render('404.php');
				}
			}
			return $output;
		}
		catch(\Exception $e) {
			$response['error'] = $e;
			echo $response->render('error.php');
		}
	}

	public function register($object_name, Callable $callback) {
		$this->objects[$object_name] = $callback;
	}

	public function __call($name, $args) {
		if(isset($this->objects[$name])) {
			$call_object = $this->objects[$name];
		}
		elseif(isset($this->defaults[$name])) {
			$call_object = $this->defaults[$name];
		}
		if(is_callable($call_object)) {
			$object = call_user_func_array($call_object, $args);
			$this->objects[$name] = $object;
		}
		else {
			$object = $call_object;
		}
		return $object;
	}

}
