<?php

namespace Microsite;

/**
 * HTTP Class
 *
 * This is a wrapper HTTP class that uses either cURL or fsockopen to
 * harvest resources from web. This can be used with scripts that need
 * a way to communicate with public APIs that support REST.
 *
 * @author      Md Emran Hasan <phpfour@gmail.com>
 * @package     Library
 * @copyright   2007-2008 Md Emran Hasan
 * @link        http://www.phpfour.com/lib/http
 */

class HTTP
{
	/**
	 * Contains the target URL
	 * @public string
	 */
	public $target;

	/**
	 * Contains the target host
	 * @public string
	 */
	public $host;

	/**
	 * Contains the target port
	 * @public integer
	 */
	public $port;

	/**
	 * Contains the target path
	 * @public string
	 */
	public $path;

	/**
	 * Contains the target schema
	 * @public string
	 */
	public $schema;

	/**
	 * Contains the http method (GET or POST)
	 * @public string
	 */
	public $method;

	/**
	 * Contains the parameters for request
	 * @public array
	 */
	public $params;

	/**
	 * Contains the cookies for request
	 * @public array
	 */
	public $cookies;

	/**
	 * Contains the cookies retrieved from response
	 * @public array
	 */
	public $_cookies;

	/**
	 * Number of seconds to timeout
	 * @public integer
	 */
	public $timeout;

	/**
	 * Whether to use cURL or not
	 * @public boolean
	 */
	public $useCurl;

	/**
	 * Contains the referrer URL
	 * @public string
	 */
	public $referrer;

	/**
	 * Contains the User agent string
	 * @public string
	 */
	public $userAgent;

	/**
	 * Contains the cookie path (to be used with cURL)
	 * @public string
	 */
	public $cookiePath;

	/**
	 * Whether to use cookie at all
	 * @public boolean
	 */
	public $useCookie;

	/**
	 * Whether to store cookie for subsequent requests
	 * @public boolean
	 */
	public $saveCookie;

	/**
	 * Contains the Username (for authentication)
	 * @public string
	 */
	public $username;

	/**
	 * Contains the Password (for authentication)
	 * @public string
	 */
	public $password;

	/**
	 * Contains the fetched web source
	 * @public string
	 */
	public $result;

	/**
	 * Contains the last headers
	 * @public string
	 */
	public $headers;

	/**
	 * Contains the last call's http status code
	 * @public string
	 */
	public $status;

	/**
	 * Whether to follow http redirect or not
	 * @public boolean
	 */
	public $redirect;

	/**
	 * The maximum number of redirect to follow
	 * @public integer
	 */
	public $maxRedirect;

	/**
	 * The current number of redirects
	 * @public integer
	 */
	public $curRedirect;

	/**
	 * Contains any error occurred
	 * @public string
	 */
	public $error;

	/**
	 * Store the next token
	 * @public string
	 */
	public $nextToken;

	/**
	 * Whether to keep debug messages
	 * @public boolean
	 */
	public $debug;

	/**
	 * Stores the debug messages
	 * @public array
	 * @todo will keep debug messages
	 */
	public $debugMsg;

	/**
	 * Constructor for initializing the class with default values.
	 * @return \Microsite\HTTP
	 */
	function __construct()
	{
		$this->clear();
	}

	/**
	 * Initialize preferences
	 *
	 * This function accepts an associative array of config values and
	 * will initialize the class using them.
	 *
	 * Example use:
	 *
	 * <pre>
	 * $httpConfig['method']     = 'GET';
	 * $httpConfig['target']     = 'http://www.somedomain.com/index.html';
	 * $httpConfig['referrer']   = 'http://www.somedomain.com';
	 * $httpConfig['user_agent'] = 'My Crawler';
	 * $httpConfig['timeout']    = '30';
	 * $httpConfig['params']     = array('public1' => 'testvalue', 'public2' => 'somevalue');
	 *
	 * $http = new Http();
	 * $http->initialize($httpConfig);
	 * </pre>
	 *
	 * @param array $config Config values as associative array
	 * @return void
	 */
	function initialize($config = array())
	{
		$this->clear();
		foreach ($config as $key => $val) {
			if( isset($this->$key) ) {
				$method = 'set' . ucfirst(str_replace('_', '', $key));

				if( method_exists($this, $method) ) {
					$this->$method($val);
				} else {
					$this->$key = $val;
				}
			}
		}
	}

	/**
	 * Clear Everything
	 *
	 * Clears all the properties of the class and sets the object to
	 * the beginning state. Very handy if you are doing subsequent calls
	 * with different data.
	 *
	 * @return void
	 */
	function clear()
	{
		// Set the request defaults
		$this->host = '';
		$this->port = 0;
		$this->path = '';
		$this->target = '';
		$this->method = 'GET';
		$this->schema = 'http';
		$this->params = array();
		$this->headers = array();
		$this->cookies = array();
		$this->_cookies = array();

		// Set the config details
		$this->debug = FALSE;
		$this->error = '';
		$this->status = 0;
		$this->timeout = '25';
		$this->useCurl = TRUE;
		$this->referrer = '';
		$this->username = '';
		$this->password = '';
		$this->redirect = TRUE;

		// Set the cookie and agent defaults
		$this->nextToken = '';
		$this->useCookie = TRUE;
		$this->saveCookie = TRUE;
		$this->maxRedirect = 3;
		$this->cookiePath = null;
		$this->userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.9';
	}


	/**
	 * Set request parameters
	 *
	 * @param array $dataArray All the parameters for GET or POST
	 * @return void
	 */
	function setParams($dataArray)
	{
		if( is_array($dataArray) ) {
			$this->params = $dataArray;
		}
	}

	/**
	 * Set basic http authentication realm
	 *
	 * @param string $username Username for authentication
	 * @param string $password Password for authentication
	 * @return void
	 */
	function setAuth($username, $password)
	{
		if( !empty($username) && !empty($password) ) {
			$this->username = $username;
			$this->password = $password;
		}
	}


	/**
	 * Add request parameters
	 *
	 * @param string $name Name of the parameter
	 * @param string $value Value of the parameter
	 * @return void
	 */
	function addParam($name, $value)
	{
		if( !empty($name) && !empty($value) ) {
			$this->params[$name] = $value;
		}
	}

	/**
	 * Add a cookie to the request
	 *
	 * @param string $name Name of cookie
	 * @param string $value Value of cookie
	 * @return void
	 */
	function addCookie($name, $value)
	{
		if( !empty($name) && !empty($value) ) {
			$this->cookies[$name] = $value;
		}
	}


	/**
	 * Whether to follow HTTP redirects
	 *
	 * @param boolean $value Whether to follow HTTP redirects or not
	 * @return void
	 */
	function followRedirects($value = TRUE)
	{
		if( is_bool($value) ) {
			$this->redirect = $value;
		}
	}

	/**
	 * Get execution result body
	 *
	 * @return string output of execution
	 */
	function getResult()
	{
		return $this->result;
	}

	/**
	 * Get execution result headers
	 *
	 * @return array last headers of execution
	 */
	function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Get execution status code
	 *
	 * @return integer last http status code
	 */
	function getStatus()
	{
		return $this->status;
	}

	/**
	 * Get last execution error
	 *
	 * @return string last error message (if any)
	 */
	function getError()
	{
		return $this->error;
	}

	/**
	 * Execute a HTTP request
	 *
	 * Executes the http fetch using all the set properties. Intelligently
	 * switch to fsockopen if cURL is not present. And be smart to follow
	 * redirects (if asked so).
	 *
	 * @param string $method The http method (GET or POST) (optional)
	 * @param string $target URL of the target page (optional)
	 * @param array $data Parameter array for GET or POST (optional)
	 * @param string $referrer URL of the referrer page (optional)
	 * @return string Response body of the target page
	 */
	function execute($method = '', $target = '', $data = array(), $referrer = '')
	{
		// Populate the properties
		$this->target = ($target) ? $target : $this->target;
		$this->method = ($method) ? $method : $this->method;

		$this->referrer = ($referrer) ? $referrer : $this->referrer;

		// Add the new params
		if( is_array($data) && count($data) > 0 ) {
			$this->params = array_merge($this->params, $data);
		}

		// Process data, if presented
		if( is_array($this->params) && count($this->params) > 0 ) {
			// Get a blank slate
			$tempString = array();

			// Convert data array into a query string (ie animal=dog&sport=baseball)
			foreach ($this->params as $key => $value) {
				if( strlen(trim($value)) > 0 ) {
					$tempString[] = $key . "=" . urlencode($value);
				}
			}

			$queryString = join('&', $tempString);
		}

		// If cURL is not installed, we'll force fscokopen
		$this->useCurl = $this->useCurl && in_array('curl', get_loaded_extensions());

		// GET method configuration
		if( $this->method == 'GET' ) {
			if( isset($queryString) ) {
				$this->target = $this->target . "?" . $queryString;
			}
		}

		// Parse target URL
		$urlParsed = parse_url($this->target);

		// Handle SSL connection request
		if( $urlParsed['scheme'] == 'https' ) {
			$this->host = 'ssl://' . $urlParsed['host'];
			$this->port = ($this->port != 0) ? $this->port : 443;
		} else {
			$this->host = $urlParsed['host'];
			$this->port = ($this->port != 0) ? $this->port : 80;
		}

		// Finalize the target path
		$this->path = (isset($urlParsed['path']) ? $urlParsed['path'] : '/') . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
		$this->schema = $urlParsed['scheme'];

		// Pass the required cookies
		$this->_passCookies();

		// Process cookies, if requested
		if( is_array($this->cookies) && count($this->cookies) > 0 ) {
			// Get a blank slate
			$tempString = array();

			// Convert cookies array into a query string (ie animal=dog&sport=baseball)
			foreach ($this->cookies as $key => $value) {
				if( strlen(trim($value)) > 0 ) {
					$tempString[] = $key . "=" . urlencode($value);
				}
			}

			$cookieString = join('&', $tempString);
		}

		// Do we need to use cURL
		if( $this->useCurl ) {
			// Initialize PHP cURL handle
			$ch = curl_init();

			// GET method configuration
			if( $this->method == 'GET' ) {
				curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
				curl_setopt($ch, CURLOPT_POST, FALSE);
			} // POST method configuration
			else {
				if( isset($queryString) ) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
				}

				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_HTTPGET, FALSE);
			}

			// Basic Authentication configuration
			if( $this->username && $this->password ) {
				curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
			}

			// Custom cookie configuration
			if( $this->useCookie && isset($cookieString) ) {
				curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
			}

			curl_setopt($ch, CURLOPT_HEADER, TRUE); // No need of headers
			curl_setopt($ch, CURLOPT_NOBODY, FALSE); // Return body

			if(!empty($this->cookiePath)) {
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath); // Cookie management.
			}
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); // Timeout
			curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent); // User Agent name
			curl_setopt($ch, CURLOPT_URL, $this->target); // Target site
			curl_setopt($ch, CURLOPT_REFERER, $this->referrer); // Referrer value

			curl_setopt($ch, CURLOPT_VERBOSE, FALSE); // Minimize logs
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // No certificate
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect); // Follow redirects
			curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirect); // Limit redirections
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); // Return in string

			// Get the target contents
			$content = curl_exec($ch);
			$contentArray = explode("\r\n\r\n", $content);

			// Get the request info
			$status = curl_getinfo($ch);

			// Store the contents
			$this->result = $contentArray[count($contentArray) - 1];

			// Parse the headers
			$this->_parseHeaders($contentArray[count($contentArray) - 2]);

			// Store the error (is any)
			$this->_setError(curl_error($ch));

			// Close PHP cURL handle
			curl_close($ch);
		} else {
			// Get a file pointer
			$filePointer = fsockopen($this->host, $this->port, $errorNumber, $errorString, $this->timeout);

			// We have an error if pointer is not there
			if( !$filePointer ) {
				$this->_setError('Failed opening http socket connection: ' . $errorString . ' (' . $errorNumber . ')');
				return FALSE;
			}

			// Set http headers with host, user-agent and content type
			$requestHeader = [$this->method . " " . $this->path . "  HTTP/1.1"];
			$requestHeader[]= "Host: " . $urlParsed['host'];
			$requestHeader[]= "User-Agent: " . $this->userAgent;
			$requestHeader[]= "Content-Type: application/x-www-form-urlencoded";

			// Specify the custom cookies
			if( $this->useCookie && isset($cookieString) && $cookieString != '' ) {
				$requestHeader[]= "Cookie: " . $cookieString;
			}

			// POST method configuration
			if( $this->method == "POST" ) {
				if(!isset($queryString)) {
					$queryString = '';
				}
				$requestHeader[]= "Content-Length: " . strlen($queryString);
			}

			// Specify the referrer
			if( $this->referrer != '' ) {
				$requestHeader[]= "Referer: " . $this->referrer;
			}

			// Specify http authentication (basic)
			if( $this->username && $this->password ) {
				$requestHeader[]= "Authorization: Basic " . base64_encode($this->username . ':' . $this->password);
			}

			$requestHeader[]= "Connection: close\r\n";

			// POST method configuration
			if( $this->method == "POST" ) {
				if(!isset($queryString)) {
					$queryString = '';
				}
				$requestHeader[]= $queryString;
			}

			$requestHeader = implode("\r\n", $requestHeader);

			// We're ready to launch
			fwrite($filePointer, $requestHeader);

			// Clean the slate
			$responseHeader = '';
			$responseContent = '';

			// 3...2...1...Launch !
			do {
				$responseHeader .= fread($filePointer, 1);
			} while (!preg_match('/\\r\\n\\r\\n$/', $responseHeader));

			// Parse the headers
			$this->_parseHeaders($responseHeader);

			// Do we have a 302 redirect ?
			if( $this->status == '302' && $this->redirect == TRUE ) {
				if( $this->curRedirect < $this->maxRedirect ) {
					// Let's find out the new redirect URL
					$newUrlParsed = parse_url($this->headers['location']);

					if( $newUrlParsed['host'] ) {
						$newTarget = $this->headers['location'];
					} else {
						$newTarget = $this->schema . '://' . $this->host . '/' . $this->headers['location'];
					}

					// Reset some of the properties
					$this->port = 0;
					$this->status = 0;
					$this->params = array();
					$this->method = 'GET';
					$this->referrer = $this->target;

					// Increase the redirect counter
					$this->curRedirect++;

					// Let's go, go, go !
					$this->result = $this->execute($newTarget);
				} else {
					$this->_setError('Too many redirects.');
					return FALSE;
				}
			} else {
				// Nope...so lets get the rest of the contents (non-chunked)
				if( $this->headers['transfer-encoding'] != 'chunked' ) {
					while (!feof($filePointer)) {
						$responseContent .= fgets($filePointer, 128);
					}
				} else {
					// Get the contents (chunked)
					while ($chunkLength = hexdec(fgets($filePointer))) {
						$responseContentChunk = '';
						$readLength = 0;

						while ($readLength < $chunkLength) {
							$responseContentChunk .= fread($filePointer, $chunkLength - $readLength);
							$readLength = strlen($responseContentChunk);
						}

						$responseContent .= $responseContentChunk;
						fgets($filePointer);
					}
				}

				// Store the target contents
				$this->result = chop($responseContent);
			}
		}

		// There it is! We have it!! Return to base !!!
		return $this->result;
	}

	/**
	 * Shortcut method for executing GET requests
	 * @param string $url The URL of the request
	 * @param array $data Data for the querystring
	 * @param string $referer A referring URL
	 * @return string The response body
	 */
	public function get($url, $data = array(), $referer = '') {
		return $this->execute('GET', $url, $data, $referer);
	}

	/**
	 * Shortcut method for executing POST requests
	 * @param string $url The URL of the request
	 * @param array $data Data for the POST
	 * @param string $referer A referring URL
	 * @return string The response body
	 */
	public function post($url, $data = array(), $referer = '') {
		return $this->execute('POST', $url, $data, $referer);
	}

	/**
	 * Parse Headers (internal)
	 *
	 * Parse the response headers and store them for finding the response
	 * status, redirection location, cookies, etc.
	 *
	 * @param string $responseHeader Raw header response
	 * @return void
	 * @access private
	 */
	function _parseHeaders($responseHeader)
	{
		// Break up the headers
		$headers = explode("\r\n", $responseHeader);

		// Clear the header array
		$this->_clearHeaders();

		// Get resposne status
		if( $this->status == 0 ) {
			// Oooops !
			if(!preg_match('#^http/[0-9]+\\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)\$#i', $headers[0], $matches)) {
				$this->_setError('Unexpected HTTP response status');
				return;
			}

			// Gotcha!
			$this->status = $matches[1];
			array_shift($headers);
		}

		// Prepare all the other headers
		foreach ($headers as $header) {
			// Get name and value
			$headerName = strtolower($this->_tokenize($header, ':'));
			$headerValue = trim(chop($this->_tokenize("\r\n")));

			// If its already there, then add as an array. Otherwise, just keep there
			if( isset($this->headers[$headerName]) ) {
				if( gettype($this->headers[$headerName]) == "string" ) {
					$this->headers[$headerName] = array($this->headers[$headerName]);
				}

				$this->headers[$headerName][] = $headerValue;
			} else {
				$this->headers[$headerName] = $headerValue;
			}
		}

		// Save cookies if asked
		if( $this->saveCookie && isset($this->headers['set-cookie']) ) {
			$this->_parseCookie();
		}
	}

	/**
	 * Clear the headers array (internal)
	 *
	 * @return void
	 * @access private
	 */
	function _clearHeaders()
	{
		$this->headers = array();
	}

	/**
	 * Parse Cookies (internal)
	 *
	 * Parse the set-cookie headers from response and add them for inclusion.
	 *
	 * @return void
	 * @access private
	 */
	function _parseCookie()
	{
		// Get the cookie header as array
		if( gettype($this->headers['set-cookie']) == "array" ) {
			$cookieHeaders = $this->headers['set-cookie'];
		} else {
			$cookieHeaders = array($this->headers['set-cookie']);
		}

		// Loop through the cookies
		for ($cookie = 0; $cookie < count($cookieHeaders); $cookie++) {
			$cookieName = trim($this->_tokenize($cookieHeaders[$cookie], "="));
			$cookieValue = $this->_tokenize(";");

			$urlParsed = parse_url($this->target);

			$domain = $urlParsed['host'];
			$secure = '0';

			$path = "/";
			$expires = "";

			while (($name = trim(urldecode($this->_tokenize("=")))) != "") {
				$value = urldecode($this->_tokenize(";"));

				switch ($name) {
					case "path"     :
						$path = $value;
						break;
					case "domain"   :
						$domain = $value;
						break;
					case "secure"   :
						$secure = ($value != '') ? '1' : '0';
						break;
				}
			}

			$this->_setCookie($cookieName, $cookieValue, $expires, $path, $domain, $secure);
		}
	}

	/**
	 * Set cookie (internal)
	 *
	 * Populate the internal _cookies array for future inclusion in
	 * subsequent requests. This actually validates and then populates
	 * the object properties with a dimensional entry for cookie.
	 *
	 * @param string $name Cookie name
	 * @param string $value Cookie value
	 * @param string $expires Cookie expire date
	 * @param string $path Cookie path
	 * @param string $domain Cookie domain
	 * @param int|string $secure Cookie security (0 = non-secure, 1 = secure)
	 * @access private
	 */
	function _setCookie($name, $value, $expires = "", $path = "/", $domain = "", $secure = 0)
	{
		if( strlen($name) == 0 ) {
			$this->_setError("No valid cookie name was specified.");
			return;
		}

		if( strlen($path) == 0 || strcmp($path[0], "/") ) {
			$this->_setError("$path is not a valid path for setting cookie $name.");
			return;
		}

		if( $domain == "" || !strpos($domain, ".", $domain[0] == "." ? 1 : 0) ) {
			$this->_setError("$domain is not a valid domain for setting cookie $name.");
			return;
		}

		$domain = strtolower($domain);

		if( !strcmp($domain[0], ".") ) {
			$domain = substr($domain, 1);
		}

		$name = $this->_encodeCookie($name, true);
		$value = $this->_encodeCookie($value, false);

		$secure = intval($secure);

		$this->_cookies[] = array("name" => $name,
			"value" => $value,
			"domain" => $domain,
			"path" => $path,
			"expires" => $expires,
			"secure" => $secure
		);
	}

	/**
	 * Encode cookie name/value (internal)
	 *
	 * @param string $value Value of cookie to encode
	 * @param string $name Name of cookie to encode
	 * @return string encoded string
	 * @access private
	 */
	function _encodeCookie($value, $name)
	{
		return ($name ? str_replace("=", "%25", $value) : str_replace(";", "%3B", $value));
	}

	/**
	 * Pass Cookies (internal)
	 *
	 * Get the cookies which are valid for the current request. Checks
	 * domain and path to decide the return.
	 *
	 * @return void
	 * @access private
	 */
	function _passCookies()
	{
		if( is_array($this->_cookies) && count($this->_cookies) > 0 ) {
			$urlParsed = parse_url($this->target);
			$tempCookies = array();

			foreach ($this->_cookies as $cookie) {
				if( $this->_domainMatch($urlParsed['host'], $cookie['domain']) && (0 === strpos($urlParsed['path'], $cookie['path']))
					&& (empty($cookie['secure']) || $urlParsed['protocol'] == 'https')
				) {
					$tempCookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
				}
			}

			// cookies with longer paths go first
			foreach ($tempCookies as $name => $values) {
				krsort($values);
				foreach ($values as $value) {
					$this->addCookie($name, $value);
				}
			}
		}
	}

	/**
	 * Checks if cookie domain matches a request host (internal)
	 *
	 * Cookie domain can begin with a dot, it also must contain at least
	 * two dots.
	 *
	 * @param string $requestHost Request host
	 * @param string $cookieDomain Cookie domain
	 * @return bool Match success
	 * @access private
	 */
	function _domainMatch($requestHost, $cookieDomain)
	{
		if( '.' != $cookieDomain{0} ) {
			return $requestHost == $cookieDomain;
		} elseif( substr_count($cookieDomain, '.') < 2 ) {
			return false;
		} else {
			return substr('.' . $requestHost, -strlen($cookieDomain)) == $cookieDomain;
		}
	}

	/**
	 * Tokenize String (internal)
	 *
	 * Tokenize string for internal usage. Omit the second parameter
	 * to tokenize the previous string that was provided in the prior call to
	 * the function.
	 *
	 * @param string $string The string to tokenize
	 * @param string $separator The separator to use
	 * @return string Tokenized string
	 * @access private
	 */
	function _tokenize($string, $separator = '')
	{
		if( !strcmp($separator, '') ) {
			$separator = $string;
			$string = $this->nextToken;
		}

		for ($character = 0; $character < strlen($separator); $character++) {
			if( gettype($position = strpos($string, $separator[$character])) == "integer" ) {
				$found = (isset($found) ? min($found, $position) : $position);
			}
		}

		if( isset($found) ) {
			$this->nextToken = substr($string, $found + 1);
			return (substr($string, 0, $found));
		} else {
			$this->nextToken = '';
			return ($string);
		}
	}

	/**
	 * Set error message (internal)
	 *
	 * @param string $error Error message
	 * @access private
	 */
	function _setError($error)
	{
		if( $error != '' ) {
			$this->error = $error;
		}
	}
}

?>