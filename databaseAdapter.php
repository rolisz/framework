<?php
	/**
		\class databaseAdapter
		Defines all the functions a database adapter should have to be working with rolisz
			@package rolisz
			@author Szabo Roland
			@version 0.0.0.4
			@todo implements countable, iterator
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
				@todo change return values for insert, update, delete, select
		**/
		public function Query($query) {
			$this->queries[] = $query;
			$this->result = $this->connection->query($query);
			if (!$this->result) {
				return false;
			}
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
			return $this->connection->insert_id;
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

?>