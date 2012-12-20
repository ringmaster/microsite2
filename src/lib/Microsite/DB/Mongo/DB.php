<?php

namespace Microsite\DB\Mongo;

/**
 * Class Db
 *
 * @package SimpleMongoPhp
 * @author Ian White (ibwhite@gmail.com)
 * @version 1.2
 *
 * This is a simple library to wrap around the Mongo API and make it a little more convenient
 * to use for a PHP web application.
 *
 * To set up, all you need to do is:
 *    - include() or require() this file
 *    - call Db::addConnection()
 *
 * Example usage:
 *   $mongo = new Mongo();
 *   Db::addConnection($mongo, 'lost');
 *
 *   Db::drop('people');
 *   Db::batchInsert('people', array(
 *     array('name' => 'Jack', 'sex' => 'M', 'goodguy' => true),
 *     array('name' => 'Kate', 'sex' => 'F', 'goodguy' => true),
 *     array('name' => 'Locke', 'sex' => 'M', 'goodguy' => true),
 *     array('name' => 'Hurley', 'sex' => 'M', 'goodguy' => true),
 *     array('name' => 'Ben', 'sex' => 'M', 'goodguy' => false),
 *   ));
 *   foreach (Db::find('people', array('goodguy' => true), array('sort' => array('name' => 1))) as $p) {
 *     echo $p['name'] " is a good guy!\n";
 *   }
 *   $ben = Db::findOne('people', array('name' => 'Ben'));
 *   $locke = Db::findOne('people', array('name' => 'Locke'));
 *   $ben['enemy'] = Db::createRef('people', $locke);
 *   $ben['goodguy'] = null;
 *   Db::save('people', $ben);
 *
 * See the Dbo.php class for how you could do the same thing with data objects.
 *
 * This library may be freely distributed and modified for any purpose.
 **/
class DB
{
	static $connections;
	static $read_slave = false;

	protected $connnection;
	protected $database;

	function __construct($collection_name, $server = 'mongodb://localhost:27017', $options = ['connect' => true])
	{
		$this->connection = new \Mongo($server, $options);
		$this->database = $this->connection->selectDB($collection_name);
	}

	/**
	 * Returns a MongoId from a string, MongoId, array, or Dbo object
	 *
	 * @param mixed $obj
	 * @return \MongoId
	 **/
	static function id($obj) {
		if ($obj instanceof \MongoId) {
			return $obj;
		}
		if (is_string($obj)) {
			return new \MongoId($obj);
		}
		if (is_array($obj)) {
			return $obj['_id'];
		}
		return new \MongoId($obj->_id);
	}

	/**
	 * Returns true if the value passed appears to be a Mongo database reference
	 *
	 * @param $value
	 * @return boolean
	 */
	static function isRef($value) {
		if (!is_array($value)) {
			return false;
		}
		return \MongoDBRef::isRef($value);
	}

	/**
	 * Returns a database cursor for a Mongo find() query.
	 *
	 * Pass the query and options as array objects (this is more convenient than the standard
	 * Mongo API especially when caching)
	 *
	 * $options may contain:
	 *   fields - the fields to retrieve
	 *   sort - the criteria to sort by
	 *   limit - the number of objects to return
	 *   skip - the number of objects to skip
	 *
	 * @param string $collection
	 * @param array $query
	 * @param array $options
	 * @return \MongoCursor
	 **/
	public function find($collection_name, $query = array(), $options = array()) {
		$collection = $this->database->selectCollection($collection_name);
		$fields = isset($options['fields']) ? $options['fields'] : [];
		$cursor = $collection->find($query, $fields);
		if (isset($options['sort']) && $options['sort'] !== null) {
			$cursor->sort($options['sort']);
		}
		if (isset($options['limit']) && $options['limit'] !== null) {
			$cursor->limit($options['limit']);
		}
		if (isset($options['skip']) && $options['skip'] !== null) {
			$cursor->skip($options['skip']);
		}
		return $cursor;
	}

	/**
	 * Just like find, but return the results as an array (of arrays)
	 *
	 * @param string $collection
	 * @param array $query
	 * @param array $options
	 * @return array
	 **/
	static function finda($collection, $query = array(), $options = array()) {
		$result = self::find($collection, $query, $options);
		$array = array();
		foreach ($result as $val) {
			$array[] = $val;
		}
		return $array;
	}

	/**
	 * Do a find() but return an array populated with one field value only
	 *
	 * @param string $collection
	 * @param string $field
	 * @param array $query
	 * @param array $options
	 * @return array
	 **/
	static function findField($collection, $field, $query = array(), $options = array()) {
		$options['fields'] = array($field => 1);
		$result = self::find($collection, $query, $options);
		$array = array();
		foreach ($result as $val) {
			$array[] = $val[$field];
		}
		return $array;
	}

	/**
	 * Do a find() returned as an associative array mapping one field to another
	 *
	 * @param string $collection
	 * @param string $key_field
	 * @param string $value_field
	 * @param array $query
	 * @param array $options
	 * @return array
	 **/
	static function findAssoc($collection, $key_field, $value_field, $query = array(), $options = array()) {
		$options['fields'] = array($key_field => 1, $value_field => 1);
		$result = self::find($collection, $query, $options);
		$array = array();
		foreach ($result as $val) {
			$array[$val[$key_field]] = $val[$value_field];
		}
		return $array;
	}

	/**
	 * Find a single object -- like Mongo's findOne() but you can pass an id as a shortcut
	 *
	 * @param string $collection
	 * @param mixed $id
	 * @return array
	 **/
	static function findOne($collection, $id) {
		$col = self::getCollection($collection, true);
		if (!is_array($id)) {
			$id = array('_id' => self::id($id));
		}
		return $col->findOne($id);
	}

	/**
	 * Count the number of objects matching a query in a collection (or all objects)
	 *
	 * @param string $collection
	 * @param array $query
	 * @return integer
	 **/
	static function count($collection, $query = array()) {
		$col = self::getCollection($collection, true);
		if ($query) {
			$res = $col->find($query);
			return $res->count();
		} else {
			return $col->count();
		}
	}

	/**
	 * Save a Mongo object -- just a simple shortcut for MongoCollection's save()
	 *
	 * @param string $collection
	 * @param array $data
	 * @return boolean
	 **/
	static function save($collection, $data) {
		$col = self::getCollection($collection);
		return $col->save($data);
	}

	public function insert($collection_name, $data, $options = array()) {
		$collection = $this->database->selectCollection($collection_name);
		return $collection->insert($data, $options);
	}

	static function lastError($collection = null, $read_only = false) {
		$db = self::getDb($collection, $read_only);
		return $db->lastError();
	}

	/**
	 * Shortcut for MongoCollection's update() method
	 *
	 * @param string $collection_name
	 * @param array $criteria
	 * @param array $newobj
	 * @param bool|array $options
	 * @return boolean
	 */
	public function update($collection_name, $criteria, $newobj, $options = array()) {
		$collection = $this->database->selectCollection($collection_name);
		if ($options === true) {
			$options = array('upsert' => true);
		}
		if (!isset($options['multiple'])) {
			$options['multiple'] = false;
		}
		return $collection->update($criteria, $newobj, $options);
	}

	static function updateConcurrent($collection, $criteria, $newobj, $options = array()) {
		$col = self::getCollection($collection);
		if (!isset($options['multiple'])) {
			$options['multiple'] = false;
		}
		$i = 0;
		foreach ($col->find($criteria, array('fields' => array('_id' => 1))) as $obj) {
			$col->update(array('_id' => $obj['_id']), $newobj);
			if (empty($options['multiple'])) {
				return;
			}
			if (!empty($options['count_mod']) && $i % $options['count_mod'] == 0) {
				if (!empty($options['count_callback'])) {
					call_user_func($options['count_callback'], $i);
				} else {
					echo '.';
				}
			}
			$i++;
		}
	}

	/**
	 * Shortcut for MongoCollection's update() method, performing an upsert
	 *
	 * @param string $collection
	 * @param array $criteria
	 * @param array $newobj
	 * @return boolean
	 **/
	static function upsert($collection, $criteria, $newobj) {
		return self::update($collection, $criteria, $newobj, true);
	}

	/**
	 * Shortcut for MongoCollection's remove() method, with the option of passing an id string
	 *
	 * @param string $collection
	 * @param array $criteria
	 * @param boolean $just_one
	 * @return boolean
	 **/
	static function remove($collection, $criteria, $just_one = false) {
		$col = self::getCollection($collection);
		if (!is_array($criteria)) {
			$criteria = array('_id' => self::id($criteria));
		}
		return $col->remove($criteria, $just_one);
	}

	/**
	 * Shortcut for MongoCollection's drop() method
	 *
	 * @param string $collection
	 * @return boolean
	 **/
	public function drop($collection_name) {
		$collection = $this->database->selectCollection($collection_name);
		var_dump($collection);
		return $collection->drop();
	}

	/**
	 * Shortcut for MongoCollection's batchInsert() method
	 *
	 * @param string $collection
	 * @param array $array
	 * @return boolean
	 **/
	static function batchInsert($collection, $array) {
		$col = self::getCollection($collection);
		return $col->batchInsert($array);
	}

	static function group($collection, array $keys, array $initial, $reduce, array $condition = array()) {
		$col = self::getCollection($collection, true);
		return $col->group($keys, $initial, $reduce, $condition);
	}

	/**
	 * Shortcut for MongoCollection's ensureIndex() method
	 *
	 * @param string $collection
	 * @param array $keys
	 * @return boolean
	 **/
	static function ensureIndex($collection, $keys, $options = array()) {
		$col = self::getCollection($collection);
		return $col->ensureIndex($keys, $options);
	}

	/**
	 * Ensure a unique index
	 *
	 * @param string $collection
	 * @param array $keys
	 * @return boolean
	 **/
	static function ensureUniqueIndex($collection, $keys, $options = array()) {
		$options['unique'] = true;
		return self::ensureIndex($collection, $keys, $options);
	}

	/**
	 * Shortcut for MongoCollection's getIndexInfo() method
	 *
	 * @param string $collection
	 * @return array
	 **/
	static function getIndexInfo($collection) {
		$col = self::getCollection($collection, true);
		return $col->getIndexInfo();
	}

	/**
	 * Shortcut for MongoCollection's deleteIndexes() method
	 *
	 * @param string $collection
	 * @return boolean
	 **/
	static function deleteIndexes($collection) {
		$col = self::getCollection($collection);
		return $col->deleteIndexes();
	}
}

?>