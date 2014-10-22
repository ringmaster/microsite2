<?php

namespace Microsite;

class Template
{
	static $vars;
	static $filters;
	static $iterator_idx;
	public $file;
	public $filename;
	protected $position = 0;

	/**
	 * Register this class to a specific stream wrapper protocol
	 * @param string $protocol The protocol to associate this class to
	 */
	public static function register($protocol) {
		stream_wrapper_register($protocol, '\Microsite\Template')
			or die('Failed to register protocol');
	}

	/**
	 * Open a Template stream
	 *
	 * @param string $path Path of the opened resource, including the protocol specifier
	 * @param string $mode Mode used to open the file
	 * @param integer $options Bitmask options for opening this stream
	 * @param string $opened_path The actual path opened if using relative path, by reference
	 * @return boolean true on success
	 */
	function stream_open( $path, $mode, $options, &$opened_path )
	{
		$this->filename = substr( $path, 5 );
		$this->file = file_get_contents( $this->filename );

		// This processed value should cache and invalidate if a checksum of the template changes!  :)
		$this->file = $this->process( $this->file );

		$this->position = 0;

		return true;
	}

	/**
	 * Read data from a Template stream
	 *
	 * @param integer $count Number of characters to read from the current position
	 * @return string Characters read from the stream
	 */
	function stream_read( $count )
	{
		if ( $this->stream_eof() ) {
			return false;
		}
		$ret = substr( $this->file, $this->position, $count );
		$this->position += strlen( $ret );
		return $ret;
	}

	/**
	 * Write data to a Template stream
	 *
	 * @param string $data Data to write
	 * @return boolean false, since this stream type is read-only
	 */
	function stream_write( $data )
	{
		// Template streams are read-only
		return false;
	}

	/**
	 * Report the position in the stream
	 *
	 * @return integer the position in the stream
	 */
	function stream_tell()
	{
		return $this->position;
	}

	/**
	 * Report whether the stream is at the end of the file
	 *
	 * @return boolean true if the file pointer is at or beyond the end of the file
	 */
	function stream_eof()
	{
		return $this->position >= strlen( $this->file );
	}

	/**
	 * Seek to a specific position within the stream
	 *
	 * @param integer $offset The offset from the specified position
	 * @param integer $whence The position to seek from
	 * @return boolean true if seek was successful
	 */
	function stream_seek( $offset, $whence )
	{
		switch ( $whence ) {
			case SEEK_SET:
				if ( $offset < strlen( $this->file ) && $offset >= 0 ) {
					$this->position = $offset;
					return true;
				}
				else {
					return false;
				}
				break;

			case SEEK_CUR:
				if ( $offset >= 0 ) {
					$this->position += $offset;
					return true;
				}
				else {
					return false;
				}
				break;

			case SEEK_END:
				if ( strlen( $this->file ) + $offset >= 0 ) {
					$this->position = strlen( $this->file ) + $offset;
					return true;
				}
				else {
					return false;
				}
				break;

			default:
				return false;
		}

	}

	/**
	 * Return fstat() info as required when calling stats on the stream
	 * @return array An array of stat()-like result data
	 */
	function stream_stat()
	{
		return stat($this->filename);
	}

	/**
	 * Return fstat() info as required when calling stats on the stream
	 * @return array An array of stat()-like result data
	 */
	function url_stat()
	{
		$result = stat($this->filename);
		$result['mtime'] = time();
		return $result;
	}

	/**
	 * Process the template for template tags
	 * @param string $template The text of the template
	 * @param bool $sub_component If true, this is a sub-template fragment, and shouldn't have the setup prepended
	 * @return string The template compiled to PHP
	 */
	public static function process( $template, $sub_component = false )
	{
		$template = str_replace('<?php', '&lt;?php', $template);

		$tags = self::get_tags();
		$tags = array_map(function($item){return $item['regex'];}, $tags);

		$regex = '#' . implode('|', $tags)  . '#si';

		preg_match_all($regex, $template, $segments, PREG_SET_ORDER);

		$template = self::compile($template, $segments, $sub_component);

		return $template;
	}

	public static function get_tags()
	{
		$tags = [
			'variable' => [
				'regex' => '\{\$(?P<variable>[\w\.]+)+(?:\s+(?P<filters>.+?))?\}(?:(?P<inside>.*?)\{/\$\1+\})?',
				'fn' => function($matches){return Template::do_variable($matches);},
			],
			'if' => [
				'regex' => '(?P<initial>\{\?\s*(?P<condition>.+?)\s*\})\s*(?P<inside_if>.+?)\s*\{/\?\}',
				'fn' => function($matches){return Template::do_if($matches);},
			],
		];
		return $tags;
	}

	/**
	 * Set the template variables into this class for use
	 * @param array $vars A key/value array of template variables
	 */
	public static function vars($vars) {
		self::$vars = $vars;
	}

	/**
	 * Returns an array of keyed functions to be used as tag output filters
	 * @return array An array of functions by key
	 */
	public static function filters() {
		return [
			'escape' => function($in){ return htmlspecialchars($in); },
			'url' => function($in){ return urlencode($in); },
			'json' => function($in){ return json_encode($in); },
		];
	}

	/**
	 * Process a value through a series of filers
	 * @param mixed $value Some value
	 * @param string $filter_1 One or more filters to apply to the value, as additional, optional arguments
	 * @return mixed|string The filtered value
	 */
	public static function filter($value, $filter_1 = null) {
		self::$filters = self::filters();
		$args = func_get_args();
		$value = array_shift($args);
		while($args) {
			$segment = array_shift($args);
			if(isset(self::$filters[$segment])) {
				$fn = self::$filters[$segment];
				$value = $fn($value, $args);
			}
		}
		return $value;
	}

	/**
	 * @param $segment
	 * @return string
	 */
	public static function do_variable($segment) {
		if(isset($segment['inside'])) {
			// Should we just push the variable into the current context, or loop over it?
			if(preg_match('#\.$#', $segment['variable'])) {
				// Push the variable into the context
				$variable = substr($segment['variable'], 0, -1);
				$replacement = '<?php $_context["' . $variable . '"] = T::sub($_context, "' . $variable . '"); ?>';
				$replacement .= self::process($segment['inside'], true);
				$replacement .= '<?php array_pop($_context); ?>';
			}
			else {
				// Loop over this variable
				$variable = $segment['variable'];
				self::$iterator_idx++;
				$iterator_value = '$_i_' . self::$iterator_idx;
				$replacement = '<?php foreach(T::sub($_context, "' . $variable . '") as ' . $iterator_value . '): $_context["' . $variable . '"] = ' . $iterator_value . '; ?>';
				$replacement .= self::process($segment['inside'], true);
				$replacement .= '<?php array_pop($_context); endforeach; ?>';
			}
		}
		else {
			$default_filters = array('escape');
			$filters = array();
			if(isset($segment['filters'])) {
				$filters = explode(' ', $segment['filters']);  // @todo make this not so simplistic
			}
			$filters = array_merge($default_filters, $filters);
			$filters = array_combine($filters, $filters);
			foreach($filters as $filter) {
				if($filter[0] == '-') {
					unset($filters[$filter]);
					unset($filters[substr($filter, 1)]);
				}
			}
			$filter_args = implode('", "', $filters);
			if($filters) {
				$filter_args = ',"' . $filter_args . '"';
			}
			$replacement = '<?= T::filter(T::sub($_context, "' . $segment['variable'] . '")' . $filter_args . ') ?>';
		}

		return $replacement;
	}

	/**
	 * @param $segment
	 * @return string
	 */
	public static function do_if($segment) {

		$parts = preg_split('/\{\?\s*(?P<condition>.+?)\s*\}/si', $segment['inside_if'], -1,  PREG_SPLIT_DELIM_CAPTURE);
		array_unshift($parts, $segment['condition']);

		$expressions = array();

		for($z = 0; $z < count($parts); $z += 2) {
			$expression = $parts[$z];
			$output = $parts[$z+1];

			if($expression == ':') {
				$expressions[':'] = $output;
			}
			else {
				if($expression[0] == ':') {
					$expression = substr($expression, 1);
				}
				$expression = preg_replace_callback('/\$([\w\.]+)/si', function($match) {
					return 'T::sub($_context, "' . $match[1] . '")';
				}, $expression);

				$expressions[$expression] = $output;
			}
		}

		uksort($expressions, function($a, $b) {
			if($a == ':') return 1;
			if($b == ':') return -1;
			return 0;
		});

		$condition = 'if';
		$replacement = '';
		foreach($expressions as $expression => $output) {
			if($expression == ':') {
				$replacement .= '<?php else: ?>';
			}
			else {
				$replacement .= '<?php ';
				$replacement .= $condition . '(' . $expression . '): ?>';
			}
			$condition = 'elseif';
			$replacement .= trim($output);
		}
		$replacement .= '<?php endif; ?>';

		return $replacement;
	}

	/**
	 * Find a substitution value based on the context and name provided
	 * @param array $contexts An array of objects representing, in reverse order, the current context
	 * @param string $varname The variable name to find the value of
	 * @return mixed The value of the requested variable name
	 * @throws \Exception
	 */
	public static function sub($contexts, $varname) {
		// Parse up the variable name
		$segments = explode('.', $varname);  // Simple for now

		$segment = array_shift($segments);
		if($segment == '_') {
			$objary = end($contexts);
		}
		// See if the named thing is in the global namespace
		elseif(isset(self::$vars[$segment])) {
			$objary = self::$vars[$segment];
		}
		else {
			// Look for the named thing in the context
			foreach(array_reverse($contexts) as $context) {
				if(is_array($context)) {
					if(isset($context[$segment])) {
						$objary = $context[$segment];
						break;
					}
				}
				elseif(is_object($context)) {
					if(isset($context->$segment)) {
						$objary = $context->$segment;
							break;
					}
				}
			}
			if(!isset($objary)) {
				throw new \Exception("Error Processing Request a", 1);
			}
		}

		while($segments) {
			$segment = array_shift($segments);
			if(is_array($objary)) {
				if(!isset($objary[$segment])) {
					throw new \Exception("Error Processing Request b", 1);
				}
				$objary = $objary[$segment];
			}
			elseif(is_object($objary)) {
				if(!isset($objary->$segment)) {
					throw new \Exception("Error Processing Request c", 1);
				}
				$objary = $objary->$segment;
			}
			else {
				throw new \Exception("Error Processing Request d", 1);
			}
		}
		return $objary;
	}

	/**
	 * Compile a template to PHP by replacing its tag segments with PHP code
	 * @param string $template The template text
	 * @param array $segments An array of preg_match() data containing the tags to replace with PHP
	 * @param bool $sub_component If true, this is a sub-component template that should not have the setup code prepended
	 * @return string The processed template with injected PHP
	 */
	public static function compile($template, $segments, $sub_component = false) {

		$tags = self::get_tags();

		foreach($segments as $segment) {
			$replace = $segment[0];
			$replacement = $segment[0];

			foreach($tags as $tag) {
				if(preg_match('#' . $tag['regex'] . '#si', $replace)) {
					$fn = $tag['fn'];
					$replacement = $fn($segment);
					break;
				}
			}

			$template = str_replace($replace, $replacement, $template);
		}

		$prepend = <<<'PREPEND'
<?php

use \Microsite\Template as T;

T::vars(get_defined_vars());
$_context = array();

?>
PREPEND;

		$output = $template;

		if(!$sub_component) {
			$output = $prepend . $template;
			//echo $output; die();
		}

		return $output;
	}

}

?>