<?php

namespace Microsite;

/**
 * Dependency Injection Object
 * Stores the closure and its properties for returning an object that will be created on-demand at runtime
 */
class DIObject
{
	protected $callback;
	protected $shared;
	protected $result = null;

	/**
	 * Create a new DIObject instance
	 * @param Callable $callback The callback function to execute to produce the DI object
	 * @param bool $shared True if this object should only be created once
	 */
	public function __construct($callback, $shared = false) {
		$this->callback = $callback;
		$this->shared = $shared;
	}

	/**
	 * Execute the function that produces the DI object, return it
	 * @param array $args The array of arguments to pass to the callback to create the DI object
	 * @return mixed|null The return value of the callback associated to this DIObject
	 */
	public function invoke($args) {
		if($this->shared) {
			if(!isset($this->result)) {
				$this->result = call_user_func_array($this->callback, $args);
			}
			return $this->result;
		}
		return call_user_func_array($this->callback, $args);
	}
}
