<?php 

/**
 *	\class base
 *	Base class. Defines some framework details, global variables and intercept functions
 *		@package rolisz
 *		@author Roland Szabo
 *		@todo Error handling function
 *		@todo Language Detection
 *		@todo Internationalization
 *		@todo set conditions for dynamic part of url
**/
class base {
	// Framework details
	const AppName = 'rolisz PHP framework',
		  Version = '0.0.1',
		  Module = 'Base class';
	
	protected static
		// rolisz global variables
		$global;

	/**
	 * 	Intercept calls to undefined static methods
	 * 		@param $func string
	 *		@param $args array
	 *		@public
	**/
	public static function __callStatic($func,array $args) {
		trigger_error('You have called an unknown static function:'.get_called_class().'::'.$func);
	}

	/**
	 * 	Base constructor is private to disable creation of objects and to reinforce the usage
	 *  of singleton pattern
	 *		@private
	**/
	private function __construct() {
		trigger_error('You shoulnd\'t construct this');
	}

	/**
	 * 	Return value of a framework variable, false if not found
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
	* Checks if $var has been set in the framework
	* 	@retval true
	* 	@retval false 
	*/
	public static function check($var) {
		if (isset(self::$global[$var])) {
			return true;
		}
		return false;
	}
	/**
	 *	Set the value of a framework variable. If $var param is string, then a variable called $var will have the value of $value.
	 * If $var is array, it should be a key-pair value like this array('var_name'=>'132','2ndvar'=>123).
	 *		@param $var - string
	 * 					- array 
	 *		@param mixed $value 
	 *		@public
	**/
	
	public static function set($var,$value = FALSE) {
		if (is_array($var) && $value == FALSE) {
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