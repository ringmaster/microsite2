<?php

namespace Microsite;

class Regex extends RouteMatcher
{
	protected $regex;
	protected $validations;
	protected $conversions = array();

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
			// If $matches is false, then field validation failed.  Don't convert if field validation failed.
			if($matches) {
				foreach($this->conversions as $field => $fn) {
					$matches[$field] = $fn((isset($matches[$field]) ? $matches[$field] : null), $field);
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

	public function convert($field, $fn) {
		$this->conversions[$field] = $fn;
	}
}
