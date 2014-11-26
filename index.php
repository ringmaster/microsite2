<?php

use Microsite\App;
use Microsite\Regex;
use Microsite\Response;
use Microsite\Request;
use Microsite\DB\PDO\DB;
use Microsite\Handler;
use Microsite\Template;
use Microsite\Tinycode;
use Microsite\Renderers\JSONRenderer;
use Microsite\Renderers\MarkdownRenderer;
use Microsite\DB\Mongo\DB as MongoDB;

//include 'src/microsite.phar';
include 'src/stub2.php';

$app = new App();

// Assign a directory in which templates can be found for rendering
$app->template_dirs = [
	__DIR__ . '/views',
];

/**
 * Basic home page.
 * Set the view to a home.php view provided in the view directory
 */
$app->route('home', '/', function(App $app) {
	return $app->response()->render('home.php');
});

/**
 * Simple string output.
 */
$app->route('string', '/string', function() {
	return "A response can be a simple string from inside a function";
});

/**
 * Weird routing issue
 */
$app->route('inbound', '/inbound', function() { echo 'ok?'; });

/**
 * Simple echo output.
 */
$app->route('echo', '/echo', function() {
	echo "A response can even be echoed from inside a function";
});


/**
 * Route with a parameter
 */
$app->route('hello', '/hello/:name', function(Request $request) {
	echo "Hello {$request['name']}!";
});

/**
 * Route with a validated parameter
 */
$app->route('count', '/count/:number', function(Request $request) {
	echo "This is a number: {$request['number']}";
})->validate_fields(['number' => '[0-9]+']);

/**
 * Route with a validated parameter function, only /valid/ok correctly routes here
 */
$app->route('valid', '/valid/:valid', function(Request $request) {
	echo "This is a valid route: {$request['valid']}";
})->validate_fields(['valid' => function($value) {return ($value == 'ok');}]);

/**
 * Convert an incoming url parameter into a useful value
 */
$app
	->route(
		'author',
		'/author/:user',
		function(Request $request, Response $response) {
			$response['output'] = '<pre>' . print_r($request['user'], 1) . '</pre>';
			return $response->render('debug.php');
		}
	)
	->validate_fields([':user' => '\d+'])
	->convert('user', function($user_id){
		$user = new stdClass(); // Simulate getting a database record.
		$user->id = $user_id;
		$user->name = 'Test User #' . $user_id;
		return $user;
	});

/**
 * Two handlers
 */
$app->route(
	'evenodd',
	'/evenodd/:number',
	function(Request $request) {
		if($request['number'] % 2 == 0) {
			echo "This is an even number";
		}
	},
	function(Request $request) {
		if($request['number'] % 2 == 1) {
			echo "This is an odd number";
		}
	}
)->validate_fields([':number' => '[0-9]+']);


/**
 * Use the route system to produce the url to the named route "hello"
 */
$app->route('interior', '/interior', function(Response $response, $app) {
	$response['output'] = $app->get_route('hello')->build(['name' => 'User']);
	return $response->render('debug.php');
});


/**
 * A simple custom Handler class
 */
class MyHandler extends Handler {
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
	function(Response $response) {
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
$app->route('even', new Regex('#^/number/(?P<number>[0-9]+)/?$#'), function(Request $request) {
	echo "The number {$request['number']} was even.";
})->validate(function($request) { return $request['number'] % 2 == 0;});

/**
 * Only accept odd arguments in the URL
 */
$app->route('odd', new Regex('#^/number/(?P<number>[0-9]+)/?$#'), function(Request $request) {
	echo "The number {$request['number']} was odd.";
})->validate(function($request) { return $request['number'] % 2 == 1;});

class AdminApp extends App {
	public function add_response(Response $response) {
		$response['added'] = 'true';
	}
}


/**
 * Create a new app to layer into the /admin URL space.
 */
$admin = new AdminApp();

/**
 * Within the admin app, create a /plugins URL
 * Output a message using the internal debug.php template
 */
$admin->route('plugins', '/plugins', function() {
	echo "This is the Plugins page";
});

/**
 * Display the value of "added" as added in the app middleware
 * Note the use of a string as the middleware method name, and that it resolves to the app method
 */
$admin->route('add_response', '/add_response', 'add_response', function(Response $response){
	echo $response['added'];
});

/**
 * Build an index for the admin route
 */
$admin->route('admin_index', '/', function(){
	echo "This is the admin index";
});

/**
 * Add the admin app as a handler within the /admin route on the main app
 */
$app->route('admin', '/admin', $admin);

/**
 * Register an on-demand object with the app
 */
$app->demand('mockdb', function($param) {
	$obj = new stdClass();
	$obj->foo = 'bar';
	$obj->baz = range($param, $param + 15, 3);
	$obj->microtime = microtime(true);
	$obj->param = $param;
	return $obj;
});

/**
 * Return the view data as a json object
 * Fetch the registsered on-demand "mockdb" object from the app
 * Note that the mockdb objects are the different because it was registered with ->demand()
 * If it was registered with ->share() it would be created only once
 */
$app->route('json', '/json', function(App $app) {
	$response = $app->response();
	$response['user'] = 'Owen';
	$response['user_id'] = 1;
	$response['registered'] = true;
	$response['mockdb_obj'] = $app->mockdb(1);
	$response['mockdb_obj2'] = $app->mockdb(16);
	$response->set_renderer(JSONRenderer::create('', $app));
	return $response->render();
});

/**
 * Create a basic test page for different accept header requests
 */
$app->route('accept', '/accept', function(Response $response) {
	$response['message'] = 'The result of the demo appears here.  This is from HTML.';
	return $response->render('accept.php');
});

$app->route('accecpt_json', '/accept', function(Response $response, App $app) {
	$response->set_renderer(JSONRenderer::create('', $app));
	$response['message'] = 'The result of the demo appears here.  This is from JSON.';
	return $response;
})->type('application/json');

/**
 * Render some markdown
 */
$app->route('markdown', '/markdown', function(Response $response, App $app) {
	$response->set_renderer(MarkdownRenderer::create($app->template_dirs(), $app));
	return $response->render('markdown.md');
});

/**
 * Register an on-demand object with the app for a real database
 */
$app->share('db', function() {
	$db = new DB('sqlite:' . __DIR__ . '/db.db');
	return $db;
});

$app->route('database', '/database', function(App $app) {
	$response = $app->response();
	$samples = $app->db()->results('SELECT * FROM sample ORDER BY age ASC;');

	$response['output'] = $response->partial('table.php', array('results' => $samples));
	$response['count'] = $app->db()->val('SELECT count(*) as ct FROM sample');
	$response['sample'] = $samples[0]['name'];
	return $response->render('debug.php');
});

/**
 * On-demand mongo
 */
$app->share('mongo', function() {
	return new MongoDB('samplez');
});

$app->route('mongo', '/mongo', function(App $app) {
	$response = $app->response();
	/** @var \Microsite\DB\Mongo\DB $mongo  */
	$mongo = $app->mongo();

	$samples = iterator_to_array($mongo->find('test'));

	$response['output'] = $response->partial('table.php', array('results' => $samples));
	return $response->render('debug.php');
});

/**
 * Display a phpinfo
 */
$app->route('phpinfo', '/phpinfo', function(){ phpinfo(); });

/**
 * Show a sequence of Tinycodes
 */
$app->route('tinycode', '/tiny', function(){
	Tinycode::init();
	header('content-type: text/plain');
	echo 'Total codes: ' . Tinycode::max_int() . "\n";
	for($z = 1; $z <=300; $z++) {
		$s = Tinycode::to_code($z);
		$n = Tinycode::to_int($s);
		echo $z . ' encoded => ' . $s . ' decoded => ' . $n . "\n";
	}
});

$app->route('first', '/one/two', function() {
	echo 'one/two';
});
$app->route('second', '/one/two/three', function() {
	echo 'one/two/three';
});
$app->route('first2', '/one/:two', function() {
	echo 'one/:two';
});
$app->route('second2', '/one/:two/three', function() {
	echo 'one/:two/three';
});

$app->route('template', '/template', function(Response $response) {
	Template::register('tpl');
	$response['name'] = '<b>Owen Winkler</b>';
	$user = new stdClass();
	$user->city = 'Chester Springs';
	$user->age = 39;
	$user->gender = 'male';
	$response['user'] = $user;
	$response['rows'] = [['cell' => 4],['cell' => '<b>3</b>'],['cell' => 12],['cell' => 8],];
	$response['values'] = [1,3,5,8,15];
	return $response->render('template.tpl');
});

class UserHandler extends \Microsite\Handler {

	public function load(App $app) {
		parent::load($app);
		$app->route('do_routed', '/routed/:number', [$this, 'do_routed'])
			->validate_fields([':number' => '[0-9]+']);
	}

	/**
	 * @url /
	 */
	public function do_index(App $app) {
		echo 'do index';
		var_dump($app);
	}

	/**
	 * @url /get
	 * @method GET
	 */
	public function do_get() {
		echo 'do get';
	}

	/**
	 * @url /post
	 * @method POST
	 */
	public function do_post() {
		echo 'do post';
	}

	/**
	 * @param Request $request
	 * @url /value/:value
	 */
	public function do_value(Request $request) {
		echo $request['value'];
	}

	public function do_routed(Request $request) {
		echo 'routed.  The number was: ' . $request['number'];
	}
}

$app->route('user', '/user', \Microsite\Handler::mount('UserHandler'));


/**
 * Run the app to match and dispatch routes
 */
$app();
