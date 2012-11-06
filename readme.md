# Microsite 2
*A reincarnation of the Microsite framework.*

## Requirements

* PHP 5.4
* A server that provides a FrontController pattern (Apache with FallbackResource works).


## Setup Instructions

1. Within this repo in the src directory is a file called *microsite.phar*.  Copy this file to your site's directory.
2. Create a file named index.php in the dame directory to be your front controller.
3. Configure your webserver to serve index.php for any request that doesn't point to a file.
4. In index.php: include 'microsite.phar';
5. Use Microsite to create your site.

## Using Microsite 

Microsite provides basic router functions, along with some other basic tools to get your application running quickly.

Use this code to create a simple home page:
```php
<?php

include 'microsite.phar';

$app = new \Microsite\App();

$app->route('home', '/', function() {
	return "Anything returned or echoed here will be displayed at the URL /";
});

// Put any new routes here, before the ->run() method.

$app->run();

?>
```

### Variable URLs

You can detect variables passed in to your URLs easily:

```php
$app->route('hello', '/hello/:name', function($response, $request) {
	return "Hello {$request['name']}!";
});
?>
```

## Additional Examples

There are many additional examples of potential route functionality in the index.php file at the root of the repo.
