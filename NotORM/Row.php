<?php

/** Single row representation
*/
class NotORM_Row implements IteratorAggregate, ArrayAccess {
	private $row, $primary, $table, $result, $pdo, $structure;
	
	function __construct(array $row, $primary, $table, NotORM_Result $result, PDO $pdo, NotORM_Structure $structure) {
		$this->row = $row;
		$this->primary = $primary;
		$this->table = $table;
		$this->result = $result;
		$this->pdo = $pdo;
		$this->structure = $structure;
	}
	
	/** Get primary key value
	* @return string
	*/
	function __toString() {
		return $this->row[$this->primary];
	}
	
	/** Get referenced row
	* @param string
	* @return NotORM_Row
	*/
	function __get($name) {
		$column = $this->structure->getReferencedColumn($name, $this->table);
		$return = &$this->result->referenced[$name];
		if (!isset($return)) {
			$table = $this->structure->getReferencedTable($name, $this->table);
			$keys = array();
			foreach ($this->result->getRows() as $row) {
				$keys[$row[$column]] = null;
			}
			$return = new NotORM_Result($table, $this->pdo, $this->structure);
			$return->where($this->structure->getPrimary($table), array_keys($keys));
		}
		return $return[$this->row[$column]];
	}
	
	/** Get referencing rows
	* @param string table name 
	* @param array (["condition"[, array("value")]])
	* @return NotORM_Result
	*/
	function __call($table, array $args) {
		$column = $this->structure->getReferencingColumn($this->table, $table);
		$return = new NotORM_MultiResult($table, $this->pdo, $this->structure, $this->result, $column, $this->row[$this->primary]);
		$return->where($column, array_keys($this->result->getRows()));
		//~ $return->order($column); // to allow multi-column indexes
		return $return;
	}
	
	// IteratorAggregate implementation
	
	function getIterator() {
		return new ArrayIterator($this->row);
	}
	
	// ArrayAccess implementation
	
	function offsetExists($key) {
		return array_key_exists($key, $this->row);
	}
	
	function offsetGet($key) {
		return $this->row[$key];
	}
	
	function offsetSet($key, $value) {
		$this->row[$key] = $value;
	}
	
	function offsetUnset($key) {
		unset($this->row[$key]);
	}
	
}