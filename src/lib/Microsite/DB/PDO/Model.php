<?php

namespace Microsite\DB\PDO;

class Model implements \ArrayAccess, \IteratorAggregate, \Countable, \Serializable, \JsonSerializable
{
	protected $fields = array();
	protected $db = null;

	function __construct(DB $db) {
		$this->db = $db;
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset An offset to check for.
	 * @return boolean true on success or false on failure.
	 * The return value will be cast to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset)
	{
		return isset($this->fields[$offset]);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset The offset to retrieve.
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset)
	{
		return $this->fields[$offset];
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset The offset to assign the value to.
	 * @param mixed $value The value to set.
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->fields[$offset] = $value;
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset The offset to unset.
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->fields[$offset]);
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing Iterator or Traversable
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->fields);
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * The return value is cast to an integer.
	 */
	public function count()
	{
		return count($this->fields);
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize()
	{
		return serialize($this->fields);
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized The string representation of the object.
	 * @return mixed the original value unserialized.
	 */
	public function unserialize($serialized)
	{
		return $this->fields = unserialize($serialized);
	}

	/**
	 * Serializes the object to a value that can be serialized natively by json_encode().
	 * @link http://docs.php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed Returns data which can be serialized by json_encode(), which is a value of any type other than a resource.
	 */
	function jsonSerialize()
	{
		return $this->fields;
	}


	public function insert($table) {
		$sql = "INSERT INTO {$table} (";
		$sql .= implode(', ', array_keys($this->fields));
		$sql .= ') VALUES (';
		$sql .= implode(', ', array_map(array($this, '_pdo_field_prefix'), array_keys($this->fields)));
		$sql .= ');';
		$this->db->query($sql, $this->fields);
	}

	public function update($table, $index) {
		if(is_string($index)) {
			$index = array($index => $this->fields[$index]);
		}
		$update_fields = array_diff_key($this->fields, $index);

		$sql = "UPDATE {$table} SET ";
		$sql .= implode(', ', array_map(array($this, '_pdo_insert_prefix'), array_keys($update_fields)));
		$sql .= ' WHERE ';
		$sql .= implode(' AND ', array_map(array($this, '_pdo_insert_prefix'), array_keys($index)));

		$this->db->query($sql, array_merge($update_fields, $index));
	}

	public function update_insert($table, $index) {
		if(is_string($index)) {
			$index = array($index => $this->fields[$index]);
		}
		$sql = "SELECT count(*) FROM {$table} WHERE ";
		$sql .= implode(' AND ',array_map(array($this, '_pdo_insert_prefix'), array_keys($index)));

		if($this->db->val($sql, $index) > 0) {
			$this->update($table, $index);
		}
		else {
			$this->insert($table);
		}
	}

	/**
	 * Set a field value by object property
	 * @param string $fieldname The name of a field
	 * @param mixed $value The value of the field
	 */
	public function __set($fieldname, $value) {
		$this->fields[$fieldname] = $value;
	}

	/**
	 * Get a field value by object property
	 * @param string $fieldname The name of the field
	 * @return mixed The field value
	 */
	public function __get($fieldname) {
		return $this->fields[$fieldname];
	}

	/**
	 * Get an array of field names for this Model
	 * @return array An array of field names
	 */
	public function get_fields() {
		return array_keys($this->fields);
	}

	public function __call($name, $values) {
		if(array_key_exists($name, $this->fields)) {
			$this->fields[$name] = $values[0];
			return $this;
		}
		if (is_callable($name) && substr($name, 0, 6) !== 'array_') {
			return call_user_func_array($name, array_merge(array($this->fields), $values));
		}
		throw new \BadMethodCallException(get_called_class().'->'.$name);
	}

	private function _pdo_field_prefix($field) {
		return ':' . $field;
	}

	private function _pdo_insert_prefix($field) {
		return "{$field} = :{$field}";
	}

	/**
	 * Return an object with the field values as object properties
	 * @return \StdClass The field values as stdClass object properties
	 */
	public function std() {
		$obj = new \StdClass();
		foreach($this->fields as $key => $value) {
			$obj->$key = $value;
		}
		return $obj;
	}

	/**
	 * Return the fields as an associative array
	 * @return array The field values in an associative array
	 */
	public function ary() {
		return $this->fields;
	}
}

?>
