<?php

spl_autoload_register(function ($className) {
	$libPath = __DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR;
	$classFile = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
	$classPath = $libPath . $classFile;
	if (file_exists($classPath)) {
		require($classPath);
	}
	else {
		die('Class ' . $className . ' not found at ' . $classPath);
	}
});

?>