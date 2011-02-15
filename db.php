<?php
	/**
		\class table
		Provides CRUD functionality. Detects automatically all the columns of a table.
			@package rolisz
			@author Roland Szabo
	
	**/
	class table {
		
		private $databaseInfo;
		private $originalData = array();
		private $modifiedData = array();
		private $primaryKey;
		private $hydrated;
		private $connection;

		
		/**
			Called from the overloaded constructor of the class. Initializes the table. 
				@param databaseAdapter $connection - A database Adapter object
				@param string $table
				@param array $hydrater - An array containing the values with which to initialize the object. Optional	
		**/
		public function __setupDB($connection, $table, $hydrated = FALSE) {
			$this->connection = $connection;
			if ($this->connection->tableExists($table)) {
				$this->table = $table;
			}
			else { 
				trigger_error("$table table does not exist");
			}
			$this->getColumns();
			if (is_array($hydrater)) {
				$this->hydrater = $hydrater;
				$this->hydrate();
			}
		}
		
		/**
			Automagic function for setting dynamic properties
				@param string $name
				@param string $value
		**/
		public function __set($name, $value) {
			if (!isset($this->data[$name])) {
					trigger_error("$name column doesn't exist in $this->table table ");
			}
			$this->data[$name] = $value;
		}
			
		/**
			Automagic function for accesing dynamic properties
				@param string $name
				@return mixed
		**/
		public function __get($name) {
			if (array_key_exists($name, $this->data)) {
				return $this->data[$name];
			}

			$trace = debug_backtrace();
			trigger_error(
				'Undefined property via __get(): ' . $name .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
			return null;
		}

		/**
			Automagic function for finding out if dynamic properties are set
				@param string $name
				@return TRUE|FALSE
		**/
		public function __isset($name) {
			return isset($this->data[$name]);
		}

		/**
			Automagic function for unsetting dynamic properties 
				@param string $name
				@return TRUE|FALSE
		**/
		public function __unset($name) {
			unset($this->data[$name]);
		}
		
		/**
			Populate the object with data from the table, selected according to $hydrater parameter in constructor
		
		**/
		private function hydrate() {
			$results = $this->connection->fetchRow("SELECT * FROM $this->table WHERE ".key($this->hydrater)."='".current($this->hydrater)."'");
			if ($results) {
				foreach ($results as $key=>$value) {
					$this->data[$key] = $value;
				}
			}
			else {
				trigger_error("Can't find ".key($this->hydrater)." equal to ".current($this->hydrater)." in $this->table");
			}
		}
		
		/**
			Save changes made to object. If the object was hydrated, it will be updated. If it was created from scratch, it will be inserted into the database
		
		**/
		public function save() {
			if ($this->hydrater) {
				$query="UPDATE ";
			}
			else {
				$query="INSERT INTO ";
			}
			$query.=$this->table.' SET ';
			foreach ($this->data as $key=>$value) {
				$query.= $key ."='".$value.'\',';
			}
			$query = substr($query,0,-1);
			if ($this->hydrater) {
				$query.=' WHERE '.key($this->hydrater).'=\''.current($this->hydrater)."'";
			}
			$this->connection->query($query);
		}
		
		/**
			Delete the corresponding record from the database
		
		**/
		public function delete() {
			if ($this->hydrater) {
				$query = $this->connection->query("DELETE FROM $this->table WHERE ".key($this->hydrater).'=\''.current($this->hydrater)."'");
				$query = $this->connection->query("ALTER TABLE $this->table AUTO_INCREMENT = 1");
			}	
			else 
				trigger_error('You are trying to delete an inexistent row');
		}
			
		/**
			Populates the $data property of the object with the columns of the table and gets the primary key of the table, if it exists
		
		**/
		private function getColumns() {
			$cols = $this->connection->fetchAll("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
						WHERE TABLE_SCHEMA = '".$this->connection->database."' AND TABLE_NAME = '$this->table'");
			foreach ($cols as $col) {
				$this->data[$col['COLUMN_NAME']] = '';
			}
			$primary = $this->connection->fetchRow("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
						WHERE TABLE_SCHEMA = '".$this->connection->database."' AND TABLE_NAME = '$this->table' AND COLUMN_KEY='PRI'");
			if ($primary) {
				$this->id = $primary['COLUMN_NAME'];
			}
			else {
				$this->id = FALSE;
			}	
		}
	}
/*
$conn = new MySQLiDatabase('localhost','root','','rolisz');

$table = new table($conn,'users',array('user'=>'teosz'));
$table->user='teosz';
$table->delete();
$rolisz = new table($conn,'users',array('id'=>1));
echo $rolisz->password;
*/
?>