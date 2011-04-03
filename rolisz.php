<?php 
include_once ('base.php');
/** 
 *	\class rolisz
 *	Central class, contains only stuff that connects the rest
 *		@package rolisz
 *		@author Roland Szabo
**/
class rolisz extends base {

	/**
	 *	Provide sandbox for functions and import files to prevent direct
	 *	access to framework internals and other scripts
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
					foreach ($functions as $function) {
						if (!is_callable($function)) {
							// Invalid route handler
							trigger_error($function.' is not a valid function');
							return;
						}
						call_user_func_array($function,$args);
					}
				} 
				else {
					// Call lambda function
					call_user_func_array($func,$args);
				}
			}
		}
		else
			// Call lambda function
			call_user_func_array($funcs,$args);

	}
	
	/**
	 *	Connects to a database adapter that implements the interface defined in databaseAdapter.php
	 *		@param string $dbtype Database type. For now there is support for MySQL 
	 *		@param string $host 
	 *		@param string $username
	 *		@param string $password
	 *		@param string $db
	 *		@return databaseAdapter
	**/
	public static function connect($dbtype,$host, $username, $password, $db) {
		include ('databaseAdapter.php');
		$adapterClass = "{$dbtype}Database";
		self::$global['dbCon'] = new $adapterClass($host, $username, $password, $db);
		return self::$global['dbCon'];
	}
	
	/**
	 *	Autoloader function. Lazy-loads classes from files in the same directory as current one. Files must be named the same the classes
	 *		@param string $class
	**/
	 public static function autoload( $class ) {
        $file = dirname(__FILE__) . '/../' . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        if ( file_exists($file) ) {
            require $file;
        }
    }

	
	/**
	 *	Trigger an HTTP 404 error
	 *		@public
	**/
	public static function http404() {
		header("HTTP/1.0 404 Not Found");
		if (self::check('404')) {
			self::call(self::get('404'));
		}
		else {
			trigger_error('Page not found');
		}
	}
	
	/**
	 *	Convert Windows double-backslashes to slashes
	 *		@param string $str 
	 *		@return string
	 *		@public
	**/
	public static function fixSlashes($str) {
		return $str?strtr($str,'\\','/'):$str;
	}
	
	/**
	 *	Convert double quotes to equivalent XML entities (&#34;)
	 *		@param string $val 	
	 *		@return string
	 *		@public
	**/
	public static function fixQuotes($val) {
		return is_array($val)?
			array_map('self::fixQuotes',$val):
			(is_string($val)?
				str_replace('"','&#34;',self::resolve($val)):$val);
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
	 * @see table
	 */
	 public static function table($table, $id=FALSE, $columns = FALSE, $connection = FALSE) {
	 	if (!class_exists('table',FALSE))	{
	 		include_once ('db.php');
	 	}
		return new table($table, $id, $columns, $connection);
	 }
	 
	/**
	 *	Sets up autoload, initializes some constants.
	 *		@public
	 *
	**/
	public static function start() {
		//	Use the rolisz autoload function
		spl_autoload_register(array('rolisz', 'autoload'));
		
		//dunno
		$root=rolisz::fixSlashes(realpath('.')).'/';
		
		//Set a few site dependent framework variables		
		self::$global=array(
			'BASE'=>preg_replace('/(.*)\/.+/','$1',$_SERVER['SCRIPT_NAME']),
			'ENCODING'=>'UTF-8',
			'ERROR'=>NULL,
			'QUIET'=>FALSE,
			'RELEASE'=>FALSE,
			'ROOT'=>$root,
			'SITEMAP'=>array(),
			'TIME'=>time(),
			'THROTTLE'=>0,
			'VERSION'=>self::AppName.' '.self::Version
		);
	}
}

// Initialize the framework
rolisz::start();