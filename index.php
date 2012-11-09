<?php

use \Microsite\App;
use \Microsite\Regex;
use \Microsite\Response;
use \Microsite\Request;
use \Microsite\DB;
use \Microsite\Handler;

include 'src/microsite.phar';
//include 'src/stub2.php';

$app = new App();

// Assign a directory in which templates can be found for rendering
$app->template_dirs = [
	__DIR__ . '/views',
];

/**
 * Basic home page.
 * Set the view to a home.php view provided in the view directory
 */
$app->route('home', '/', function(Response $response) {
	return $response->render('home.php');
});

/**
 * Simple string output.
 */
$app->route('string', '/string', function() {
	return "A response can be a simple string from inside a function";
});

/**
 * Simple echo output.
 */
$app->route('echo', '/echo', function() {
	echo "A response can even be echoed from inside a function";
});


/**
 * Route with a parameter
 */
$app->route('hello', '/hello/:name', function(Response $response, Request $request) {
	echo "Hello {$request['name']}!";
});

/**
 * Route with a validated parameter
 */
$app->route('count', '/count/:number', function(Response $response, Request $request) {
	echo "This is a number: {$request['number']}";
})->validate_fields([':number' => '[0-9]+']);

/**
 * Route with a validated parameter function, only /valid/ok correctly routes here
 */
$app->route('valid', '/valid/:valid', function(Response $response, Request $request) {
	echo "This is a valid route: {$request['valid']}";
})->validate_fields([':valid' => function($matches, $value) {if($value == 'ok') return $matches; else return false;}]);

/**
 * Convert an incoming url parameter into a useful value
 */
$app
	->route(
		'user',
		'/user/:user',
		function(Response $response, Request $request) {
			echo "The requested user's name is: {$request['user']->name}";
		}
	)
	->validate_fields([':user' => '\d+'])
	->convert('user', function($user_id){
		$user = new stdClass(); // Simulate getting a database record.
		$user->id = $user_id;
		$user->name = 'Test User';
		return $user;
	});

/**
 * Two handlers
 */
$app->route(
	'evenodd',
	'/evenodd/:number',
	function(Response $response, Request $request) {
		if($request['number'] % 2 == 0) {
			echo "This is an even number";
		}
	},
	function(Response $response, Request $request) {
		if($request['number'] % 2 == 1) {
			echo "This is an odd number";
		}
	}
)->validate_fields([':number' => '[0-9]+']);


/**
 * Use the route system to produce the url to the named route "hello"
 */
$app->route('interior', '/interior', function(Response $response, Request $request, App $app) {
	$response['output'] = $app->get_route('hello')->build(['name' => 'User']);
	return $response->render('debug.php');
});


/**
 * A simple custom Handler class
 */
class MyHandler extends \Microsite\Handler {
	public $prerequisite;

	public function handler_one() {
		echo 'This is a custom handler function.';
	}
	public function prerequisite() {
		$this->prerequisite = 'set';
	}
	public function handler_two() {
		echo 'The prerequisite value is: ' . $this->prerequisite;
	}
}

/**
 * Have this route respond with a method from the custom handler
 */
$app->route('dohandler', '/dohandler1', Handler::handle('MyHandler', 'handler_one'));

/**
 * Demonstrate the shared handler instance between handler methods
 * This allows a handler class object to maintain a state between handler method execution
 */
$app->route(
	'prerequisite',
	'/prerequisite',
	Handler::handle('MyHandler', 'prerequisite'),
	Handler::handle('MyHandler', 'handler_two')
);

/**
 * GET method only
 * Demonstrates two routes with the same URL, on different HTTP methods
 */
$app->route('form', '/form', function() {
	echo <<< FORM_HTML
<form action="" method="POST">
<label>Name: <input type="text" name="name" /></label>
<input type="submit" value="Submit">
</form>
FORM_HTML;

})->get();

/**
 * POST method only
 * Demonstrates two routes with the same URL, on different HTTP methods
 */
$app->route(
	'form_post',
	'/form',
	function($response) {
		if(trim($_POST['name']) == '') {
			$response->redirect('/form');
		}
	},
	function() {
		echo 'The entered name is: ' . $_POST['name'];
	}
)->post();

/**
 * Response for either the GET or POST method
 */
$app->route('getorpost', '/method', function() {echo 'Worked.';})->via('GET,POST');

/**
 * Use a regular expression to test the route URL
 * Pass the name back into the response for output.
 * Use the internal debug.php view again for output.
 */
$app->route('hiya', new Regex('#/hiya/(?P<name>.+)/?$#'), function(Response $response, Request $request) {
	$response['output'] = "Hiya {$request['name']}";
	return $response->render('debug.php');
});


/**
 * Only accept even arguments in the URL
 */
$app->route('even', new Regex('#^/number/(?P<number>[0-9]+)/?$#'), function() {
	echo "The number was even.";
})->validate(function($request) { return $request['number'] % 2 == 0;});

/**
 * Only accept odd arguments in the URL
 */
$app->route('odd', new Regex('#^/number/(?P<number>[0-9]+)/?$#'), function() {
	echo "The number was odd.";
})->validate(function($request) { return $request['number'] % 2 == 1;});


/**
 * Create a new app to layer into the /admin URL space.
 */
$admin = new App();

/**
 * Within the admin app, create a /plugins URL
 * Output a message using the internal debug.php template
 */
$admin->route('plugins', '/plugins', function(Response $response) {
	echo "This is the Plugins page";
});

/**
 * Add the admin app as a handler within the /admin route on the main app
 */
$app->route('admin', '/admin', $admin);

/**
 * Register an on-demand object with the app
 */
$app->register('mockdb', function($param) {
	$obj = new stdClass();
	$obj->foo = 'bar';
	$obj->baz = [1,2,3];
	$obj->microtime = microtime(true);
	$obj->param = $param;
	return $obj;
});

/**
 * Return the view data as a json object
 * Fetch the registsered on-demand "mockdb" object from the app
 * Note that both mockdb objects are the same - it is created only once
 */
$app->route('json', '/json', function(Response $response, Request $request, App $app) {
	$response['user'] = 'Owen';
	$response['user_id'] = 1;
	$response['registered'] = true;
	$response['mockdb_obj'] = $app->mockdb('a');
	$response['mockdb_obj2'] = $app->mockdb('b');
	$response->set_renderer(\Microsite\Renderers\JSONRenderer::create(''));
	return $response->render();
});


/**
 * Register an on-demand object with the app for a real database
 */
$app->register('db', function() {
	$db = new DB('sqlite:' . __DIR__ . '/db.db');
	return $db;
});

$app->route('database', '/database', function(Response $response, $request, $app) {
	$samples = $app->db()->results('SELECT * FROM sample ORDER BY age ASC;');
	$response['output'] = $response->partial('table.php', array('results' => $samples));
	return $response->render('debug.php');
});

/**
 * Run the app to match and dispatch routes
 */
$app();

?>
