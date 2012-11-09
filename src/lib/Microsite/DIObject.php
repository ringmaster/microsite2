<?php

namespace Microsite;

class DIObject
{
	protected $callback;
	protected $shared;
	protected static $result = null;

	public function __construct($callback, $shared = false) {
		$this->callback = $callback;
		$this->shared = $shared;
	}

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
