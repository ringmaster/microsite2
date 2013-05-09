<?php

namespace Microsite;

/**
 * A representation of a CSS selector, with methods to convert CSS to XPath
 */
class HTMLSelector
{
	/** @var string $selector the CSS selector */
	public $selector;

	/**
	 * Constructor for setting the CSS selector in this class
	 * @param string $selector A CSS selector
	 */
	public function __construct($selector)
	{
		$this->selector = $selector;
	}

	/**
	 * Convert the CSS selector to an XPath selector
	 * @return string
	 */
	public function toXPath()
	{
		preg_match_all('/[:\.\w#]+|>|\+|,/sim', $this->selector, $parts);
		$xpath = '';
		$rooting = '//'; // This is XPath for "any descndant of"
		$stack = array();
		foreach($parts[0] as $part) {
			switch($part) {
				case '>': // Direct descendant of
					$rooting = '/';
					break;
				case '+': // Sibling of, not sure how to handle that yet
					break;
				case ',': // OR...
					$stack[] = '|';
					$rooting = '//';
					break;
				default:
					$xpath_part = $this->get_part_xpath($part, $stack);
					$stack[] = $rooting;
					$stack[] = $xpath_part;
					$rooting = '//';
					break;
			}
		}
		$xpath = implode('', $stack);
		return $xpath;
	}

	/**
	 * Interal method for parsing the CSS parts into XPath parts
	 * @param string $part Some atomic part of a CSS selector
	 * @param array $stack An array of previous xpath parts
	 * @return string The equivalent XPath part
	 */
	private function get_part_xpath($part, $stack)
	{
		// For "[name=value]" $2 = "name", $3 = "=", $5 = "value"
		preg_match_all('/\[(([^\]]+?)(([~\-!]?=)([^\]]+))?)\]|\W?\w+/', $part, $matches, PREG_SET_ORDER);
		$props = array();
		$tag = '*';
		foreach($matches as $match) {
			if($match[0][0] == '#') {  // it's an ID
				$props[] = '[@id = "' . substr($match[0], 1) . '"]';
			}
			elseif($match[0][0] == '.') {  // it's a class
				$props[] = '[contains(@class, "' . substr($match[0], 1) . '")]';
			}
			elseif($match[0][0] == ':') { // it's a pseudo-selector, oh noes!
				// @todo Ack!  Do something!
				$last = end($stack);

			}
			elseif($match[0][0] == '[') { // it's a property-based selector
				if(empty($match[5])) { // Just checking if the element has the name
					$props[] = '[@' . $match[1] . ']';
				}
				else {
					$props[] = '[@' . $match[1] . ']';
				}
			}
			else { // it's a tag
				$tag = $match[0];
			}
		}
		return $tag . implode('', $props);
	}
}

?>