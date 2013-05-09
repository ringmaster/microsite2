<?php

namespace Microsite;

/**
 * A *very* simple DOMDocument wrapper, used to more easily query HTML values and append/remove elements
 */

class HTMLDoc
{
	/** @var \DOMXPath $xp */
	public $xp;
	/** @var \DomDocument $dom */
	public $dom;

	/**
	 * Create a HTMLDoc object
	 * @param string $html The HTML to parse
	 */
	public function __construct($html)
	{
		$this->dom = new \DOMDocument();
		libxml_use_internal_errors(true);
		$this->dom->loadHTML($html);
		$this->xp = new \DOMXPath($this->dom);
	}

	/**
	 * Fluent constructor for HTMLDoc objects
	 * @param string $html The HTML to parse
	 * @return HTMLDoc An instance of the HTMLDoc object created
	 */
	public static function create($html)
	{
		return new HTMLDoc($html);
	}

	/**
	 * Find elements in the DOM based on CSS selector
	 * @param string $find A CSS selector
	 * @return HTMLNodes A list of qualifying nodes
	 */
	public function find($find)
	{
		$expression = new HTMLSelector($find);

		return $this->query($expression->toXPath());
	}

	/**
	 * Find the first element in the DOM based on a CSS selector
	 * @param string $find A CSS selector
	 * @return HTMLNode A qualifying node
	 */
	public function find_one($find)
	{
		$expression = new HTMLSelector($find);

		$array = $this->query($expression->toXPath());
		return reset($array);
	}

	/**
	 * Pass a query on to the XPath query method
	 * @param string $expression An XPath expression
	 * @param \DomNode $contextnode The context of the query, by default, the root node
	 * @param bool $registerNodeNS true by default, false to disable the automatic registration of the context node
	 * @return \DOMNodeList A list of qualifying nodes
	 */
	public function query($expression, \DomNode $contextnode = null, $registerNodeNS = true)
	{
		return new HTMLNodes($this->xp->query($expression, $contextnode, $registerNodeNS));
	}

	/**
	 * Return the HTML represented by the DOM
	 * @return string The requested HTML
	 */
	public function get()
	{
		$body_content = $this->query('//body/*');
		$output = '';
		foreach($body_content as $node) {
			$output .= $this->dom->saveXML($node->node);
		}
		return $output;
	}

	/**
	 * Render this DOM as a string
	 * @return string the string representation of the DOM
	 */
	function __toString()
	{
		return $this->get();
	}

}

?>