<?php
include_once('rolisz.php');

define('SINGLE', 'RELATION_SINGLE');
define('ONE_TO_MANY', 'RELATION_ONE_TO_MANY');
define('MANY', 'RELATION_MANY');
define('NOT_RECOGNIZED', 'RELATION_NOT_RECOGNIZED');
define('NOT_ANALYZED', 'RELATION_NOT_ANALYZED');
define('CUSTOM', 'RELATION_CUSTOM');
	/**
		\class table
		Provides CRUD functionality. Detects automatically all the columns of a table.
			@package rolisz
			@author Roland Szabo
	
	**/
	class table extends base{
		
		
		private $connection;
		/**
			Static variable containing the columns of all tables that have been instantiated
				@todo allow objects representing same table, but different columns
		**/
		static public $tables = array();
		/** 
			Contains the table name of this object  
		**/
		public $table;
		private $originalData = array();
		private $modifiedData = array();
		static private $primaryKey = array();
		/**
			Static variable containing the relations between tables
		**/
		static public $relations = array();

		/**
			Initializes the table.
				@param string $table
				@param int $id
				@param array $columns 
				@param databaseAdapter $connection
		**/
		public function __construct($table, $id=FALSE, $columns = FALSE, $connection = FALSE) {
			var_dump($connection);
			if ($connection) {
				$this->connection = $connection;
			}
			else {
				$this->connection = self::$global['dbCon'];
			}
			$this->table = $table;
			if ($columns && is_array($columns)) {
				self::$tables[$this->table] = $columns;
			}
			elseif (!isset(self::$tables[$table])) {
				if (!$this->getColumns()) {
					trigger_error("No table called $table found");
				}
			}
			if ($id) {
				$this->hydrate($id);
			}
		}
		
		/**
			Automagic function for setting dynamic properties
				@param string $name
				@param string $value
		**/
		public function __set($name, $value) {
			if (!array_search($name,self::$tables[$this->table])) {
					trigger_error("$name column doesn't exist in $this->table table ");
			}
			$this->modifiedData[$name] = $value;
		}
			
		/**
			Automagic function for accesing dynamic properties
				@param string $name
				@return mixed
		**/
		public function __get($name) {
			if (!in_array($name,self::$tables[$this->table])) {
				trigger_error("$name column doesn't exist in $this->table table ");
				return false;
			}
			if (array_key_exists($name, $this->modifiedData)) {
				return $this->modifiedData[$name];
			}
			if (array_key_exists($name, $this->originalData)) {
				return $this->originalData[$name];
			}
			trigger_error("$name has not been set yet");
			return null;
		}
		
		/**
			Automagic function that checks if a column exists
				@param string $name
				@return TRUE|FALSE
		**/
		public function __isset($name) {
			if (!in_array($name,self::$tables[$this->table])) {
			echo 'bong';
				return false;
			}
			return true;
		}
		
		/**
			Magic function for serialization
		**/
		public function __sleep() {
			$result = array_merge($this->originalData,$this->modifiedData);
			return $result;
		}
		
		/**
			Populate the object with data from the table, selected according to primary key value
				@param number $id
				@return array 
		
		**/
		private function hydrate($id) {
			$results = $this->connection->fetchRow("SELECT * FROM {$this->table} WHERE ".self::$primaryKey[$this->table]."='$id'");
			if ($results) {
				foreach ($results as $key=>$value) {
					$this->originalData[$key] = $value;
				}
			}
			else {
				trigger_error("Can't find ".self::$primaryKey[$this->table]." equal to $id in {$this->table}");
				return false;
			}
		}
		
		/**
			Save changes made to object. If the object was hydrated, it will be updated. If it was created from scratch, it will be inserted into the database
		
		**/
		public function save() {
			if (!empty($this->originalData)) {
				$query="UPDATE ";	
				for ($i=0; $i<count($this->originalData); $i++) {
					if (!isset($this->modifiedData)) {
						$this->modifiedData = $this->originalData;
					}
				}
			}
			else {
				$query="INSERT INTO ";
			}
			$query.=$this->table.' SET ';
			foreach ($this->modifiedData as $key=>$value) {
				$query.= $key ."='".$value.'\',';
			}
			$query = substr($query,0,-1);
			if (!empty($this->originalData)) {
				$query.=' WHERE '.self::$primaryKey[$this->table].'=\''.$this->originalData[self::$primaryKey[$this->table]]."'";
			}
			$this->connection->query($query);
			if (empty($this->originalData)) {
				$this->originalData[self::$primaryKey[$this->table]] = $this->connection->getInsertID();
				return $this->connection->getInsertID();
			}
		}
		
		/**
			Delete the corresponding record from the database
		
		**/
		public function delete() {
			if (!empty($this->originalData)) {
				$query = $this->connection->query("DELETE FROM {$this->table} WHERE ".self::$primaryKey[$this->table]."='{$this->originalData[self::$primaryKey[$this->table]]}'");
				$query = $this->connection->query("ALTER TABLE $this->table AUTO_INCREMENT = 1");
				$this->originalData = array();
			}	
			else 
				trigger_error('You are trying to delete an inexistent row');
		}
			
		/**
			Populates the $data property of the object with the columns of the table and gets the primary key of the table, if it exists
		
		**/
		private function getColumns() {
			$cols = $this->connection->fetchAll("SELECT COLUMN_NAME, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS
						WHERE TABLE_SCHEMA = '".$this->connection->database."' AND TABLE_NAME = '$this->table'");
			if (!$cols) {
				return FALSE;
			}
			self::$primaryKey[$this->table]=FALSE;
			foreach ($cols as $col) {
				self::$tables[$this->table][] = $col['COLUMN_NAME'];
				if ($col['COLUMN_KEY']=='PRI') {
					self::$primaryKey[$this->table] = $col['COLUMN_NAME'];
				}
			}
			return TRUE;
		}
		
		/**
			Makes and executes a SQL query, based on various filters, orders, groups, and columns to return.
				@param string $table Table in which to search
				@param array $filters Filters to apply. Can be name=>value pair, SQL statement, or recursive array relating to conditions on other tables
				@param array $ordergroup How to order, group and limit the search
				@param array $columns What columns to return in search
				@return array
		
		**/
		public function find($table, $filters = array(), $ordergroup = array(), $columns = array()) {
			
			$pK = self::$primaryKey[$this->table];
			if($pK != false && isset($this->originalData[$pK])) {
				$filters[$pK] = $this->originalData[$pK];
				$filters = array($this->table => $filters);
			}
			$query = new Query($table, $filters, $ordergroup, $columns);
			$results = $this->connection->fetchAll($query->buildQuery());
			//return $results;
			return self::importRows($table,$results);		
		}
		

		/** 
			Adds a relation to another table. This is not for many to many tables. Use addRelationM2M for that. If third argument isn't passed, it defaults to the table primary key
				@param string $tablename
				@param string $arg1 
				@param string $arg2
		**/
		public function addRelation($tablename, $arg1, $arg2=FALSE) {
			if ($arg2) {
				$origKey = $arg1;
				$targetKey = $arg2;
			}
			else {
				$origKey = self::$primaryKey[$this->table];
				$targetKey = $arg1;
			}
			if (isset(self::$global['dbCon']) && self::$global['dbCon']==$this->connection) 
				$table = new table ($tablename);
			else {
			echo 'ceva'.$tablename;
				$table = new table ($tablename,FALSE,FALSE,$this->connection);
			}
			if (isset($table->$targetKey)) {
				self::$relations[$this->table][$tablename] = new stdClass();
				self::$relations[$this->table][$tablename]->origKey = $origKey;
				self::$relations[$this->table][$tablename]->targetKey = $targetKey;
				self::$relations[$tablename][$this->table] = new stdClass();
				self::$relations[$tablename][$this->table]->origKey = $targetKey;
				self::$relations[$tablename][$this->table]->targetKey = $origKey;
				self::$relations[$tablename][$this->table]->type = self::$relations[$this->table][$tablename]->type = NOT_ANALYZED;
			}
			else {
				trigger_error("$targetKey column doesn't exist in $tablename");
			}
			$this->analyzeRelations();
		}
		
		/**
			Adds a many-to-many relation between tables. Takes a crapload of arguments.	
				@param string $connectortable
				@param string $thisid
				@param string $mappedid
				@param string $connectedtable
				@param string $thatid
				@param string $cmappedid
				@todo find a way to reduce arguments
		
		**/
		
		public function addRelationM2M($connectortable,$thisid,$mappedid,$connectedtable,$thatid,$cmappedid) {
			$table = new table($connectedtable);
			$conntable = new table($connectortable);
			if (isset($conntable->$mappedid) && isset($conntable->$cmappedid)) {
					self::$relations[$this->table][$connectedtable] = new stdClass();
					self::$relations[$this->table][$connectedtable]->origKey = $thisid;
					self::$relations[$this->table][$connectedtable]->targetKey = $mappedid;
					self::$relations[$this->table][$connectedtable]->connector = $connectortable;
					self::$relations[$connectedtable][$this->table] = new stdClass();
					self::$relations[$connectedtable][$this->table]->origKey = $thatid;
					self::$relations[$connectedtable][$this->table]->targetKey = $cmappedid;
					self::$relations[$connectedtable][$this->table]->connector = $connectortable;
					self::$relations[$connectedtable][$this->table]->type = self::$relations[$this->table][$connectedtable]->type = MANY;
			}
			else {
					trigger_error("$mappedid column doesn't exist in $connectortable or $cmappedid column doesn't exist in $connectedtable");
			}
		}
		/**
			Analyzes the relation between tables and marks them accordingly. So far only ONE TO MANY and MANY TO MANY ones. Haven't seen a case of single
		
		**/
		private function analyzeRelations() {
			foreach(self::$relations as $key1 => $table) {
				foreach ($table as $key => &$relation) {
					$table1 = new table($key1);
					$table2 = new table($key);
					if ($relation->type==NOT_ANALYZED) {
						if (isset($relation->connector)) {	
							$relation->type = MANY;
						}
						$tK = $relation->targetKey;
						$oK = $relation->origKey;
						if (isset($table2->$tK) && isset($table1->$oK)) {
							$relation->type = ONE_TO_MANY;
						}
						//@TODO single relations
						elseif ($relation->type == NOT_ANALYZED) {
							$info->relationType = NOT_RECOGNIZED;
							trigger_error("Error! Relation between tables not recognized: $key1 and $key");
						}
					}
				}	
			}
		}
		
		/**
			Set all the values of row at once
				@param array $values
		**/
			public function setAll($values) {
				if (isset($values[self::$primaryKey[$this->table]])) {
					$this->originalData = $values;
				}
				else {
					$this->modifiedData = $values;
				}
			}
			
		/**
			Imports an array of rows and returns an array of table objects filled with values
				@param string $table
				@param array $values
				@return array
		**/
			public static function importRows($table,$values) {
				$output = array();
				if (is_array($values)) {
					foreach ($values as $value) {
						$tableObject = new table($table);
						$tableObject->setAll($value);
						$output[] = $tableObject;
					}
				}
				if (!count($output)) {
					return false;
				}
				return count($output)>1?$output:$output[0];
			}
			
		/**
			Static wrapper functions
			
		**/
		
		/**
			Initializes a certain table	
				@param string $table
				@param int $id
				@param array $columns
				@param databaseAdapter $connection
		**/
			public static function set($table, $id=FALSE, $columns = FALSE, $connection = FALSE) {
				return new table($table, $id, $columns, $connection);
			}
		
		/**
			Adds a Many-To-Many relationship statically. For more detailed description, read normal method.
				@param string $connectortable
				@param string $thisid
				@param string $mappedid
				@param string $connectedtable
				@param string $thatid
				@param string $cmappedid
				@todo find a way to reduce arguments
				@todo find a way to add relations between tables with other params
		**/
			public static function addRelationM2MS($table,$connectortable,$thisid,$mappedid,$connectedtable,$thatid,$cmappedid) {
				$table = new table ($table);
				$table->addRelationM2M($connectortable,$thisid,$mappedid,$connectedtable,$thatid,$cmappedid);
			}
			
		/** 
			Adds a relation to another table statically. For more detailed description, read normal method.
				@param string $tablename
				@param string $arg1 
				@param string $arg2
				@todo find a way to add relationships between tables with other params
		**/
		public static function addRelationS($table, $tablename, $arg1, $arg2=FALSE) {
			$table = new table($table);
			$table->addRelation($tablename, $arg1, $arg2);
		}
		
		/**
			Static wrapper for find. Arguments the same.
				@param string $table Table in which to search
				@param array $filters Filters to apply. Can be name=>value pair, SQL statement, or recursive array relating to conditions on other tables
				@param array $ordergroup How to order, group and limit the search
				@param array $columns What columns to return in search
				@return array
		**/
		public static function findS($table, $filters = array(), $ordergroup = array(), $columns = array()) {
			$Table = new table($table);
			return $Table->find($table, $filters, $ordergroup, $columns);
		}
	}
	
	/**
		/class Query
		Helper class for making the more complicated queries.
		
	**/
	
	class Query extends base{
		
		/**
			Constructor. 
				@param string $class
				@param array $filters
				@param array $ordergroup
				@param array $columns
		
		**/
		public function __construct($class, $filters, $ordergroup, $columns) {
			$this->filters = $filters;
			$this->ordergroup = $ordergroup;
			$this->wheres = array();
			$this->joins = array();
			$this->fields = array();
			$this->orders = array();
			$this->groups = array();
			$this->limit = '';
			$tableName = $class;
			$this->class = new table($class);
			if(sizeof($columns) == 0) { // if $columns is not passed, use all fields from $class->databaseInfo->fields
				$fields = table::$tables[$this->class->table];
				foreach($fields as $property) {
					$this->fields[] = $tableName.'.'.$property;
				}
			}
			else { // otherwise, use only the fields from $justthese
				foreach($columns as $property) {
					$this->fields[] = $tableName.'.'.$property;
				}
			}
			if(sizeof($filters) > 0 )
			{	
				foreach($filters as $property=>$value) {
					$filter = $this->buildFilters($property, $value, $this->class);
					if (is_array($filter)) {
						$this->wheres = array_merge($this->wheres, $filter);
					}
					else {
						$this->wheres[] = $filter;
					}
				}
			}
			$this->buildOrderBy();
		}
		
		
		/**
			Processes the filters passed in the constructor and returns appropiate SQL queries
				@param mixed $property
				@param mixed $value
				@param table $class
				@return string
		
		**/
		private function buildFilters($property, $value, $class) {
			//The element was an array => it was a filtering by a related table
			if ((array_key_exists($property,table::$relations[$class->table]) || $property==$class->table) && is_array($value)) {
				$allFilters = array();
				$property = new table($property);
				foreach($value as $key=>$val) {
					if ($property->table!=$class->table)
						$this->buildJoins($property,$class);
					$filter = $this->buildFilters($key, $val, $property);
					if (is_array($filter)) {
						$allFilters = array_merge($filter, $allFilters);
					}
					else {
						$allFilters[] = $filter;
					}
				}	
				return $allFilters;
			}
			//The element was not an array or didn't have a string index
			elseif (is_numeric($property)) {
				return $value;
			}			
			else {
				return "{$class->table}.{$property} = '{$value}'";
			}
		}
		
		/**
			Build the order, group, limit SQL strings for the query
				
		
		**/
		private function buildOrderBy() {
			$hasorderby = false;
			foreach($this->ordergroup as $key=>$extra) {
				if(strpos(strtoupper($extra), 'ORDER BY') !== false) {
					$this->orders[] = str_replace('ORDER BY', "", strtoupper($extra));
					unset($this->ordergroup[$key]);
				}
				if(strpos(strtoupper($extra), 'LIMIT') !== false) {
					$this->limit = $extra;
					unset($this->ordergroup[$key]);
				}
				if(strpos(strtoupper($extra), 'GROUP BY') !== false) { 
					$this->groups[] = str_replace('GROUP BY', "", strtoupper($extra));
					unset($this->ordergroup[$key]);
				}
			}
		}
		
		/**
			Makes the SQL strings for the JOINS necesary in the filtering
				@param table $child
				@param table $parent
		
		**/
		private function buildJoins($child, $parent) {
			switch ($parent::$relations[$parent->table][$child->table]->type) {
				case NOT_ANALYZED:
					$parent->analyzeRelations();
					return ($this->buildJoins($child,$parent));
					break;
				case MANY:
					$this->joins[] = "LEFT JOIN {$parent::$relations[$parent->table][$child->table]->connector} ON {$parent::$relations[$parent->table][$child->table]->connector}.{$parent::$relations[$parent->table][$child->table]->targetKey} = {$parent->table}.{$parent::$relations[$parent->table][$child->table]->origKey} ";
					$this->joins[] = "LEFT JOIN {$child->table} ON {$parent::$relations[$parent->table][$child->table]->connector}.{$parent::$relations[$child->table][$parent->table]->targetKey} = {$child->table}.{$parent::$relations[$child->table][$parent->table]->origKey} ";
					break;
				case ONE_TO_MANY:
					$this->joins[] = "LEFT JOIN {$child->table} ON {$child->table}.{$parent::$relations[$parent->table][$child->table]->targetKey} = {$parent->table}.{$parent::$relations[$parent->table][$child->table]->origKey} ";
					break;
				default:
					trigger_error("Incorrect relation between {$parent->table} and {$child->table}");	
			
			}
			$this->joins = array_unique($this->joins);
		}
		
		/**
			Simply joins and returns all the stuff done in the previous functions	
				@return string with SQL statement
		
		**/
		public function buildQuery() {
			$where = (sizeof($this->wheres) > 0) ? ' WHERE '.implode(" \n AND \n\t", $this->wheres) : '';
			$order = (sizeof($this->orders) > 0) ? ' ORDER BY '.implode(", ", $this->orders) : '' ;
			$group = (sizeof($this->groups) > 0) ? ' GROUP BY '.implode(", ", $this->groups) : '' ;
			$query = 'SELECT '.implode(", \n\t", $this->fields)."\n FROM \n\t".$this->class->table."\n ".implode("\n ", $this->joins).$where.' '.$group.' '.$order.' '.$this->limit;
			return($query);
		}
		
		/**
			Get's a count for how many rows would a query return ?
				@return array;
		***/
		function getCount() {
			$where = (sizeof($this->wheres) > 0) ? ' WHERE '.implode(" \n AND \n\t", $this->wheres) : '';
			$order = (sizeof($this->orders) > 0) ? ' ORDER BY '.implode(", ", $this->orders) : '' ;
			$group = (sizeof($this->groups) > 0) ? ' GROUP BY '.implode(", ", $this->groups) : '' ;
			$query = "SELECT count(*) FROM \n\t".$this->class->table."\n ".implode("\n ", $this->joins).$where.' '.$group.' '.$order.' ';

			return self::$global['dbCon']->fetchRow($query);

		}
	
	}
?>