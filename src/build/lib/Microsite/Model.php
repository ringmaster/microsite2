<?php

namespace Microsite;

class Model
{
	protected $fields = array();
	protected $db = null;

	function __construct($db)
	{
		$this->db = $db;
	}

//	public static function get($table, $crit, $class = 'Model') {
//		$sql = "SELECT * FROM {$table} WHERE ";
//		$sql .= implode(' AND ', array_map(array($this, '_pdo_insert_prefix'), array_keys($crit)));
//
//		$this->db->row($sql, $crit, $class);
//	}

	public function insert($table){
		$sql = "INSERT INTO {$table} (";
		$sql .= implode(', ', array_keys($this->fields));
		$sql .= ') VALUES (';
		$sql .= implode(', ', array_map(array($this, '_pdo_field_prefix'), array_keys($this->fields)));
		$sql .= ');';
		$this->db->query($sql, $this->fields);
	}

	public function update($table, $index){
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

	public function __set($fieldname, $value) {
		$this->fields[$fieldname] = $value;
	}

	public function __get($fieldname) {
		return $this->fields[$fieldname];
	}

	public function get_fields() {
		return array_keys($this->fields);
	}

	public function __call($name, $value) {
		if(array_key_exists($name, $this->fields)) {
			$this->fields[$name] = $value[0];
			return $this;
		}
	}

	private function _pdo_field_prefix($field){
		return ':' . $field;
	}

	private function _pdo_insert_prefix($field) {
		return "{$field} = :{$field}";
	}

	public function std() {
		$obj = new \StdClass();
		foreach($this->fields as $key => $value) {
			$obj->$key = $value;
		}
		return $obj;
	}
}

?>