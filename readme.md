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

### Additional Examples

There are many additional examples of potential route functionality in the index.php file at the root of the repo.

## How Does This Differ From...?

There are bound to be comparisons between Microsite and existing frameworks.  The main question being, "Why bother with this when X already exists?"  Sure, this ground is well-tread, but there were a couple of (cheekily-called) design decisions that influenced this project that might make it unique from others:

* **Easy for the builder** - This is a design methodology I like to employ in all of my projects, where the tool does the heavy lifting and makes it as easy as possible for the developer to do his job.  You should only need to look at example code to get a good understanding of what's going on, and it should, in most cases, "Just work".
* **Very little "magic"** - There are tools (even some I've written in the past) that rely extensively on __get()/__set()/__call() that make it difficult to see why certain things work.  I've been trying to build with as little of this magic as possible so that IDEs have a chance to provide useful insight into properties and calls.
* **Doesn't drag a huge library along with it** - The point of Microsite is "micro", to be small.  I didn't want to drag a library like Symfony along when I don't use 90% of its features.  Still, I wanted the system to be extensible enough that I could add it myself later.
* **Minimally functional** - In contrast to the "microness" of Microsite, the library needs to offer a minimum set of functionality to produce a site with a basic set of functionality.  A small, usable database/model class set is included, for example, which accomplishes basic database needs without dragging along a full ORM system.

For me, personally, there were certain other goals:

* Produce a common PSR-0-capable library as a single PHAR archive.
* Build something re-usable for other projects that could remain reasonably stable.
* Enhance my own understanding of current techniques/technologies, like phing, unit/feature testing, CI, etc. by employing them here.
* Satisfy that nagging "I can do this better" need everyone eventually gets when using other people's frameworks for a while.
* Create an alloy of all the good parts I like about other frameworks.

I certainly don't claim Microsite is the best framework out there.  It's probably not even as good as some of the existing microframeworks (yet).  Nevertheless, it is a useful exercise, and can hopefully yield fruitful results for anyone who cares to join the fun.

### Explicit Technical Differences

All that said, there are a few explicit technical differences that you may notice when using Microsite that are probably worth pointing out.

#### Middleware Is Magic

In one fit of defiance to my own rules, middleware functions can be stacked as additional functions upon route registration:

```php
$app->route(
	'routename', 
	'/route/path', 
	function(){/* Do something */},
	function(){/* Do something else */},
	...
);
```

The handler functions are executed in order.  If any of the functions in the chain return anything, then the chain is broken (no subsequent functions execute) and what they return is output.  This can be useful for the following scenario:

```php
$login_redirect = function(){ /* if the user is not logged in, redirect them to the login page */}

$app->route('admin_list', '/admin/list', $login_redirect, function(){ /* show list */ });
$app->route('admin_add', '/admin/add', $login_redirect, function(){ /* add something */ });
```

Both of these routes have an intermediary $login_redirect middleware function that executes first.  If the user is not logged in, then they are redirected to the login page.

### Validation Functions Are Distinct From Middleware

There is a slight difference between middleware and a validation function.  Normally, if a route matches, then its handler functions are executed and progress concludes.  No other route can match after one route matches.

If a validation function fails, then additional subsequent routes matching the same URL can match.  An example of when this is useful is if you want to display different output on a URL based on whether your are logged in:

```php
$app->route('view_item_auth', '/item/:item', function(){ /* show the item with editing fields */})
	->validate(function(){ /* return true if the user is logged in */});
$app->route('view_item_unauth', '/item/:item', function(){ /* show the item WITHOUT editing fields */})
	->validate(function(){ /* return true if the user is NOT logged in */});
```

Since unauthenticated users don't match the first route due to the validation function, the second route is able to test and execute.  

It is possible to acheive this same capability by using two handlers on the same route, and there is no obvious advantage either way other than which way you feel makes your code most readable.
