<?php

namespace Microsite\DB\PDO;

use \PDO;

class DB extends PDO
{
	protected $pdo_statement;
	protected $fetch_class;

	/**
	 * Construct a database object
	 * @param string $connect_string A connection string like "sqlite:{filename}"
	 * @param string $username The username for a connection
	 * @param string $password The password for a connection
	 */
	public function __construct($connect_string, $username = '', $password = '')
	{
		try {
			parent::__construct($connect_string, $username, $password);
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
		}
		catch (\PDOException $e) {
			throw new \Exception('Connection to "' . $connect_string . '" failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Run a query against a database.  Get a result
	 * @param string $query The SQL to run against the database
	 * @param array $args An associative array of query parameters
	 * @return bool|\PDOStatement False if query fails, results in this database's fetch_class if successful
	 * @throws \Exception
	 */
	public function query($query, $args = array())
	{
		if(!empty($this->pdo_statement)) {
			$this->pdo_statement->closeCursor();
		}

		if($this->pdo_statement = $this->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true))) {
			$this->pdo_statement->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $this->fetch_class, [$this]);

			if(!$this->pdo_statement->execute($args)) {
				throw new \Exception($this->pdo_statement->errorInfo());
			}
			return true;
		}
		else {
			throw new \Exception($this->errorInfo());
		}
	}

	/**
	 * Get all of the results for a query
	 * @param string $query The SQL to get the results for
	 * @param array $args An associative array of parameters for the query
	 * @param string $class_name An optional class to use as each row instance in the result array
	 * @return array|bool  The results in an array if successful, false if fail
	 */
	public function results($query, $args = array(), $class_name = '\Microsite\DB\PDO\Model')
	{
		$this->fetch_class = $class_name;
		if($this->query($query,$args)) {
			return $this->pdo_statement->fetchAll();
		}
		else {
			return false;
		}
	}

	/**
	 * Get a single row from a query
	 * @param string $query The SQL to get the results for
	 * @param array $args An associative array of parameters for the query
	 * @param string $class_name An optional class to use for the result
	 * @return \Microsite\DB\PDO\Model|bool|object  The result in a Model object if successful, false if fail
	 */
	public function row($query, $args = array(), $class_name = '\Microsite\DB\PDO\Model')
	{
		$this->fetch_class = $class_name;

		if($this->query($query,$args)) {
			return $this->pdo_statement->fetch();
		}
		else {
			return false;
		}
	}

	/**
	 * Get all of the values for a specific column from a query
	 * @param string $query The SQL to get the results for
	 * @param array $args An associative array of parameters for the query
	 * @return array|bool  The results in an array if successful, false if fail
	 */
	public function col($query, $args = array())
	{
		if($this->query($query,$args)) {
			return $this->pdo_statement->fetchAll(PDO::FETCH_COLUMN);
		}
		else {
			return false;
		}
	}

	/**
	 * Get a single field value result for a query
	 * @param string $query The SQL to get the result for
	 * @param array $args An associative array of parameters for the query
	 * @return mixed|bool  The result if successful, false if fail
	 */
	public function val($query, $args = array())
	{
		if($this->query($query,$args)) {
			$result = $this->pdo_statement->fetch(PDO::FETCH_NUM);
			return $result[0];
		}
		else {
			return false;
		}
	}

	/**
	 * Get an associative array of the results for a query
	 * @param string $query The SQL to get the results for
	 * @param array $args An associative array of parameters for the query
	 * @param int|string $keyfield The index or name of the field to use as the key in the result array
	 * @param int|string $valuefield The index or name of the field to use as the value in the result array, or the name of
	 *   a class that should be used as the result class for each row in the result array
	 * @internal param string $class_name An optional class to use as each row instance in the result array
	 * @return array|bool  The results in an array if successful, false if fail
	 */
	public function assoc($query, $args = array(), $keyfield = 0, $valuefield = 1)
	{
		if($this->query($query, $args)) {
			if(is_string($valuefield) && class_exists($valuefield, true)) {
				$this->fetch_class = $valuefield;
				$result = $this->pdo_statement->fetchAll();
			}
			else {
				$result = $this->pdo_statement->fetchAll(PDO::FETCH_NUM);
			}
			$output = array();
			foreach($result as $item) {
				if(is_object($item)) {
					$output[$item->$keyfield] = $item;
				}
				elseif(is_array($item)) {
					$output[$item[$keyfield]] = $item[$valuefield];
				}
			}
			return $output;
		}
		else {
			return false;
		}
	}

	public static function inclause($values, $prefix)
	{
		$out = array();
		$index = 0;
		foreach($values as $value) {
			$index++;
			$out[':' . $prefix . $index] = $value;
		}
		return $out;
	}
}

?>
