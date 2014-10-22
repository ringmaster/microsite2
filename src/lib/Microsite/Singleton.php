<?php

namespace Microsite;

class Singleton {

	private static $instances = [];


	/**
	 * Returns the Singleton instance of this class.
	 * @return Singleton The Singleton instance.
	 */
	public static function get_instance()
	{
		$class = get_called_class();
		if (!isset(self::$instances[$class])) {
			$args = func_get_args();
			$r_class = new \ReflectionClass($class);
			self::$instances[$class] = $r_class->newInstanceArgs($args);
		}
		return self::$instances[$class];
	}

	/**
	 * Protected constructor to prevent creating a new instance of the
	 * *Singleton* via the `new` operator from outside of this class.
	 */
	protected function __construct()
	{
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}

	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup()
	{
	}
}