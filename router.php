<?php
include_once ('rolisz.php');
/**
 *  \class router
 * 	This class provides URL routing, pretty URLs and named URLs functionality. 
 * 		@package rolisz
 * 		@author Roland Szabo
 */
class router extends base {
	/**
	 *  Set a new route pattern.  
	 * 		@param string $pattern defines the route. It can contain variables like /:var/ and catch-alls at the end: url/*. If
	 * 	it's an array of routes (that all lead to the same thing), the $name parameter will be ignored.
	 * 		@param mixed $funcs defines the functions that can be passed as arguments. The functions can given as a list names separated by |, an array
	 *  consisting of things that return true to is_callable. If the array contains things that return false, it will trigger an error and
	 * 	the element will be unset. Also, you can just pass an anonymous function. If you give it as a string, you can also pass files.
	 * 		@param string $http optional, defaults to GET, and is the HTTP method for which this route is valid. It can be GET or POST.
	 * 	If you want the route to be valid for AJAX calls only, use AGET or APOST.
	 * 		@param string $name optional, defaults to FALSE and gives the name of the route, to be used with urlFor(). 
	**/
	public static function route($pattern, $funcs, $http = 'GET', $name = FALSE) {
		if ($name && is_string($pattern)) {
			self::$global['namedRoutes'][$name] = trim($pattern);
		}
		
		// Check if http is correct 
		$http = explode('|',$http);
		foreach ($http as $method) {
			if (!preg_match('/A?(GET|POST)/',$method)) {
				throw new Exception("HTTP request type $http is invalid");
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
						throw new Exception($file." is not a valid file");
						return;
					}
				} 
			}
		}
		elseif (is_array($funcs)) {
			foreach ($funcs as $function) {
				if (!is_array($function) && substr_count($function,'.php')!=0 ) {
					if (!is_file($function)) {
						// Invalid file 
						throw new Exception($function." is not a valid file");
						return;
					}
				}
			}
		} 		
		elseif(!is_callable($funcs)) {
			// Invalid function
			throw new Exception($func.' is not a valid function');
			return;
		}
		
		// Use pattern and HTTP method as array indices
		// Save handlers
		foreach ($http as $method) {
			if (is_string($pattern))
				$pattern = array($pattern);
			foreach ($pattern as $pat) {
				$route = $pat;
				self::$global['ROUTES'][$method][$route] = $funcs;
			}
		}
	}
	
	/** 
	 * Checks if $route matches the current URL
	 * 		@param string $route
	 *  	@retval true
	 * 		@retval false
	**/
	private static function matches($route) {
		$pattern = $route;
		$route = array_slice(explode('/',$route),1);
		$i = 0;
		$url = self::$global['ROUTE'];
		while (isset($route[$i]) && isset($url[$i])) {
			// If we have a catchall in the route
			if ($route[$i]=='*') {
				return true;
			}
			// If we have a variable in our pattern
			elseif (strlen($route[$i])>0 && $route[$i][0]==':') {
				self::$global['ARGS'][$pattern][] = $url[$i];
				$i++;
			}
			// If it's a match
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
	 * 		Process routes based on incoming URI. URL that is not matched will be passed
	 * 	as arguments to the functions that are called
	**/
	
	public static function run() {
		$routes = array_keys(self::$global['ROUTES'][$_SERVER['REQUEST_METHOD']]);
		if (self::$global['AJAX']) {
			$routes = array_merge($routes, array_keys(self::$global['ROUTES']['A'.$_SERVER['REQUEST_METHOD']]));
		}
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
		rolisz::runPlugins('beforeMatch',self::$global['ROUTE']);
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
			rolisz::http404();
		}
		else {
		
		if (!isset(self::$global['ARGS'][$valid_routes[0]])) {
			self::$global['ARGS'][$valid_routes[0]] = array();
		}
		self::$global['currentRoute'] = self::$global['ROUTES'][$_SERVER['REQUEST_METHOD']][$valid_routes[0]];
		rolisz::runPlugins('afterMatch',self::$global['currentRoute']);
		rolisz::call(self::$global['currentRoute'],self::$global['ARGS'][$valid_routes[0]]);
		
		// Delay output for throttling
		$elapsed=time()-$time;
		if (self::$global['THROTTLE']/1e3>$elapsed)
			usleep(1e6*(self::$global['THROTTLE']/1e3-$elapsed));
		}
		return;
	}
	
	/** 
	 * Returns a URL with values for a given routing pattern
	 * 		@param string $name
	 * 		@param array $params
	 * 		@return string
	**/
	public static function urlFor($name, $params = array()) {
		if (!isset(self::$global['namedRoutes'][$name])) {
			throw new Exception("The $name route could not be found");
		}
		$route = self::$global['namedRoutes'][$name];
		foreach ($params as $key=>$value) {
			$route = str_replace(':' . $key, $value, $route);
		}
		return self::$global['BASE'].$route;
	}
}
?>