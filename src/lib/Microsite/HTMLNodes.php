<?php

namespace Microsite;

/**
 * Contain a list of DOM nodes and provide access to them
 */
class HTMLNodes extends \ArrayObject
{

	/**
	 * Overridden constructor for \ArrayObject
	 * Converts regular \DomNodes in this array to HTMLNodes so that they have new methods
	 * @param null|array|\DomNodeList $input A list of objects to initialize this \ArrayObject with
	 * @param int $flags
	 * @param string $iterator_class
	 */
	public function __construct($input = null, $flags = 0, $iterator_class = "ArrayIterator")
	{
		$altered_input = array();
		if($input instanceof \DOMNodeList) {
			foreach($input as $i) {
				if($i instanceof \DOMNode) {
					$altered_input[] = new HTMLNode($i);
				}
				else {
					$altered_input[] = $i;
				}
			}
		}
		parent::__construct($altered_input, $flags, $iterator_class);
	}

	/**
	 * Make calls against this list to execute that method on all of the items within it
	 * @param string $method The method called on this list
	 * @param array $args Arguments to this call
	 * @return HTMLNodes $this
	 */
	public function __call($method, $args)
	{
		foreach($this as $htmlnode) {
			call_user_func_array(array($htmlnode, $method), $args);
		}
		return $this;
	}

	/**
	 * Set the value of a parameter on every item of this array
	 * @param string $name The name of the parameters
	 * @param mixed $value The value to assign to that parameter
	 */
	public function __set($name, $value)
	{
		foreach($this as $htmlnode) {
			$htmlnode->$name = $value;
		}
	}
}

?>