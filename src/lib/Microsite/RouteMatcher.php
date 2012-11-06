<?php

namespace Microsite;

abstract class RouteMatcher
{
	public abstract function match($value);
	public abstract function build($vars);
	public abstract function validate_fields($validation);
	public abstract function convert($field, $fn);
}