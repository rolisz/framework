<?php

/**
 *  \class acl
 * 	This plugin allows you to easily work with Access Control Lists. It integrates with the router class, automatically checking 
 * 	if the current requester has permission to run the functions associated with the URL. 
 *		@author Roland Szabo
 * 		@package rolisz 
 */
class acl extends plugin {
	
	private $acl;
	private $source;
	private $sourceType;
	private $requester;
	private $prefix = NULL;
	private $denyFunction;
	
	/**
	 *  Constructor for ACL lists. Initializes internal list. First parameter gives the requester that will be used for ACL. 
	 * The second parameter indicates a function that will be called instead of those that the requester can't acces.
	 * 	The third parameter is either the prefix for the tables, if database based ACL's are used or tt can be an array, passed like
	 * 	this: <code>array(
	 *		'requesterss'=>array('users'=>array('test','rolisz'),'mods'=>array('bad_mod'),'admins'),
	 *		'resources'=>array('posts','stats','comments','users'),
	 *		'actions'=>array('view','edit','delete','ban'),
	 *		'permissions'=>array(array('users','posts','view'),
						   array('users','comments','view'),
						   array('users','comments','add')
						   ))
	 * </code>. It can be a database table, in which case
	 * 	the plugin will use the default database connection through the table class. Or it can be an XML file. Fourth parameter is optional,
	 *  defaults to 'db'. It must be 'xml' if you use an XML file, or a databaseAdapter object if you want to get the ACL table 
	 * 	from a different database. 
	 * 		@param string $requester 
	 * 		@param function $deny
	 * 		@param array|string $prefix  
	 * 		@param $sourceType
	 */
	public function __construct($requester, $prefix = '', $deny = 'default',$sourceType = 'db') {
		$this->requester = $requester;	
		if ($deny!='default') {
			$this->denyFunction = $deny;
		}
		else {
			$this->denyFunction  =  function () {
				echo 'You can\'t touch this. Hammertime';
			};
		}	
		
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
			if ($currentUserGroups == FALSE) {
				reset($this->acl['requesters']);
				$currentUserGroups = is_numeric(key($this->acl['requesters']))?$this->acl['requesters']:key($this->acl['requesters']); 
			}
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
			$perms = rolisz::get('dbCon')->fetchAll("SELECT `{$prefix}_actions`.`action`, `{$prefix}_resources`.`resource` FROM `{$prefix}_permissions` 
											LEFT JOIN `{$prefix}_actions` ON `{$prefix}_actions`.id = `{$prefix}_permissions`.action 
											LEFT JOIN `{$prefix}_resources` ON `{$prefix}_resources`.id = `{$prefix}_permissions`.resource 
											WHERE requester =  ANY (SELECT `{$prefix}_requesters`.id FROM `{$prefix}_requesters` WHERE 
												`left` <= (SELECT `{$prefix}_requesters`.left FROM {$prefix}_requesters WHERE {$prefix}_requesters.requester='{$requester}') AND 
												`right` >= (SELECT `{$prefix}_requesters`.right FROM {$prefix}_requesters WHERE {$prefix}_requesters.requester='{$requester}') ORDER BY `left`) 
												OR requester = (SELECT `{$prefix}_requesters`.id FROM `{$prefix}_requesters` WHERE `left`=1)");
			$this->acl = array('permissions'=>$perms);
			if (is_array($this->acl['permissions'])) {
				foreach ($this->acl['permissions'] as $key => &$value) {
					if (is_numeric($key)) {
						if ($value['resource'] == '') {
							unset($this->acl['permissions'][$key]);
						}
						else { 
						$this->acl['permissions'][$value['resource']][] = $value['action'];
						unset ($this->acl['permissions'][$key]);
						}
					} 
				}
			}
			else {
				$this->acl['permissions'] = array();
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
	 *  Get name
	 * 		@return string 
	 */
	public function getName() {
		return 'ACL';
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
	public function run() {
		$funcs = &self::$global['currentRoute'];
		if (is_string($funcs)) {
			$funcs = explode('|',$funcs);
		}
		if (is_array($funcs)) {
			foreach ($funcs as $key => $func) {
				if (is_string($func) && substr_count($func,'.php')!=0) {
					continue;
				}
				if ($this->isAllowed($func)) {

				}
				else {
					$funcs[$key] = $this->denyFunction;
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
		$this->prefix = $prefix;
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
	 * 		@param string $type It can be 'resource', 'action', 'requester' or 'permission'
	 * 		@param mixed $what For 'resource', 'action' and 'requester' it should be a string containing the name of the new 
	 * 	element. For 'permission' it should be an associative array containing key-value pairs for each of the types. If no action
	 * 	has to be defined then it action should be NULL. 
	 * 		@param string $where Used only for 'requester', it should the name of the node below which to insert. If it is not found,
	 * 	the new value is inserted as a new group.
	 * 		@retval false It returns false if you are trying to use it without a database or XML file.
	 * 		@retval true
	 */
	public function addNew($type, $what, $where = FALSE) {
		if (is_null($this->prefix)) 
			return false;
		//Check to make sure it doesn't already exist. Permissions are not checked
		if ($type == 'resource' || $type == 'action' || $type == 'requester') {
			$search = table::findS($this->prefix."_{$type}s",array($type=>$what));
			if ($search){
				trigger_error("$what $type already exists");
				return false;
			} 
		}
		if ($type == 'resource' || $type == 'action') {
			$req = new table($this->prefix."_{$type}s");
			$req->$type = $what;
			$req->save();
		}
		if ($type == 'requester') {
			$req = new table($this->prefix.'_requesters');
			//Treat cases where $where is another requester, another id, or it is not defined/found
			if (is_numeric($where)) {
				$orig = new table($this->prefix.'_requesters',$where);
			}
			else {
				$orig = $req->find($this->prefix.'_requesters',array('requester'=>$where));
			}
			if ($orig) {
				$left = $orig->left;
			}
			else {
				$left = rolisz::get('dbCon')->fetchFirst("SELECT MAX(`left`) FROM `{$this->prefix}_requesters`");
			}
			if ($left == NULL) {
				$left = 0;
			}
			rolisz::get('dbCon')->query("UPDATE `{$this->prefix}_requesters` SET `left` = `left` +2 WHERE `left`>{$left}");
			rolisz::get('dbCon')->query("UPDATE `{$this->prefix}_requesters` SET `right` = `right` +2 WHERE `right`>{$left}");
			$req->requester = $what;
			$req->left = $left+1;
			$req->right = $left+2;
			$req->save();
		}
		if ($type == 'permission') {
			if (!isset($what['action'])) {
				$what['action'] = NULL;
			}
			//Loop through each type and if it's a string, get the associated id
			foreach (array('action','resource','requester') as $type) {
				if (is_string($what[$type])) {
					$what[$type] = rolisz::table($this->prefix."_{$type}s")->find($this->prefix."_{$type}s",array($type=>$what[$type]));
					if (!$what[$type]) {
						trigger_error("{$what[$type]} {$type} not found");
						return false;
					}
					$what[$type] = $what[$type]->id;
				}
			}
			$perm = new table($this->prefix.'_permissions');
			$perm->requester = $what['requester'];
			$perm->resource = $what['resource'];
			$perm->action = $what['action'];
			$perm->save();
		}
		return true;
	}

	/**
	 *  Edit the ACL. Parameters the same as for addNew(), except an extra parameter.
	 * 		@param string $type It can be 'resource', 'action', 'requester' or 'permission'
	 * 		@param mixed $what For 'resource', 'action' and 'requester' it should be a string containing the name of the new 
	 * 	element. For 'permission' it should be an associative array containing key-value pairs for each of the types. If no action
	 * 	has to be defined then it action should be NULL. 
	 * 		@param string $towhat What to change this to
	 * 		@param string $where Used only for 'requester', it should the name of the node after which to insert. If it is not found,
	 * 	the new value is inserted as a new group.
	 * 		@retval false It returns false if you are trying to use it without a database or XML file or if an element can't be found.
	 * 		@retval true
	 * 
	 */
	public function editACL($type, $what, $towhat, $where = FALSE) {
		if (is_null($this->prefix)) 
			return false;
		if (is_string($what)) {
			$req = table::findS($this->prefix."_{$type}s",array($type=>$what));
			if (!$req) {
				trigger_error("Can't find {$what} of {$type}");
				return false;
			}
			$what = $req->id;
		}
		if ($type == 'resource' || $type == 'action') {
			$req = new table($this->prefix."_{$type}s",$what);
			$req->$type = $towhat;
			$req->save();
		}
		if ($type == 'requester') {
			$req = new table($this->prefix.'_requesters',$what);
			if (!$req) {
				trigger_error("Can't find {$what} {$type} to edit");
				return false;
			}
			//Treat cases where $where is another requester, another id, or it is not defined/found
			if ($where) {
				if (is_numeric($where)) {
					$orig = new table($this->prefix.'_requesters',$where);
				}
				else  {
					$orig = $req->find($this->prefix.'_requesters',array('requester'=>$where));
				}
				if ($orig) {
					$right = $orig->right;
				}
				else {
					trigger_error("Can't find {$where} to put there the {$what} {$type}");
					return false;
				}
				rolisz::get('dbCon')->query("UPDATE `{$this->prefix}_requesters` SET 
								left = left-2 WHERE left>{$req->right} AND left<{$right}+3");
				rolisz::get('dbCon')->query("UPDATE `{$this->prefix}_requesters` SET 
								right = right-2 WHERE right>{$req->right} AND right<{$right}+3");
				$req->left = $right+1;
				$req->right = $right+2;
			}
			$req->requester = $towhat;		
			$req->save();
		}
		if ($type == 'permission') {
			//Loop through each type and if it's a string, get the associated id
			foreach ($towhat as $type=>$value) {
				if (is_string($value)) {
					$towhat[$type] = rolisz::table($this->prefix."_{$type}s")->find($this->prefix."_{$type}s",array($type=>$value));
					if (!$towhat[$type]) {
						trigger_error("{$value} {$type} not found");
						return false;
					}
					$towhat[$type] = $towhat[$type]->id;
				}
			}
			$perm = new table($this->prefix.'_permissions',$what);
			foreach ($towhat as $type=>$value) {
				$perm->$type=$value;
			}
			$perm->save();
		}
		return true;
	}


	/**
	 *  Delete something from the ACL. Parameters the same as for addNew(), except missing the last parameter
	 * 		@param string $type It can be 'resource', 'action', 'requester' or 'permission'
	 * 		@param mixed $what For 'resource', 'action' and 'requester' it should be a string containing the name of the new 
	 * 	element. For 'permission' it should be an associative array containing key-value pairs for each of the types. If no action
	 * 	has to be defined then it action should be NULL. 
	 * 		@retval false It returns false if you are trying to use it without a database or XML file or if an element can't be found.
	 * 		@retval true
	 * 
	 */
	public function deleteACL($type, $what) {
		if (is_null($this->prefix)) 
			return false;
		if (is_string($what)) {
			$req = table::findS($this->prefix."_{$type}s",array($type=>$what));
			if (!$req) {
				trigger_error("Can't find {$what} of {$type}");
				return false;
			}
			$what = $req->id;
		}
		$element = rolisz::table($this->prefix."_{$type}s",$what);
		$element->delete();
		if ($type!='permissions') {
			$req = table::findS($this->prefix.'_permissions',array($type=>$what));
			if (!is_array($req)) {
				$req = array($req);
			}
			$size = sizeOf(req);
			for ($i=0; $i<$size; $i++) {
				$req[$i]->delete();
			}
		}
	}
		
	/**
	 *  Retrieves all requesters, resources, actions or permissions from the database or XML file. 
	 * 		@param string $type
	 * 		@return array
	 */
	 public function getACL($type) {
	 	if (is_null($this->prefix)) 
			return false;
		if ($type == 'permission') {
			return rolisz::get('dbCon')->fetchAll("SELECT act.action, req.requester, res.resource, perm.id FROM {$this->prefix}_permissions AS perm 
						LEFT JOIN {$this->prefix}_actions AS act ON perm.action=act.id 
						LEFT JOIN {$this->prefix}_requesters AS req ON perm.requester=req.id 
						LEFT JOIN {$this->prefix}_resources AS res ON perm.resource=res.id");	
		}
		$acl =  table::findS($this->prefix."_{$type}s");
		if (!is_array($acl)) {
			$acl = array($acl);
		}
		foreach ($acl as &$element) {
			$element = $element->getData();
		}
		return $acl;
	 }
}
?>