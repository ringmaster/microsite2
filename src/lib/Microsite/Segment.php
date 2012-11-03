<?php

namespace Microsite;

class Segment extends RouteMatcher
{
	private $regex;
	private $route;

	public function __construct($route) {

		$this->route = $route;

		$segments = preg_split('#(:\w+(?:\#.+?\#)?)#', $route, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

		$regex = '';
		while(count($segments)) {
			$segment = array_shift($segments);
			if($segment[0]==':') {
				preg_match('#:(?P<name>\w+)(?:\#(?P<preg>.+)\#)?#', $segment, $matches);
				$regex .= '(?P<' . $matches['name'] . '>';
				if(empty($matches['preg'])) {
					$regex .= '.+?';
				}
				else {
					$regex .= $matches['preg'];
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

	public function match($value) {
		if(preg_match($this->regex, $value, $matches)) {
			return $matches;
		}
		return false;
	}

	public function build($vars) {

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
}