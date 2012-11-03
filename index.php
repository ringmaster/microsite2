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

$app->template_dirs = [
	__DIR__ . '/views',
	'phar://microsite.phar/lib/Microsite/Views'
];

$app->register('renderer', function() use($app) {
	return \Microsite\Renderers\PHPRenderer::create(
		$app->template_dirs
	);
});

/**
 * Basic home page.
 * Set the view to a home.php view provided in the view directory
 */
$app->route('/', function(Response $response){
	return $response->render('home.php');
});

/**
 * Simple string output.
 */
$app->route('/string', function(){
	return "A response can be a simple string from inside a function";
});

/**
 * Simple echo output.
 */
$app->route('/echo', function(){
	echo "A response can even be echoed from inside a function";
});

/**
 * A simple custom Handler class
 */
class MyHandler extends \Microsite\Handler {
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
$app->route('/dohandler1', Handler::handle('MyHandler', 'handler_one'));

/**
 * Demonstrate the shared handler instance between handler methods
 * This allows a handler class object to maintain a state between handler method execution
 */
$app->route(
	'/prerequisite',
	Handler::handle('MyHandler', 'prerequisite'),
	Handler::handle('MyHandler', 'handler_two')
);

/**
 * GET method only
 * Demonstrates two routes with the same URL, on different HTTP methods
 */
$app->route('/form', function(){
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
	'/form',
	function($response) {
		if(trim($_POST['name']) == '') {
			$response->redirect('/form');
		}
	},
	function(){
		echo 'The entered name is: ' . $_POST['name'];
	}
)->post();


/**
 * Use a regular expression to test the route URL
 * Pass the name back into the response for output.
 * Use the internal debug.php view again for output.
 */
$app->route(new Regex('#/hello/(?P<name>.+)/?$#'), function(Response $response, Request $request){
	$response['output'] = "Hello {$request['name']}";
	return $response->render('debug.php');
});


/**
 * Only accept even arguments in the URL
 */
$app->route(new Regex('#^/number/(?P<number>[0-9]+)/?$#'), function(){
	echo "The number was even.";
})->validate(function($request) { return $request['number'] % 2 == 0;});

/**
 * Only accept odd arguments in the URL
 */
$app->route(new Regex('#^/number/(?P<number>[0-9]+)/?$#'), function(){
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
$admin->route('/plugins', function(Response $response){
	$response['output'] = "This is the Plugins page";
	return $response->render('debug.php');
});

/**
 * Add the admin app as a handler within the /admin route on the main app
 */
$app->route('/admin', $admin);

/**
 * Register an on-demand object with the app
 */
$app->register('mockdb', function(){
	$obj = new stdClass();
	$obj->foo = 'bar';
	$obj->baz = [1,2,3];
	$obj->microtime = microtime(true);
	return $obj;
});

/**
 * Return the view data as a json object
 * Fetch the registsered on-demand "mockdb" object from the app
 * Note that both mockdb objects are the same - it is created only once
 */
$app->route('/json', function(Response $response, Request $request, App $app) {
	$response['user'] = 'Owen';
	$response['user_id'] = 1;
	$response['registered'] = true;
	$response['mockdb_obj'] = $app->mockdb();
	$response['mockdb_obj2'] = $app->mockdb();
	$response->set_renderer(\Microsite\Renderers\JSONRenderer::create(''));
	return $response->render();
});


/**
 * Register an on-demand object with the app for a real database
 */
$app->register('db', function(){
	$db = new DB('sqlite:' . __DIR__ . '/db.db');
	return $db;
});

$app->route('/database', function(Response $response, $request, $app) {
	$samples = $app->db()->results('SELECT * FROM sample ORDER BY age ASC;');
	$response['output'] = $response->partial('table.php', array('results' => $samples));
	return $response->render('debug.php');
});

/**
 * Run the app to match and dispatch routes
 */
$app->run();

?>
