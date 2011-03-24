<?php 

/**
	Use the rolisz autoload function
**/
spl_autoload_register(array('rolisz', 'autoload'));
/**
	\class base
	Base class, all the others inherit from it
		@package rolisz
		@author Roland Szabo
		@todo Error handling function
		@todo ORM
		@todo Language Detection
		@todo Internationalization
		@todo set conditions for dynamic part of url
**/
class base {
	//@{
	//! Framework details
	const
		AppName='rolisz PHP framework',
		Version='0.0.0.4';
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
		trigger_error('You shoulnd\'t construct this');
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
				if (substr_count($func,'.php')!=0) {
					// Run external PHP script
					$file = strstr($func,'.php',TRUE).'.php';
					if (!is_file($file)) {
						// Invalid route handler
						trigger_error($file." is not a valid file");
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
			$route = $pattern;
			self::$global['ROUTES'][$method][$route] = $funcs;
		}
	}
	
	/**
		Checks if $route matches the current URL
			@param string $route
			$return TRUE|FALSE
	
	**/
	public static function matches($route) {
		$pattern = $route;
		$route = array_slice(explode('/',$route),1);
		$i = 0;
		$url = self::$global['ROUTE'];
		while (isset($route[$i]) && isset($url[$i])) {
			if ($route[$i]=='*') {
				return true;
			}
			elseif (strlen($route[$i])>0 && $route[$i][0]==':') {
				self::$global['ARGS'][$pattern][] = $url[$i];
				$i++;
			}
			elseif ($route[$i] == $url[$i]) {
				$i++;
			}
			else {
				unset(self::$global['ARGS'][$pattern]);
				return false;
			}
		}	
		if (isset($route[$i])==isset($url[$i])) {
		return true;
		}
		elseif (!isset($url[$i]) && $route[$i]=='*') {
			return true;
		}
		else {
			unset(self::$global['ARGS'][$pattern]);
			return false;
		}
	}

	/**
		Process routes based on incoming URI. \n
		URL that is not matched will be passed as arguments to the functions that are called
			@public
	**/
	
	public static function run() {
		$routes = array_keys(self::$global['ROUTES'][$_SERVER['REQUEST_METHOD']]);
		// Process routes
		if (!isset($routes)) {
			trigger_error('No routes set!');
			return;
		}
		
		$found=FALSE;
		$time=time();
		
		self::$global['ROUTE'] = explode ('/',trim(substr($_SERVER['REQUEST_URI'],strlen(self::$global['BASE'])),' /'));
		
		self::$global['ARGS'] = array();
		
		$valid_routes = array();
		// Search recursively the depth to which an identical route is defined 
		foreach ($routes as $route) {
			if (self::matches($route)) {
				$valid_routes [] = $route;
			}
		}
		rsort($valid_routes);
		self::$global['ARGS'] = array_unique(self::$global['ARGS']);

		if (!empty($valid_routes)) {
				$found=TRUE;
		}
		
		if (!$found) {
			self::http404();
		}
		else {
		
		if (!isset(self::$global['ARGS'][$valid_routes[0]])) {
			self::$global['ARGS'][$valid_routes[0]] = array();
		}
		//Remaining part of URL is passed to functions as arguments
		rolisz::call(self::$global['ROUTES'][$_SERVER['REQUEST_METHOD']][$valid_routes[0]],self::$global['ARGS'][$valid_routes[0]]);
		
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
		Connects to a database adapter, as defined in databaseAdapter.php
			@param string $dbtype
			@param string $host
			@param string $username
			@param string $password
			@param string $db
			@return databaseAdapter
	**/
	public static function connect($dbtype,$host, $username, $password, $db) {
		include ('databaseAdapter.php');
		$adapterClass = "{$dbtype}Database";
		self::$global['dbCon'] = new $adapterClass($host, $username, $password, $db);
		return self::$global['dbCon'];
	}
	
	/**
		Autoloader function. Lazy-loads classes from files in the same directory as current one. Files must be named the same the classes
			@param string $class
	**/
	 public static function autoload( $class ) {
        $file = dirname(__FILE__) . '/../' . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        if ( file_exists($file) ) {
            require $file;
        }
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