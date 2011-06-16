<?php 
include_once ('base.php');
/** 
 *	\class rolisz
 *	Central class, contains only the most common functions or wrappers for the other classes
 * 		@package rolisz
 *		@author Roland Szabo
**/
class rolisz extends base {


	/**
	 *	Calls functions and includes files and passes them $args as argument
	 *		@param mixed $funcs 
	 *		@param array $args 
	 *		@public
	**/
	public static function call($funcs,$args) {
		if (!is_array($args)) {
			$args=array($args);
		}
		if (is_string($funcs)) {
			// Call each code segment
			foreach (explode('|',$funcs) as $func) {
				if (substr_count($func,'.php')!=0) {
					// Run external PHP script
					$file = strstr($func,'.php',TRUE).'.php';
					$functions = substr(strstr($func,'.php'),5);
					
					include $file;
					$functions = explode(':',$functions);
					if ($functions[0]!='') {
						foreach ($functions as $function) {
							if (!is_callable($function)) {
								// Invalid route handler
								throw new Exception($function.' is not a valid function');
								return;
							}
							switch(count($args)) { 
				        case 0: $function(); break; 
				        case 1: $function($args[0]); break; 
				        case 2: $function($args[0], $args[1]); break; 
				        case 3: $function($args[0], $args[1], $args[2]); break; 
				        case 4: $function($args[0], $args[1], $args[2], $args[3]); break; 
				        case 5: $function($args[0], $args[1], $args[2], $args[3], $args[4]); break; 
				        default: call_user_func_array(array($class, $method), $args);  break; 
				    } 
						}
					}
				} 
				else {
					// Call lambda function
					switch(count($args)) { 
				        case 0: $func(); break; 
				        case 1: $func($args[0]); break; 
				        case 2: $func($args[0], $args[1]); break; 
				        case 3: $func($args[0], $args[1], $args[2]); break; 
				        case 4: $func($args[0], $args[1], $args[2], $args[3]); break; 
				        case 5: $func($args[0], $args[1], $args[2], $args[3], $args[4]); break; 
				        default: call_user_func_array(array($class, $method), $args);  break; 
				    } 
				}
			}
		}
		elseif (is_array($funcs)) {
			foreach ($funcs as $func) {
				if (is_string($func) && substr_count($func,'.php')!=0) {
					// Run external PHP script
					include $func;
				} 
				else {
					switch(count($args)) { 
				        case 0: $func(); break; 
				        case 1: $func($args[0]); break; 
				        case 2: $func($args[0], $args[1]); break; 
				        case 3: $func($args[0], $args[1], $args[2]); break; 
				        case 4: $func($args[0], $args[1], $args[2], $args[3]); break; 
				        case 5: $func($args[0], $args[1], $args[2], $args[3], $args[4]); break; 
				        default: call_user_func_array(array($class, $method), $args);  break; 
				    } 
				}
			}
		}
		else 
			// Call lambda function
			switch(count($args)) { 
				        case 0: $funcs(); break; 
				        case 1: $funcs($args[0]); break; 
				        case 2: $funcs($args[0], $args[1]); break; 
				        case 3: $funcs($args[0], $args[1], $args[2]); break; 
				        case 4: $funcs($args[0], $args[1], $args[2], $args[3]); break; 
				        case 5: $funcs($args[0], $args[1], $args[2], $args[3], $args[4]); break; 
				        default: call_user_func_array(array($class, $method), $args);  break; 
		 } 

	}
	
	/**
	 *	Connects to a database adapter that implements the interface defined in databaseAdapter.php
	 * 	Checks for the existence of the class in databaseAdapter.php and then in a file called $dbtype.DatabaseAdapter.php
	 *		@param string $dbtype Database type. For now there is support for MySQL 
	 *		@param string $host 
	 *		@param string $username
	 *		@param string $password
	 *		@param string $db
	 *		@return databaseAdapter
	**/
	public static function connect($dbtype,$host, $username, $password, $db) {
		include_once ('databaseAdapter.php');
		$adapterClass = "{$dbtype}Database";
		if (!class_exists($adapterClass)) { 
			if (file_exists($adapterClass.'Adapter.php')) {
				include ($adapterClass.'Adapter.php');
			}
			else {
				throw new Exception("$adapterClass class was not found");
				return false;
			} 
		}
		self::$global['dbCon'] = new $adapterClass($host, $username, $password, $db);
		return self::$global['dbCon'];
	}
	
	/**
	 *	Autoloader function. Lazy-loads classes from files first from the framework directory, then the framework plugin directory,
	 * 	then the current one and finally a plugins folder situated in the current folder. Files must be named the same the classes
	 *		@param string $class
	**/
	 public static function autoload( $class ) {
        $file = dirname(__FILE__) . '/' . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        if ( file_exists($file) ) {
            require $file;
			return true;
        }
		$file = dirname(__FILE__) . '/plugins/' . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
		if ( file_exists($file) ) {
           	require $file;
			return true;
       	}
		$file = './'.str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
		if (file_exists($file)) {
			require $file;
			return true;
		}
		$file = './plugins/'.str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
		if (file_exists($file)) {
			require $file;
			return true;
		}
	}

	
	/**
	 *	Trigger an HTTP 404 error. If the 404 framework variable has been set to a function,
	 * it will get called. Else, it just triggers an error saying the page was not found.
	**/
	public static function http404() {
		header("HTTP/1.0 404 Not Found");
		if (self::check('404')) {
			self::call(self::get('404'));
			die();
		}
		else {
			trigger_error('Page not found');
			die();
		}
	}
	
	/**
	 *	Convert Windows double-backslashes to slashes
	 *		@param string $str 
	 *		@return string
	**/
	public static function fixSlashes($str) {
		return $str?strtr($str,'\\','/'):$str;
	}
	
	/**
	 *	Fix mangled braces
	 *		@param string $str 
	 *		@return string
	 * 		@public
	**/
	public static function fixBraces($str) {
		// Fix mangled braces
		return strtr(
			$str,array('%7B'=>'{','%7D'=>'}','%5B'=>'[','%5D'=>']','%20'=>' ')
		);
	}
	
	/**
	 * Plugin functions
	 */
	
	/**
	 *  Return an instance of pluginStructure
	 * 		@return pluginStructure
	 */ 
	public static function plugin () {
		include_once 'pluginStructure.php';
		return new pluginStructure();
	} 
	
	/**
	 *  Runs plugins associated with $name execution point. Additional parameters will be passed to plugin as parameters
	 * 		@param string $name
	 */
	public static function runPlugins($name) {
		if (class_exists('pluginStructure',FALSE)) {
			call_user_func_array(array('pluginStructure','runPlugins'),func_get_args());
		}
	}
	
	/**
	 * Wrappers for the router class
	 */
	 
	 /**
	  * @see router::route
	  */
	 public static function route($pattern, $funcs, $http = 'GET', $name = FALSE) {
	 	if (!class_exists('router',FALSE))	{
	 		include_once ('router.php');
	 	}
	 	router::route($pattern, $funcs, $http, $name);
	 }
	 
	 /**
	  * @see router::run
	  */
	 public static function run() {
		if (!class_exists('router',FALSE))	{
	 		include_once ('router.php');
	 	}
	 	router::run();
	 }
	 
	 /**
	  * @see router::urlFor
	  */
	  public static function urlFor($name, $params = array()) {
	  	return router::urlFor($name,$params);
	  }
	  
	/**
	 * Wrappers for tables
	 */
	 
	/**
	 *  Returns a new instance of table class. For parameters @see table
	 * 		@return table
	 */
	 public static function table($table, $id=FALSE, $columns = FALSE, $connection = FALSE) {
	 	if (!class_exists('table',FALSE))	{
	 		include_once ('table.php');
	 	}
		return new table($table, $id, $columns, $connection);
	 }
	 
	/**
	 *	Sets up autoload, initializes some constants and starts session. 
	 *		@public
	**/
	public static function start() {
		//	Use the rolisz autoload function
		spl_autoload_register(array('rolisz', 'autoload'));
		
		//dunno
		$root = rolisz::fixSlashes(realpath('.')).'/';
		
		//Set a few site dependent framework variables		
		self::$global = array(
			'BASE'=>preg_replace('/(.*)\/.+/','$1',$_SERVER['SCRIPT_NAME']),
			'ENCODING'=>'UTF-8',
			'ERROR'=>NULL,
			'QUIET'=>FALSE,
			'RELEASE'=>FALSE,
			'ROOT'=>$root,
			'SITEMAP'=>array(),
			'TIME'=>time(),
			'THROTTLE'=>0,
			'VERSION'=>self::AppName.' '.self::Version,
			'AJAX'=>(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))
		);
		
		self::$executionPoints = array( 
			'beforeMatch' => array(),
			'afterMatch' => array(),
			'hydrateTable' =>array(),
			'saveTable' =>array(),
			'deleteTable' =>array(),
			'compileTemplate' =>array()
		);
		session_start();
	}
}

// Initialize the framework
rolisz::start();