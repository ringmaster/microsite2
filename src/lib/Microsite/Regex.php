<?php

namespace Microsite;

class Regex extends RouteMatcher
{
	protected $regex;
	protected $validations;

	public function __construct($regex) {
		$this->regex = $regex;
	}

	public function match($value) {
		if(preg_match($this->regex, $value, $matches)) {
			foreach($this->validations as $field => $validation) {
				if(is_callable($validation)) {
					$matches = $validation($matches, $matches[substr($field, 1)], $this, $field);
				}
			}
			return $matches;
		}
		return false;
	}

	public function build($vars) {
		return '';
	}

	public function validate_fields($validations) {
		$this->validations = $validations;
	}
}
