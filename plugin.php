<?php
include_once ('rolisz.php');

/** 
 * \class plugin
 *  Plugin class. This is inherited for all the other plugins. Not an interface in case you don't want to redeclare some of the
 * 	functions in other plugins
 * 		@package rolisz
 * 		@author Roland Szabo
 * 
 */
class plugin extends base {
	
	/**
	 *  Constructor
	 */
	public function __construct() {

	}
	
	/**
	 *  Default function run at an execution point
	 */
	public function run($arg) {
		var_dump( $arg);
	}
	
	/**
	 *  Returns the name of the plugin
	 * 		@retval string
	 */
	public function getName() {
		return get_class($this);
	}
	
	/**
	 *  Returns the default excution points. By default it doesn't have any. The following execution points are available
	 * inside the rolisz framework: 
	 * 		- beforeMatch fires in the router module before trying to match the current URL.
	 * 		- afterMatch fires in the router module after it has succesfully matched a URL. If no URL has been matched, it won't fire.
	 * 		- hydrateTable fires in the table class if an object has been hydrated from the database.
	 * 		- saveTable fires in the table class if an object has been successfully saved to the database.
	 * 		- deleteTable fires in the table class if an object has been successfully deleted from the database.
	 * 		- compileTemplate fires in the template engine after compiling a template.   
	 * 		@retval false
	 * 		@retval string
	 * 		@retval array
	 */
	public static function getDefaultExecutionPoints() {
		return false;
	}
	
	/**
	 *  Returns name of the the default function to run at an execution point. Default is 'run'
	 * 		@return string
	 */
	public static function getDefaultMethod() {
		return 'run';
	}
	
}
?>