<?php

class acl extends plugin {
	
	private $acl;
	private $source;
	private $sourceType;
	private $requester;
	private $prefix = NULL;
	
	/**
	 *  Constructor for ACL lists. Initializes internal list. First parameter gives the requester that will be used for ACL. 
	 * 	The second parameter is either the prefix for the tables, if database based ACL's are used or tt can be an array, passed like
	 * 	this: <code>array(
	 *		'requesterss'=>array('users'=>array('test','rolisz'),'mods'=>array('bad_mod'),'admins'),
	 *		'resources'=>array('posts','stats','comments','users'),
	 *		'actions'=>array('view','edit','delete','ban'),
	 *		'permissions'=>array(array('users','posts','view'),
						   array('users','comments','view'),
						   array('users','comments','add')
						   ))
	 * </code>. It can be a database table, in which case
	 * 	the plugin will use the default database connection through the table class. Or it can be an XML file. Second parameter is optional,
	 *  defaults to 'db'. It must be 'xml' if you use an XML file, or a databaseAdapter object if you want to get the ACL table 
	 * 	from a different database. 
	 * 		@param string $requester 
	 * 		@param $prefix  - array
	 * 						- string
	 * 		@param $sourceType
	 */
	public function __construct($requester, $prefix = '', $sourceType = 'db') {
		$this->requester = $requester;		
		if (is_array($prefix)) {
			if (empty($prefix)) {
				trigger_error('The array for the ACL is empty');
			}
			$this->acl = $prefix;
			$this->sourceType = 'array';
			if (!$this->checkConsistency()) {
				trigger_error('The ACL list is inconsistent or faulty');
			}
			$currentUserGroups = $this->getPath($this->acl['requesters']);
			foreach ($this->acl['permissions'] as $key=> $value) {
				if (is_numeric($key)) {
					if (in_array($value[0],$currentUserGroups)) {
						if (!isset($value[2])) {
							$value[2]=NULL;
						}
						$this->acl['permissions'][$value[1]][] = $value[2];
					}
					unset ($this->acl['permissions'][$key]);
				}
			}
		}
		elseif ($sourceType instanceof databaseAdapter) {
			$this->prefix = $prefix;
			$perms = rolisz::table($prefix.'_permissions')
				->addRelation($prefix.'_actions','action','id')
				->addRelation($prefix.'_resources','resource','id')
				->addRelation($prefix.'_requesters','requester','id');
			/*$tree = rolisz::get('dbCon')->fetchAll("SELECT {$prefix}_requesters.id, {$prefix}_requesters.requester FROM `{$prefix}_requesters` WHERE 
							`left` <= (SELECT {$prefix}_requesters.left FROM {$prefix}_requesters WHERE {$prefix}_requesters.requester='{$requester}') AND 
							`right` >= (SELECT {$prefix}_requesters.right FROM {$prefix}_requesters WHERE {$prefix}_requesters.requester='{$requester}') ORDER BY `left`");*/
			
			
			$perms = rolisz::get('dbCon')->fetchAll("SELECT `{$prefix}_actions`.`action`, `{$prefix}_resources`.`resource` FROM `{$prefix}_permissions` 
											LEFT JOIN `{$prefix}_actions` ON `{$prefix}_actions`.id = `{$prefix}_permissions`.action 
											LEFT JOIN `{$prefix}_resources` ON `{$prefix}_resources`.id = `{$prefix}_permissions`.resource 
											WHERE requester =  ANY (SELECT `{$prefix}_requesters`.id FROM `{$prefix}_requesters` WHERE 
												`left` <= (SELECT `{$prefix}_requesters`.left FROM {$prefix}_requesters WHERE _requesters.requester='{$requester}') AND 
												`right` >= (SELECT `{$prefix}_requesters`.right FROM {$prefix}_requesters WHERE _requesters.requester='{$requester}') ORDER BY `left`)");
			$this->acl = array('permissions'=>$perms);
			foreach ($this->acl['permissions'] as $key => &$value) {
				if (is_numeric($key)) {
					$this->acl['permissions'][$value['resource']][] = $value['action'];
					unset ($this->acl['permissions'][$key]);
				} 
			}
		}
		elseif($sourceType == 'xml') {
			//@todo implement
			//In this case it's the xml file
			$this->prefix = $prefix;
			echo 'Not implemented yet';
		}
	}
	
	/**
	 *  This checks for the consistency of the ACL list. 
	 * 		@retval true
	 * 		@retval false
	 */
	private function checkConsistency() {
		if (!isset($this->acl['requesters']) || !isset($this->acl['resources']) || !isset($this->acl['permissions'])) {
			return false;
		}
		//@todo implement better check
		return true;
	}
	
	/**
	 *  Recursively search for requester in acl list
	 * 		@param array $array
	 */
	 private function getPath($array) {

	 	if (is_array($array) && in_array($this->requester,$array)) {
	 		return array($this->requester);
	 	}
	 	if (is_array($array))
		 	foreach ($array as $key=>&$value) {
		 		$ceva = $this->getPath($value);
		 		if ($ceva) {
		 			$ceva[]= $key;
		 			return $ceva;
		 		}
				unset($array[$key]);
		 	}
		return false;
	 }
	 
	 /**
	  *  Executes the plugin at an execution point.
	  * 	@param string $url - here because of compatibility with other plugins that might be executed at afterMatch
	  * 	@param mixed $funcs
	  */
	public function run($url, $funcs) {
		if (is_string($funcs)) {
			$funcs = explode('|',$funcs);
			foreach ($funcs as &$func) {
				if (substr_count($func,'.php')!=0) {
					$func = substr(strstr($func,'.php'),5);
				} 
			}
		}
		if (is_array($funcs)) {
			foreach ($funcs as &$func) {
				if (is_string($func) && substr_count($func,'.php')!=0) {
					continue;
				}
				if ($this->isAllowed($func)) {
					echo 'yay';	
				}
				else {
					echo 'nay';
					unset($func);
				}
			}
		}
	}
	
	/**
	 *  Verifies if requester has clearance for this. 
	 * 		@param function $function
	 * 		@retval true
	 * 		@retval false
	 */
	public function isAllowed($function) {
		if ((!is_array($function) && key_exists($function,$this->acl['permissions'])) ||  // case if only a function is given, so check only resource 
			(is_array($function) && isset($this->acl['permissions'][$function[0]]) && $this->acl['permissions'][$function[0]]=$function[1])) { //case when object and function given, so check resource and action				
			return true;
		}
		else {
			return false;	
		}
	}
	
	/**
	 *  This creates the necessary tables for ACL. It creates a few default elements for everything. You may specify a prefix for 
	 * 	the tables. You may optionally pass it a database connection. Else it will use the framework connection to the database.
	 * 		@param string $prefix defaults to empty string ''
	 * 		@param databaseAdapter $dbCon 
	 */
	public function init($prefix = '', $dbCon = FALSE) {
		if (!$dbCon) {
			$dbCon = self::$globals['dbCon'];
		}
		//This table contains the requester types and their groups stored as Modified Preorder Tree Traversal 
		$dbCon->createTable($prefix.'_requesters',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),
														'requester'=>array('TEXT','NOT NULL'),
														'left'=>array('INT','NOT NULL'),
														'right'=>array('INT','NOT NULL')));
		//This table contains objects upon which requesters can act
		$dbCon->createTable($prefix.'_resources',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),
											 		   'resource'=>array('TEXT','NOT NULL')));
		//This table contains actions with which requesters can act upon resources. They are optional
		$dbCon->createTable($prefix.'_actions',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),
											 		 'action'=>array('TEXT','NOT NULL')));
		//This table contains the actual permissions
		$dbCon->createTable($prefix.'_permissions',array('id'=>array('INT','NOT NULL','AUTO_INCREMENT','PRIMARY KEY'),
														 'requester'=>array('INT','NOT NULL'),
														  'resource'=>array('INT','NOT NULL'),
														  'action'=>array('INT','NOT NULL')));
		include_once('../db.php');
		$req = new table($prefix.'_requesters');
		$req->requester = 'users';
		$req->left = '1';
		$req->right = '2';
		$req->save();
		$req = new table($prefix.'_resources');
		$req->resource = 'posts';
		$req->save();
		$req = new table($prefix.'_actions');
		$req->action = 'view';
		$req->save();
		$req = new table($prefix.'_permissions');
		$req->requester = 1;
		$req->resource = 1;
		$req->action = 1;
		$req->save();
	}

	/**
	 *  Adds a new type of resource, action, requester or permission. Works only with database and XML based ACLs.
	 * 		@param string $type
	 * 		@param mixed $what
	 * 		@param string $where
	 * 		@retval false
	 */
	public function addNew($type, $what, $where = FALSE) {
		if (is_null($this->prefix)) 
			return false;
		if ($type = 'resource') {
			
		}
	}
}
?>