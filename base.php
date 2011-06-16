<?php 

/**
 *	\class base
 *	Base class for all the others in the framwork. Defines some framework details, global variables and setters and getters for framework variables. 
 *		@package rolisz
 *		@author Roland Szabo
 *		@todo Error handling function
 *		@todo Language Detection
 *		@todo Internationalization
 *		@todo Set conditions for dynamic part of url
**/
class base {
	// Framework details
	const AppName = 'rolisz PHP framework',
		  Version = '0.0.3',
		  Module = 'Base class';
	
	protected static
		// \var array $global
		// rolisz global variables
		$global,
		// \var array $plugins
		//Internal list of the plugins registered
		$plugins,
		// \var array $executionPoints
		//Internal list of execution points for the plugins
		$executionPoints;

	/**
	 * 	Base constructor is private to disable creation of objects and to reinforce the usage of the singleton pattern.
	**/
	private function __construct() {
		trigger_error('You shoulnd\'t construct this');
	}

	/**
	 * 	Return the value of a framework variable or false if it was not found.
	 *		@param string $var 
	 * 		@retval true
	 * 		@retval false
	**/
	public static function get($var) {
		if (isset(self::$global[$var])) 
			return self::$global[$var];
		else 
			return false;
	}
	
	/**
	* Checks if $var variable has been set in the framework.
	* 		@param string $var
	*	 	@retval true
	* 		@retval false 
	*/
	public static function check($var) {
		return isset(self::$global[$var]);
	}
	
	/**
	 *	Set the value of a framework variable. If $var param is a string, then a variable called $var will have the value of $value.
	 * If $var is an array, it should be a key-pair value like this <code> array('var_name'=>'132','2ndvar'=>123) </code>
	 *		@param string|array $var 
	 *		@param mixed $value 
	 *		@public
	**/
	
	public static function set($var,$value = FALSE) {
		if (is_array($var)) {
			foreach ($var as $key => $value) {
				self::$global[$key] = $value;
			}
		}
		else {
			self::$global[$var] = $value;
		}
	}
	
}
?>