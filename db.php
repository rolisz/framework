<?php


define('SINGLE', 'RELATION_SINGLE');
define('FOREIGN', 'RELATION_FOREIGN');
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
	class table {
		
		private $connection;
		//Stativ variable containing the columns of all tables that have been instantiated
		//@TODO allow objects representing same table, but different columns
		static private $tables = array();
		//Contains only the table name of this object
		private $table;
		private $originalData = array();
		private $modifiedData = array();
		static private $primaryKey = array();
		static private $relations = array();

		public function __construct($connection, $table, $id=FALSE, $columns = FALSE) {
			$this->connection = $connection;
			$this->table = $table;
			if ($columns && isarray($columns)) {
				self::$tables[$this->table] = $columns;
			}
			elseif (!isset(self::$tables[$this->table])) {
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
			if (!isset(self::$tables[$this->table[$name]])) {
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
			if (!isset(self::$tables[$this->table[$name]])) {
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
			if ($this->hydrater) {
				$query.=' WHERE '.self::$primaryKey[$this->table].'=\''.$this->originalData[self::$primaryKey[$this->table]]."'";
			}
			$this->connection->query($query);
			if (empty($this->originalData)) {
				return $this->connection->getInsertID();
			}
		}
		
		/**
			Delete the corresponding record from the database
		
		**/
		public function delete() {
			if ($this->hydrater) {
				$query = $this->connection->query("DELETE FROM {$this->table} WHERE ".self::$primaryKey[$this->table]."='{$this->originalData[self::$primaryKey[$this->table]]}'");
				$query = $this->connection->query("ALTER TABLE $this->table AUTO_INCREMENT = 1");
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
		
		public function find($class) {
		
		}
		/** 
			Adds a relation to another table and then does some magic to figure out what kind of a relationship is it
				@param string $tabelname
				@param string $connectortabelname
		**/
		public function addRelation($tabelname, $connectortabelname = FALSE) {
			var_dump($tabelname);
			var_dump($connectortabelname);
			if (is_array($tabelname)) {
				self::$relations[$this->tabel][key($tabelname)] = new stdClass();
				self::$relations[$this->tabel][key($tabelname)]->origKey = self::$primaryKey[$this->table];
				self::$relations[$this->tabel][key($tabelname)]->targetKey = current($tabelname);
			}
			if (is_array($connectortabelname)) {
				echo $this->tabel.$tabelname;
				self::$relations[$this->tabel][$tabelname] = new stdClass();
				//self::$relations[$this->tabel][$tabelname]->origKey = self::$primaryKey[$this->table];
				//self::$relations[$this->tabel][$tabelname]->targetKey = current($connectortabelname);
				//self::$relations[$this->tabel][$tabelname]->connector = key($connectortabelname);
			}
			/*if(is_subclass_of($classname, 'dbObject')) {// the class to connect is a dbObject
				$obj = new $classname(false);
				$info->className = $classname;
				if($info->relationType == RELATION_NOT_ANALYZED)
				{
					if(array_key_exists('connectorClass', get_object_vars($info)) && $info->connectorClass != '' && is_subclass_of($info->connectorClass, 'dbObject')) { // this class has a connector class. It should be a many:many relation
						$connector = $info->connectorClass;
						$connectorobj = new $connector(false);
						if(array_key_exists($this->databaseInfo->primary, $connectorobj->databaseInfo->fields) && array_key_exists($obj->databaseInfo->primary, $connectorobj->databaseInfo->fields)) {
							$info->relationType = RELATION_MANY; // yes! The primary key of the relation now appears in this object, the connector class and one of the connected class. it's a many:many relation
							continue;
						} 
						else { 
							unset($info->connectorClass); // it's not connected to our relations
						}
					}
					if(	array_key_exists($obj->databaseInfo->primary, $this->databaseInfo->fields) && array_key_exists($this->databaseInfo->primary, $obj->databaseInfo->fields)) {
						$info->relationType = RELATION_SINGLE; // if the primary key of the connected object exists in this object and the primary key of this object exists in the connected object it's a 1:1 relation
					}
					elseif((array_key_exists($this->databaseInfo->primary, $obj->databaseInfo->fields) && !array_key_exists($obj->databaseInfo->primary, $this->databaseInfo->fields) || !array_key_exists($this->databaseInfo->primary, $obj->databaseInfo->fields) && array_key_exists($obj->databaseInfo->primary, $this->databaseInfo->fields)) ) {
							$info->relationType = RELATION_FOREIGN;	// if the primary key of the connected object exists in this object (or the other way around), but the primary key of this object does not exist in the connected object (or the other way around) it's a many:1 or 1:many relation		
					}
					elseif($info->relationType == RELATION_NOT_ANALYZED) {
						$info->relationType = RELATION_NOT_RECOGNIZED;  // we don't recognize this type of relation.
						Logger::Trace("Warning! Relation not recognized! {$classname} connecting to ".get_class($this)); 
					}
					$this->relations[$classname] = $info;
				}
			}
			else
			{
				Logger::Trace ("{$classname} is not a dbObject!");
				unset($this->relations[$classname]); // tried to connect a non-dbobject object.
			}*/
		}
	}
?>