<?php
include_once('rolisz.php');

/**
 * \class pluginStructure
 * 	Class that handles plugin registering, execution points. 
 * 		@package rolisz
 * 		@package Roland Szabo
 */
 class pluginStructure extends base{
 	
	
	public function __construct() {
		
	}
	/**
	 *  Registers a plugin. First parameter can be either the name of a plugin, a plugin file or a plugin class itself.
	 * 	Second parameter is optional and speciefies when to execute the plugin if it has to be executed sometime during the 
	 *  execution of rolisz classes. For a list of events, see plugin class. 
	 * 	If the plugin constructor requires parameters, you have to instantiate an object and pass that as a parameter to this function.
	 * 		@param $name - string	- class
	 * 								- file
	 * 					 - plugin object
	 * 					 - array with a class and the function to call from it
	 * 		@param $execution Optional. See plugin class for values.		 
	 */
	public static function registerPlugin($name,$execution = FALSE) {
		if (is_object($name) && !is_subclass_of($name,'plugin')) {
			trigger_error('You are trying to register a plugin, but you are giving the wrong object');
		} 
		elseif (is_array($name) && count($name) == 2) {
			if (!class_exists($name[0]) || !method_exists($name[0],$name[1])) {
				trigger_error('You are trying to register a plugin, but the arrays is wrong');
				return false;
			}
		}
		elseif (is_string($name)) {
			if (!class_exists($name) || !is_subclass_of($name,'plugin')) {
				if (!file_exists($name) && file_exists($name.'.php')) {
					$name = $name.'.php';	
					//@todo check in plugins folder
				}
				elseif (!file_exists($name)) {
					
					trigger_error('Cannot find such a plugin, not as a class, not as a file');
					return false;
				} 
				include_once($name);
				$name = pathinfo($name,PATHINFO_BASENAME );
				$name = substr($name,0,-4);
				if (!class_exists($name) || !is_subclass_of($name,'plugin')) {
					trigger_error('You are trying to register a plugin that is not really a plugin');
					return false;
				}
			}
		}
		if (!is_array($name) && !is_string($name) && !is_object($name)) {
			trigger_error('You are trying to register an plugin with, but first parameter is the wrong type');
		}
		self::$plugins [] = $name;
		
		$name = end(self::$plugins);
		$name = &self::$plugins[key(self::$plugins)];
		if ($execution != FALSE) {
			if (isset(self::$executionPoints[$execution])) {
				self::$executionPoints[$execution][] = $name;
			}
			else {
				trigger_error('Inexistent execution point given');
			}
		}
		elseif ($name::getDefaultExecutionPoints()) {
			$default = $name::getDefaultExecutionPoints();
			if (is_string($default)) {
				self::$executionPoints[$default][] = $name;
			}
			elseif (is_array($default)) {
				foreach ($defaul as $point) {
					self::$executionPoints[$point][] = $name;
				}
			}
			else {
				trigger_error($name.' plugin has incorrect default execution points');
			}
		}
	} 
	
	/**
	 * 	Unregisters a plugin. First parameter is the name of the plugin, as returned by the plugin::getName() method.
	 * 		@param string $name
	 */
	public static function unregisterPlugin($name) {
		if (in_array($name,self::$plugins)) {
			unset(self::$plugins[array_search($name,self::$plugins)]);
			//@todo remove from execution point lists too
		}
		else {
			trigger_error($name.' plugin not found');
		}
	}

	/**
	 *  Checks if a plugin called $name exists. 
	 * 		@param string $name
	 * 		@retval true
	 * 		@retval false 
	 */
	 public static function checkPlugin($name) {
	 	if (in_array($name,self::$plugins)) {
	 		return true;
		}
		return false;
	 }
	 
	/**
	 *  Adds a new execution point to the internal execution point list. 
	 * 		@param string $name
	*/
	public function registerExecutionPoint($name) {
		if (!isset(self::$executionPoints[$name])) {
			self::$executionPoints[$name] = array();
		}
		else {
			trigger_error("{$name} execution point already exists");
		}
	}
	 
	/**
	*  Removes an execution point from the internal execution point list. Attention, you can remove rolisz's own execution points,
	*	after which it may no longer work as expected. 
	* 	@param string $name
	*/
	public function unregisterExecutionPoint($name) {
		if (isset(self::$executionPoints[$name])) {
			unset(self::$executionPoints[$name]);
		}
		else {
			trigger_error("{$name} execution point doesn't exist");
		}
	}
	
	/**
	 *  Checks if an execution point exists.
	 * 		@param string $name
	 * 		@retval true
	 * 		@retval false
	 */
	public static function checkExecutionPoint($name) {
		if (isset(self::$executionPoints[$name])) {
			return true;
		}
		return false;	
	}
	
	/**
	 *  Runs plugins and functions that are associated with the $name execution point. Any additional parameters will be passed 
	 * 	to the function in the same order. 
	 * 		@param string $name
	 */
	 public static function runPlugins($name) {
	 	if (isset(self::$executionPoints[$name]) 
			&& is_array(self::$executionPoints[$name]) 
			&& !empty(self::$executionPoints[$name])) {
				$args = array_slice(func_get_args(),1);
				var_dump($args);
				foreach (self::$executionPoints[$name] as $function) {
					if (is_array($function)) {
						$class = new $function[0];
						$method = $function[1];
					}
					elseif (is_object($function)) {
						$class = $function;
						$method =  $function::getDefaultMethod();
					}
					else {
						$class = new $function;
						$method = $function::getDefaultMethod();
					}
					call_user_func_array(array($class,$method),$args);
				}
		}
	 }
 }
?>