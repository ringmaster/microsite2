<?php

// Disable the static web routing of this phar from an including file by defining NO_STATIC_WEB, if necessary
if(defined('NO_STATIC_WEB')) {
	Phar::mapPhar('microsite.phar');
}
else {
	/**
	 * Rewrite a direct request to an internal file
	 * @return string The rewritten URL filename
	 */
	function microsite_rewrite() {
		$r = $_SERVER['REQUEST_URI'];
		if(strpos($r, '/microsite.phar') !== false) {
			$r = preg_replace('#^.*/microsite.phar#', '', $r);
		}
		if(file_exists("phar://microsite.phar{$r}")) {
			return $r;
		}
		else {
			return 'index.php';
		}
	}

	$index = str_replace('\\', DIRECTORY_SEPARATOR, 'index.php');
	$fourohfour = str_replace('\\', DIRECTORY_SEPARATOR, '404.php');
	$mimes = array(
		'phps' => Phar::PHPS, // pass to highlight_file()
		'c' => 'text/plain',
		'cc' => 'text/plain',
		'cpp' => 'text/plain',
		'c++' => 'text/plain',
		'dtd' => 'text/plain',
		'h' => 'text/plain',
		'log' => 'text/plain',
		'rng' => 'text/plain',
		'txt' => 'text/plain',
		'xsd' => 'text/plain',
		'php' => Phar::PHP, // parse as PHP
		'inc' => Phar::PHP, // parse as PHP
		'avi' => 'video/avi',
		'bmp' => 'image/bmp',
		'css' => 'text/css',
		'gif' => 'image/gif',
		'htm' => 'text/html',
		'html' => 'text/html',
		'htmls' => 'text/html',
		'ico' => 'image/x-ico',
		'jpe' => 'image/jpeg',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'js' => 'application/x-javascript',
		'midi' => 'audio/midi',
		'mid' => 'audio/midi',
		'mod' => 'audio/mod',
		'mov' => 'movie/quicktime',
		'mp3' => 'audio/mp3',
		'mpg' => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'pdf' => 'application/pdf',
		'png' => 'image/png',
		'swf' => 'application/shockwave-flash',
		'tif' => 'image/tiff',
		'tiff' => 'image/tiff',
		'wav' => 'audio/wav',
		'xbm' => 'image/xbm',
		'xml' => 'text/xml',
	);
	Phar::webPhar("microsite.phar", 'index.php', '404.php', $mimes, 'microsite_rewrite');
}

// Make sure the autoloader is loaded
include 'phar://microsite.phar/lib/Microsite/Autoloader.php';

// Initialize the autoloader class
\Microsite\Autoloader::init();

// If we're running on the console, do other useful things
switch(php_sapi_name()) {
	case 'cli':
		\Microsite\Console::run();
		break;
	case 'cli-server':
		// This is what executes when the app is run from the PHP-based server.
		break;
}
__HALT_COMPILER();
