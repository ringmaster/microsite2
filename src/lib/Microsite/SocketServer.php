<?php

namespace Microsite;

/**
 * Create a server using a TCP/UDP socket
 */
class SocketServer
{
	var $port = 2282;
	var $max_clients = 10;
	protected $routes = [];

	/**
	 * @param int $port The port on which this server will listen
	 */
	public function __construct($port)
	{
		$this->port = $port;
	}

	/**
	 * Bind a function to a parsed socket request
	 * @param string $name The name of the binding
	 * @param string $bind_condition The condition under which to handle this binding
	 * @param Callable $handler The callback that will handle this binding
	 * @return \Microsite\Route
	 */
	public function bind($name, $bind_condition, $handler)
	{
		$args = func_get_args();
		$name = array_shift($args);
		$bind_condition = array_shift($args);
		$route = new Route($bind_condition);
		foreach($args as $arg) {
			$route->add_handler($arg);
		}
		$this->routes[$name] = $route;
		return $route;
	}

	public function run()
	{
		$listener = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		socket_set_option($listener, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($listener, 0, $this->port);
		socket_listen($listener, $this->max_clients);

		$clients = [['socket' => $listener]];

		while (true) {
			$read = [];
			foreach($clients as $client) {
				$read[] = $client['socket'];
			}

			$ready = socket_select($read, $write = NULL, $except = NULL, $tv_sec = NULL);

			foreach($clients as $client_index => $client) {
				if( in_array($client['socket'], $read) ) {
					if( $client['socket'] == $listener) {
						if(count($clients) > $this->max_clients) {
							echo "Max clients reached.  Rejected new connection.\r\n";
						}
						else {

							$newsocket = socket_accept($listener);
							socket_getpeername($newsocket, $ip);

							$client = [
								'socket' => $newsocket,
								'ip' => $ip,
							];

							$clients[] = $client;

							socket_write($client['socket'], "Welcome to my Custom Socket Server\r\n");
							$client_count = count($clients) - 1;
							socket_write($client['socket'], "There are {$client_count} client(s) connected to this server.\r\n");

							echo "New client connected: {$client['ip']}\r\n";
							echo "Client count: {$client_count}\r\n";
						}
					}
					else {

						$data = @socket_read($client['socket'], 1024, PHP_NORMAL_READ);

						if( $data === FALSE ) {
							unset($clients[$client_index]);
							var_dump($client);
							echo "{$client['ip']}:{$client_index} Client disconnected!\r\n";
							continue;
						}

						$data = trim($data);

						if( !empty($data) ) {
							// @todo remove this and allow registered handlers to exit
							if( $data == 'exit' ) {
								socket_write($client['socket'], "Thanks for trying my Custom Socket Server, goodbye.\r\n");
								socket_close($client['socket']);
								echo "Client {$client['ip']}:{$client_index} is exiting.\r\n";
								unset($clients[$client_index]);
								continue;
							}

							echo("{$client['ip']}:{$client_index} is sending a message!\r\n");
							foreach($clients as $dest_index => $dest_client) {
								if( isset($dest_client['socket']) ) {
									if ($dest_index != $client_index && ($dest_client['socket'] != $listener) ) {
										socket_write($dest_client['socket'], "[{$client['ip']}:{$client_index}] says: {$data}\r\n");
									}
								}
							}
							break;
						}
					}
				}
			}
		}
	}
}

?>
