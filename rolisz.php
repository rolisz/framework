<?php 
class base {
	//@{
	//! Framework details
	const
		AppName='rolisz PHP framework',
		Version='0.0.0.1';
	//@}

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_Object='{@CONTEXT} cannot be used in object context',
		TEXT_Class='Undefined class {@CONTEXT}',
		TEXT_Method='Undefined method {@CONTEXT}',
		TEXT_PHPExt='PHP extension {@CONTEXT} is not enabled';
	//@}

	protected static
		//! Fat-Free global variables
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

class rolisz extends base {

	/**
		Validate route pattern and break it down to an array consisting
		of the request method and request URI
			@return mixed
			@param $pattern string
			@public
	**/
	
	public static function check_route($pattern) {
		preg_match('/(\S+\s+)?(\S+)/',$pattern,$parts);
		$parts=array_slice($parts,1);
		$valid=TRUE;
		if ($parts[0]=="") {
				$parts[0]='GET';
		}
		foreach (explode('|',$parts[0]) as $method) {
			if (!preg_match('/(GET|POST)/',$method)) {
				$valid=FALSE;
				break;
			}
		}
		$parts[0]=trim($parts[0]);
		$parts[1]=trim($parts[1],'/');
		$parts[1]=explode('/',$parts[1]);
		foreach ($parts[1] as $key=>$part) {
			$parts[1][$part]='';
		}
		var_dump($parts);
		if ($valid)
			return $parts;
		// Invalid route
		trigger_error("Route $pattern is invalid");
		return FALSE;
	}
	
	/**
		Assign handler to route pattern
			@param $pattern string
			@param $funcs mixed
			@public
	**/

	public static function route($pattern, $funcs) {
		// Check if valid route pattern
		$route=self::check_route($pattern);
		if (!$route) {
			trigger_error("Route $pattern is invalid");
			return;
		}
		// Valid URI pattern
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
			// Invalid route handler
			trigger_error($func.' is not a valid function');
			return;
		}
		// Assign name to URI variable 
		//have no ideea what it is (I think some sort of sanitizing)
		$regex=preg_replace(
			'/{?@(\w+\b)}?/i',
			// Valid URL characters (RFC 1738)
			'(?P<$1>[\w\-\.!~\*\'"(),]+\b)',
			// Wildcard character in URI
			str_replace('\*','(.*)',preg_quote($route[1],'/'))
		);
		// Use pattern and HTTP method as array indices
		// Save handlers
		self::$global['ROUTES']['/^'.$regex.'\/?(?:\?.*)?$/i'][$route[0]]=$funcs;
	}
	
	/**
		Process routes based on incoming URI
			@public
	**/
	
	public static function run() {
		$routes=&self::$global['ROUTES'];
		// Process routes
		if (!isset($routes)) {
			trigger_error('No routes set!');
			return;
		}
		$found=FALSE;
		// Detailed routes get matched first
		krsort($routes);
		// Save the current time
		$time=time();
		foreach ($routes as $regex=>$route) {
			if (!preg_match($regex,
				substr($_SERVER['REQUEST_URI'],strlen(self::$global['BASE'])),
				$args))
				continue;
			$found=TRUE;
			// Inspect each defined route
			foreach ($route as $method=>$proc) {
				if (!preg_match('/'.$method.'/',$_SERVER['REQUEST_METHOD'])) {
					continue;
				}
				// Save named regex captures
				foreach ($args as $key=>$arg)
					// Remove non-zero indexed elements
					if (is_numeric($key) && $key)
						unset($args[$key]);
				self::$global['PARAMS']=$args;
				rolisz::call($proc);
			}
			$elapsed=time()-$time;
			if (self::$global['THROTTLE']/1e3>$elapsed)
				// Delay output
				usleep(1e6*(self::$global['THROTTLE']/1e3-$elapsed));
			// Hail the conquering hero
			return;
		}
		
		// No such Web page
		self::http404();
	}

	
	public static function get($var) {
		return self::$global[$var];
	}
	
	public static function set($var,$value) {
		self::$global[$var]=$value;
	}
	/**
		Provide sandbox for functions and import files to prevent direct
		access to framework internals and other scripts
			@param $funcs mixed
			@public
	**/
	public static function call($funcs) {
		if (is_string($funcs)) {
			// Call each code segment
			foreach (explode('|',$funcs) as $func) {
				if (substr($func,-4)=='.php') {
					// Run external PHP script
					include $func;
				} 
				else {
					// Call lambda function
					call_user_func($func);
				}
			}
		}
		else
			// Call lambda function
			call_user_func($funcs);

	}
	
	/**
		Turn linear array into a recursively nested array
		array('1','2','3') turns into array('1'=>array('2'=>array('3'=>'')))
			@return array
			@param $array array
			@public
			
	**/
	
	public static function recursify ($array) {
		if (count($array)==0) 
			return '';
		$returnarray[$array[0]]=recursify(array_slice($array,1));
		return $returnarray;
		
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
			@return string
			@param $str string
			@public
	**/
	public static function fixSlashes($str) {
		return $str?strtr($str,'\\','/'):$str;
	}
	
	/**
		Convert double quotes to equivalent XML entities (&#34;)
			@return string
			@param $val string
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
			@return string
			@param $str string
			@public
	**/
	public static function fixBraces($str) {
		// Fix mangled braces
		return strtr(
			$str,array('%7B'=>'{','%7D'=>'}','%5B'=>'[','%5D'=>']','%20'=>' ')
		);
	}
	
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

rolisz::start();