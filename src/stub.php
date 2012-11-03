<?php

if(defined('NO_STATIC_WEB')) {
	Phar::mapPhar('microsite.phar');
}
else {
	function microsite_rewrite() {
		$r = $_SERVER['REQUEST_URI'];
		if(strpos($r, '/microsite.phar') !== false) {
			$r = preg_replace('#^.*/microsite.phar#', '', $r);
		}
		if (file_exists("phar://microsite.phar{$r}")) {
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
spl_autoload_register(function ($className) {
	$libPath = 'phar://microsite.phar/lib/';
	$classFile = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
	$classPath = $libPath . $classFile;
	if (file_exists($classPath)) {
		require($classPath);
	}
	else {
		die('Class ' . $className . ' not found at ' . $classPath);
	}
});
__HALT_COMPILER();
