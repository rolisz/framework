<?php
	/**
		\class databaseAdapter
		Defines all the functions a database adapter should have to be working with rolisz
			@package rolisz
			@author Szabo Roland
			@copyright Szabo Roland 2011
			@version 0.0.0.0.2
			@access public
	**/
interface databaseAdapter {

	public function __construct($host, $username, $password, $db);
	public function connect($host, $username, $password);
	public function escapeValue($value);
	public function fetchRow($query=false, $type='assoc');
	public function fetchAll($query=false, $type='assoc');	
	public function getError();    
	public function getInsertID();
	public function numRows();
	public function numAffected();
	public function query($query);
	public function selectDatabase($db);	
	public function tableExists($table);
}

	/**
		\class MySQLiDatabase
		MySQLi specific implementation of databaseAdapter
			@package rolisz
			@author Roland Szabo

	 **/
class MySQLiDatabase implements databaseAdapter {

	/**
		Stores the MySQLi connection
	**/
	public $connection;
	/**
		Stores the latest result from a query
	**/
	public $result;
	/**
		Stores the working database
	**/
	public $database;
	/**
		Stores all the queries that have been executed
	**/
	public $queries;
	
	/**
		Class constructor, initializes connection to MySQL database
			@param string $host 
			@param string $username 
			@param string $password 
			@param string $db 
			@return void			
	
	**/
	
	public function __construct($host, $username, $password, $db) {
		$this->database = $db;
		$this->connect($host, $username, $password);
		$this->queries = array();
	}
	
	/** 
		Makes a new connection to MySQL database 
			@param string $host
			@param string $username 
			@param string $password 
			@retval TRUE|FALSE
	**/
	public function connect($host, $username, $password) {
		$this->connection = new mysqli($host, $username, $password, $this->database);
		if ($this->connection->connect_error) {
			trigger_error('Connect Error (' . $connection->connect_errno . ') '
            . $connection->connect_error);
			return false;
		}
		return true;
	}
	
	/**
		Changes to current database
			@param string $db 
			@retval TRUE |FALSE

		
	**/
	public function selectDatabase($db) {
		$this->database = $db;
		return $this->connection->select_db($db);
	}
	
	/** 
		Escapes a string
			@param string $value 
			@return string
		
	**/
	public function escapeValue($value) {
		return $this->connection->real_escape_string($value);
	}
	
	/**
		Executes query
			@param string $query 
			@return mixed
	
	**/
	public function Query($query) {
		$this->queries[] = $query;
		$this->result = $this->connection->query($query);
		return $this->result;
	}
	
	/**
		Fetches first row from the results of a query. Returns numeric array, associative array or both depending on second parameter:1,2,3
			@param string $query 
			@param int $type 1 - Associative array, 2 - Numeric array, 3 - Both
			@return mixed
	**/
	public function fetchRow($query=false, $type=1) {
		if ($query != false ) {
				$this->Query($query);
		}
		if ($this->result != false && $this->result->num_rows > 0 && $this->result->field_count > 0) {
			return $this->result->fetch_array($type);
			
		}
		return false;
	}
	
	/**
		Fetches all the rows from the results of a query. Returns numeric array, associative array or both depending on second parameter:1,2,3
			@param string $query 
			@param int $type 1 - Associative array, 2 - Numeric array, 3 - Both
			@return mixed
	**/
	public function fetchAll($query=false, $type=1){
		if ($query != false ) {
				$this->Query($query);
		}
		$result = array();
		if ($this->result != false && $this->result->num_rows > 0 && $this->result->field_count > 0) {
			while ($row = $this->result->fetch_array($type) ) {
				$result[] = $row;
			}
			return $result;
		}
		return false;
	}
	
	/**
		Return the last MySQLi error
			@retval string
	**/ 
	public function getError() {
		return $this->connection->error;
	}
	
	/** 
		Returns the id of the last insertion
			@retval int
	**/
	public function getInsertID() {
		return $this->connection->insert_id();
	}
	
	/**
		Returns the number of rows from a query
			@retval int
	**/
	public function numRows() {
		return $this->result->num_rows();
	}
	
	/**
		Returns the number of rows affected by an UPDATE query
			@retval int
	**/
	public function numAffected() {
		return $this->connection->affected_rows();
	}
	
	/**
		Checks if a table exists
			@param string $table
			@return TRUE|FALSE
	**/
	public function tableExists($table) {
		$exists = $this->fetchRow('SHOW TABLES LIKE \''.$table."'");
		if ($exists)
			return true;
		return false;
	}
}

	/**
		\class table
		Provides CRUD functionality. Detects automatically all the columns of a table.
			@package rolisz
			@author Roland Szabo
	
	**/
class table {
    /**  Location for overloaded data.  **/
    private $data = array();
	private $id;
	private $hydrater;
	private $connection;
	private $table;

	/**
		Constructor of the class. Initializes the table. 
			@param databaseAdapter $connection - A database Adapter object
			@param string $table
			@param array $hydrater - An array containing the values with which to initialize the object. Optional	
	**/
	public function __construct($connection, $table, $hydrater = FALSE) {
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