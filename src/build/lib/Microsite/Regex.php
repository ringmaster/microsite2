<?php

namespace Microsite;

class Regex
{
	private $regex;
	
	public function __construct($regex) {
		$this->regex = $regex;
	}

	public function match($value) {
		if(preg_match($this->regex, $value, $matches)) {
			return $matches;
		}
		return false;
	}
}