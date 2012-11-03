<?php

namespace Microsite;

abstract class RouteMatcher
{
	public abstract function match($value);
	public abstract function build($vars);
}