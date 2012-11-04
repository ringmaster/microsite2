<?php

namespace Microsite;

class Segment extends RouteMatcher
{
	protected $regex = false;
	protected $route;
	protected $validations = array();

	public function __construct($route) {
		$this->route = $route;
	}

	public function match($value) {
		if(!$this->regex) {
			$this->generate_regex();
		}
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
		if(!$this->regex) {
			$this->generate_regex();
		}

		$segments = preg_split('#(:\w+(?:\#.+?\#)?)#', $this->route, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		$build = '';
		while(count($segments)) {
			$segment = array_shift($segments);
			if($segment[0]==':') {
				preg_match('#:(?P<name>\w+)(?:\#(?P<preg>.+)\#)?#', $segment, $matches);
				$build .= $vars[$matches['name']];
			}
			else {
				$build .= $segment;
			}
		}
		return $build;

	}

	protected function generate_regex() {
		$route = $this->route;
		$validations = $this->validations;

		$segments = preg_split('#(:\w+)#', $route, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		$regex = '';
		while(count($segments)) {
			$segment = array_shift($segments);
			if($segment[0]==':') {
				$regex .= '(?P<' . substr($segment, 1) . '>';

				if(isset($validations[$segment]) && is_string($validations[$segment])) {
					$regex .= $validations[$segment];
				}
				else {
					$regex .= '.+?';
				}
				$regex .= ')';
				if(count($segments) == 0) {
					$regex .= '$'; // If the last segment is a regex, be greedy until the end of the URL
				}
			}
			else {
				$regex .= preg_quote($segment, '#');
				if(count($segments) == 0) {
					$regex .= '$'; // If the last segment is a regex, be greedy until the end of the URL
				}
			}
		}

		$regex = '#^' . $regex . '#';

		$this->regex = $regex;
	}

	public function validate_fields($validations) {
		$this->validations = $validations;
	}
}