<?php
	/**
		\class databaseAdapter
		Defines all the functions a database adapter should have to be working with rolisz
			@package rolisz
			@author Szabo Roland
			@todo implements countable, iterator
	**/
	interface databaseAdapter {

		public function __construct($host, $username, $password, $db);
		public function connect($host, $username, $password);
		public function disconnect();
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
		public function createTable($name,$params);
		public function dropTable($name);
		public function alterTable($name,$params);
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
		 * 	Class constructor, initializes connection to MySQL database. Not implemented as
		 *  singleton pattern because you can have connections to different databases.
		 * 		@param string $host
		 * 		@param string $username
		 * 		@param string $password
		 * 		@param string $db 
		 **/
		public function __construct($host, $username, $password, $db) {
			$this->database = $db;
			$this->connect($host, $username, $password);
			$this->queries = array();
		}
		
		/* 
		 * 	Makes a new connection to MySQL database
		 * 		@param string $host
		 * 		@param string $username
		 * 		@param string $password
		 * 		@retval true
		 * 		@retval false
		 **/
		public function connect($host, $username, $password) {
			$this->connection = @new mysqli($host, $username, $password, $this->database);
			if ($this->connection->connect_error) {
				trigger_error('Connect Error (' . $this->connection->connect_errno . ') '
				. $this->connection->connect_error);
				return false;
			}
			return true;
		}
		
		/**
		 *	Disconnects from the database
		 **/
		public function disconnect() {
			$this->connection->close();
		}
		
		/**
		 *  Destructor of class. Disconnects from database.
		 */
		public function __destruct() {
			$this->disconnect();
		}
		
		/** 
		 * Connects to a different database
		 * 		@param string $db
		 * 		@retval true			
		 * 		@retval false			
		**/
		public function selectDatabase($db) {
			$this->database = $db;
			return $this->connection->select_db($db);
		}
		
		/** 
		 *	Escapes a string for safe MySQL insertion
		 *		@param string $value 
		 *		@return string			
		 **/
		public function escapeValue($value) {
			return $this->connection->real_escape_string($value);
		}
		
		/**
		 *	Executes query
		 *		@param string $query 
		 *		@return mixed
		 *		@todo change return values for insert, update, delete, select
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
		 *	Fetches first row from the results of a query. Returns numeric array, associative array or both depending on second parameter:1,2,3
		 * 		@param string $query		
		 * 		@param int $type 1 - Associative array, 2 - Numeric array, 3 - Both
		 * 		@return mixed
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
		 *	Fetches all the rows from the results of a query. Returns numeric array, associative array or both depending on second parameter:1,2,3
		 * 		@param string $query
		 * 		@param int $type 1 - Associative array, 2 - Numeric array, 3 - Both
		 * 		@return mixed
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
		 *	Return the last MySQLi error
		 *		@return string
		**/ 
		public function getError() {
			return $this->connection->error;
		}
		
		/** 
		 *	Returns the id of the last insertion
		 *		@return int
		**/
		public function getInsertID() {
			return $this->connection->insert_id;
		}
		
		/**
		 *	Returns the number of rows from a query
		 *		@return int
		**/
		public function numRows() {
			return $this->result->num_rows();
		}
		
		/**
		 *	Returns the number of rows affected by an UPDATE query
		 *		@return int
		**/
		public function numAffected() {
			return $this->connection->affected_rows();
		}
		
		/**
		 *	Checks if a table exists
		 *		@param string $table
		 *		@retval true
		 * 		@retval false
		**/
		public function tableExists($table) {
			$exists = $this->fetchRow('SHOW TABLES LIKE \''.$table."'");
			if ($exists)
				return true;
			return false;
		}
		
		/** 
		 * 	Creates a table.
		 * 		@param string $name Has to match this regex [A-Za-z0-9_]*
		 * 		@param array $params Example: array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),'text'=>array('TEXT','NOT NULL'))
		 * 		@retval true
		 * 		@retval false
		**/
		public function createTable($name,$params) {
			if (!is_array($params))
				return false;
			if (!preg_match('/^[A-Za-z0-9_.-]*$/',$name)) {
				return false;
			}
			$query = "CREATE TABLE `{$this->database}`.`{$name}` (";
			foreach ($params as $key => $value) {
				$query.= "`".$key."`".' '.implode(' ',$value).',';
			}
			$query = substr($query,0,-1);
			$query.= ') ENGINE = MYISAM;';
			$query = $this->Query($query);
			if ($query == TRUE) {
				return $query;
			}
			else {
				return $this->getError();
			}
		}
		
		/** 
		 * 	It drops $name table if it exists. If it doesn't exist it returns true (you wanted it gone, right?).
		 * 		@param string $name		
		 * 		@retval true
		 * 		@retval false
		**/
		public function dropTable($name) {
			if ($this->tableExists($name)) 
				return $this->Query("DROP TABLE {$name} ");
			else
				return true;
		}

		/** 
		 *  Alters $name table. To change it's name by $params must containt an element with key 'name' and value the new name of the table in params.
		 * 	To add a column, $paramas must contain an element with key 'add' and value an array of columns you wish to add (specified as at createTable. 
		 * 	To modify a column, $params must contain an element with key 'edit' and value an array of columns you wish to edit (specified as at createTable.
		 * 	To drop a column, $params must contain an $element with key 'delete' and value the name of the column you want to delete.
		 * 		@param string $name
		 * 		@param array $params
		 * 		@retval true
		 * 		@retval false
		 * 		@todo some checks for valid column names and parameters
		**/
		public function alterTable($name,$params) {
			if (!$this->tableExists($name)) 
				return false;
			$query = "ALTER TABLE `{$this->database}`.`{$name}` ";
			if (isset($params['name']) && preg_match('/^[A-Za-z0-9_.-]*$/',$params['name'])) {
				$query.= "RENAME TO `{$this->database}`.`{$params['name']}`, ";
			}
			if (isset($params['add']) && is_array($params['add'])) {
				foreach($params['add'] as $key=>$add) {
					$query.= 'ADD `'.$key.'` '.implode(' ',$add).', ';
				}	
			}
			if (isset($params['edit']) && is_array($params['edit'])) {
				foreach($params['edit'] as $key=>$edit) {
					$query.= 'CHANGE  `'.$key.'` `'.key($edit).'` '.implode(' ',current($edit)).', ';
				}	
			}
			if (isset($params['delete']) && is_array($params['delete'])) {
				foreach($params['delete'] as $del) {
					$query.= 'DROP `'.$del.'`,';
				}	
				
			}
			$query = substr($query,0,-1);
			return $this->Query($query);
			
		}
	}
?>