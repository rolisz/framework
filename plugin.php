<?php
include_once ('rolisz.php');

/** 
 * \class plugin
 *  Plugin class. This is inherited for all the other plugins. 
 * 		@package rolisz
 * 		@author Roland Szabo
 * 
 */
class plugin extends base {
	
	public function __construct() {

	}
	
	public function run($arg) {
		var_dump( $arg);
	}
	
	public function getName() {
		return get_class($this);
	}
	
	public static function getDefaultExecutionPoints() {
		return false;
	}
	
	public static function getDefaultMethod() {
		return 'run';
	}
	
}
?>