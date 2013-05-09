<?php

namespace Microsite;

/**
 * A representation of the node on which we can call custom methods
 * @property string class The CSS class of this node
 */
class HTMLNode
{
	/** @var \DomNode $node */
	public $node;

	/**
	 * Constructor for this node
	 * @param \DOMNode $node The actual node we're trying to access
	 */
	function __construct($node)
	{
		$this->node = $node;
	}

	/**
	 * Get the value of an attribute of this node
	 * @param string $name The name of the attribute value to obtain
	 * @return mixed The value of the attribute
	 */
	function __get($name)
	{
		switch($name) {
			default:
				if($attribute = $this->node->attributes->getNamedItem($name)) {
					return $attribute->nodeValue;
				}
				if($attribute = $this->node->attributes->getNamedItem(str_replace('_', '-', $name))) {
					return $attribute->nodeValue;
				}
				return null;
		}
	}

	/**
	 * Set the value of an attribute on this node
	 * @param string $name The name of the attribute to set
	 * @param mixed $value The value of the parameter
	 */
	function __set($name, $value)
	{
		switch($name) {
			default:
				if(!$attribute = $this->node->attributes->getNamedItem($name)) {
					$attribute = $this->node->ownerDocument->createAttribute($name);
					$this->node->appendChild($attribute);
				}
				$attribute->nodeValue = $value;

				break;
		}
	}

	/**
	 * Add a class to the class attribute of this node
	 * @param string|array $newclass The class or classes to add to this node
	 */
	function add_class($newclass)
	{
		$class = $this->class;
		$classes = preg_split('#\s+#', $class);
		$newclass = is_array($newclass) ? $newclass : preg_split('#\s+#', $newclass);
		$classes = array_merge($classes, $newclass);
		$classes = array_unique($classes);
		$this->class = trim(implode(' ', $classes));
	}

	/**
	 * Remove a class from this node
	 * @param string|array $removeclass The class or classes to remove from this node
	 */
	function remove_class($removeclass)
	{
		$class = $this->class;
		$classes = preg_split('#\s+#', $class);
		$removeclass = is_array($removeclass) ? $removeclass : preg_split('#\s+#', $removeclass);
		$classes = array_diff($classes, $removeclass);
		$classes = array_unique($classes);
		$this->class = trim(implode(' ', $classes));
	}

	/**
	 * Remove this node from the DOM
	 */
	function remove()
	{
		$this->node->parentNode->removeChild($this->node);
	}

	/**
	 * Append HTML as a child of this node
	 * @param string $html The HTML to add, which is subsequently parsed into DOMNodes
	 */
	function append_html($html)
	{
		$frag = $this->node->ownerDocument->createDocumentFragment();
		$frag->appendXML($html);
		$this->node->appendChild($frag);
	}

	/**
	 * Move the children of this node into this node's parent, just before this node in the DOM tree
	 */
	function promote_children()
	{
		while($this->node->hasChildNodes()) {
			$child = $this->node->firstChild;
			$this->node->removeChild($child);
			$this->node->parentNode->insertBefore($child, $this->node);
		}
	}

	/**
	 * Get this node's string representation
	 * @return string The node's string representation
	 */
	function get()
	{
		return $this->node->ownerDocument->saveXML($this->node);
	}

	/**
	 * Get the HTML of all child elements of this node
	 * @return string The requested HTML
	 */
	function inner_html()
	{
		$inner_html = '';
		foreach($this->node->childNodes as $child) {
			$tmp_dom = new \DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$inner_html .= trim($tmp_dom->saveXML());
		}
		// Kludgey hack to remove doctype spec
		$inner_html = preg_replace('#^\s*<\?xml(\s.*)?\?>\s*#', '', $inner_html);
		return $inner_html;
	}

}

?>