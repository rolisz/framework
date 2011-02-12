<?php 

/**
	\class base
	Base class, all the others inherit from it
		@package rolisz
		@author Roland Szabo
		@todo Error handling function
		@todo Autoloading function
		@todo ORM
**/
class base {
	//@{
	//! Framework details
	const
		AppName='rolisz PHP framework',
		Version='0.0.0.2';
	//@}

	
	protected static
		//! rolisz global variables
		$global,
		//! Profiler statistics
		$stats;

	/**
		Intercept calls to undefined static methods
			@param $func string
			@param $args array
			@public
	**/
	public static function __callStatic($func,array $args) {
		self::$global['CONTEXT']=get_called_class().'::'.$func;
		trigger_error('Undefined method called '.self::$global['CONTEXT']);
	}

	/**
		Class constructor
			@public
	**/
	public function __construct() {
		// Prohibit use of class as an object
		self::$global['CONTEXT']=get_called_class();
		trigger_error(self::TEXT_Object);
	}

}

/** 
	\class rolisz
	Main class, contains the URL routing thingies, a few cleanups thingies
		@package rolisz
		@author Roland Szabo

**/
class rolisz extends base {

	/**
		Assign handler to route pattern
			@param string $pattern 
			@param mixed $funcs 
			@param string $http 
			@param string $name
			@public
	**/

	public static function route($pattern, $funcs, $http = 'GET', $name = FALSE) {
		if ($name) {
			self::$global['namedRoutes'][$name] = $pattern;
		}
		
		// Check if valid route pattern
		$pattern = trim($pattern,' /');
		$pattern = explode('/',$pattern);

		// Check if http is correct 
		$http = explode('|',$http);
		foreach ($http as $method) {
			if (!preg_match('/(GET|POST)/',$method)) {
				trigger_error("HTTP request type $http is invalid");
				return;
			}
		}
		
		// Valid functions
		if (is_string($funcs)) {
			// String passed
			foreach (explode('|',$funcs) as $func) {
				// Not a lambda function
				if (substr($func,-4)=='.php') {
					// PHP include file specified
					if (!is_file($func)) {
						// Invalid route handler
						trigger_error($func." is not a valid file");
						return;
					}
				}
				elseif (!is_callable($func)) {
					// Invalid route handler
					trigger_error($func.' is not a valid function');
					return;
				}
			}
		}
		elseif (!is_callable($funcs)) {
			// Invalid function
			trigger_error($func.' is not a valid function');
			return;
		}
		
		// Use pattern and HTTP method as array indices
		// Save handlers
		foreach ($http as $method) {
			$route = self::recursify($pattern, $funcs);
			if (isset(self::$global['ROUTES'][$method]))
				self::$global['ROUTES'][$method]=array_merge_recursive(self::$global['ROUTES'][$method],$route);
			else 
				self::$global['ROUTES'][$method]=$route;
		}
	}
	
	/**
		Process routes based on incoming URI. \n
		URL that is not matched will be passed as arguments to the functions that are called
			@public
	**/
	
	public static function run() {
		$routes = &self::$global['ROUTES'][$_SERVER['REQUEST_METHOD']];
		// Process routes
		if (!isset($routes)) {
			trigger_error('No routes set!');
			return;
		}
		
		$found=FALSE;
		$time=time();
		
		// Get the current URL part after base
		$route = explode ('/',trim(substr($_SERVER['REQUEST_URI'],strlen(self::$global['BASE'])),' /'));

		$args=array();
		// Search recursively the depth to which an identical route is defined 
		$i=0;
		while (is_array($routes)) {
			if (isset ($route[$i])) {
				//If the same part is next in both the route and the url
				if (isset($routes[$route[$i]])) {
					$routes=$routes[$route[$i]];
					$i++;
				}
				else {
					$temp = self::array_filter_key($routes,function($val) {
						if ($val[0]==':') return true;
						return false;
					});
					$check = FALSE;
					//If the route contains a variable and the following part matches too. Checks if is last argument or there are other after
					foreach ($temp as $key=>$value) {
						if ((is_array($value) && isset($route[$i+1]) && isset($value[$route[$i+1]])) || (!is_array($value) && !isset($route[$i+1]))) {
							$routes=$value;
							$args[] = $route[$i];
							$i++;
							$check = TRUE;
						}
					}
					//Checks if there is catch-all
					if (!$check) {
						if (isset($routes['*'])) {
							$routes=$routes['*'];
							$args=implode('/',array_splice($route,$i));
						}
						else {
							break;
						}
					}
				}
			}
			//Checks if there is catch-all for the base element
			elseif (isset($routes['*'])) {
					$routes=$routes['*'];
					$args=implode('/',array_splice($route,$i));
				}
			else
				break;
		}
		if (!is_array($routes)) {
				$found=TRUE;
		}
		if (isset($routes[0]) && is_array($routes)) {
			$routes=$routes[0];
			$found=TRUE;
		}
		
		if (!$found) {
			self::http404();
		}
		else {
		
		//Remaining part of URL is passed to functions as arguments
		rolisz::call($routes,$args);
		
		// Delay output
		$elapsed=time()-$time;
		if (self::$global['THROTTLE']/1e3>$elapsed)
			usleep(1e6*(self::$global['THROTTLE']/1e3-$elapsed));
		}
		return;
	}
	
	/**
		Returns a URL with values for a given routing pattern
			@param string $name
			@param array $params
			@return string
	**/
	public static function urlFor($name, $params = array()) {
		if (!isset(self::$global['namedRoutes'][$name])) {
			trigger_error("The $name route could not be found");
		}
		$route = self::$global['namedRoutes'][$name];
		foreach ($params as $key=>$value) {
			$route = str_replace(':' . $key, $value, $route);
		}
		return self::$global['BASE'].$route;
	}
	/** 
		Return value of framework variable, false if not found
			@param string $var 
			@return mixed
			@public
	
	**/
	public static function get($var) {
		if (isset(self::$global[$var])) 
			return self::$global[$var];
		else 
			return false;
	}
	
	/**
		Set value of framework variable
			@param string $var 
			@param mixed $value 
			@public
	
	**/
	
	public static function set($var,$value) {
		self::$global[$var]=$value;
	}
	
	
	/**
		Provide sandbox for functions and import files to prevent direct
		access to framework internals and other scripts
			@param mixed $funcs 
			@param array $args 
			@public
	**/
	public static function call($funcs,$args) {
		if (!is_array($args)) {
			$args=array($args);
		}
		if (is_string($funcs)) {
			// Call each code segment
			foreach (explode('|',$funcs) as $func) {
				if (substr($func,-4)=='.php') {
					// Run external PHP script
					include $func;
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
		Turn linear array into a recursively nested array, with optional argument to be the the value at the end \n
		ex: array('1','2','3') turns into array('1'=>array('2'=>array('3'=>'')))
			@param array $array 
			@param mixed $end 
			@return array
			@public
			
	**/
	
	public static function recursify ($array,$end = '') {
		if (count($array)==0) 
			return $end;
		$returnarray[$array[0]]=self::recursify(array_slice($array,1),$end);
		return $returnarray;
		
	}
	
	/**
		Filter an array by keys. Arguments similar to array_filter.
			@param array $input
			@param function $callback
			@return array
	
	**/
	public static function array_filter_key( $input, $callback ) {
		if ( !is_array( $input ) ) {
			trigger_error( 'array_filter_key() expects parameter 1 to be array, ' . gettype( $input ) . ' given', E_USER_WARNING );
			return null;
		}
		
		if ( empty( $input ) ) {
			return $input;
		}
		
		$filteredKeys = array_filter( array_keys( $input ), $callback );
		if ( empty( $filteredKeys ) ) {
			return array();
		}
		
		$input = array_intersect_key( $input, array_flip( $filteredKeys ) );
		
		return $input;
	}
	/**
		Trigger an HTTP 404 error
			@public
	**/
	public static function http404() {
		// Strip query string
		self::$global['CONTEXT']=parse_url(
			substr($_SERVER['REQUEST_URI'],strlen(self::$global['BASE'])),
			PHP_URL_PATH
		);
		trigger_error(self::$global['CONTEXT'].' can\'t be found');
	}
	
	/**
		Convert Windows double-backslashes to slashes
			@param string $str 
			@return string
			@public
	**/
	public static function fixSlashes($str) {
		return $str?strtr($str,'\\','/'):$str;
	}
	
	/**
		Convert double quotes to equivalent XML entities (&#34;)
			@param string $val 	
			@return string
			@public
	**/
	public static function fixQuotes($val) {
		return is_array($val)?
			array_map('self::fixQuotes',$val):
			(is_string($val)?
				str_replace('"','&#34;',self::resolve($val)):$val);
	}
	
	/**
		Fix mangled braces
			@param string $str 
			@return string
			@public
	**/
	public static function fixBraces($str) {
		// Fix mangled braces
		return strtr(
			$str,array('%7B'=>'{','%7D'=>'}','%5B'=>'[','%5D'=>']','%20'=>' ')
		);
	}
	
	/**
		Initializes some framework constants
			@public
	
	**/
	public static function start() {
		$root=rolisz::fixSlashes(realpath('.')).'/';
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