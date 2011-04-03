<?php
	/**
		\class template
		Provides basic templating capabilites
			@package rolisz
			@author Roland Szabo
	
	**/
include_once ('rolisz.php');

class template extends base {

	public function __construct() {
	
	}
	
	/**
	 *	Set the value of a framework variable magically. If $var param is string, then a variable called $var will have the value of $value.
	 * If $var is array, it should be a key-pair value like this array('var_name'=>'132','2ndvar'=>123).
	 *		@param $var - string
	 * 					- array 
	 *		@param mixed $value 
	 *		@public
	**/
	public function __set($var,$value = FALSE) {
		if (is_array($var) && $value == FALSE) {
			foreach ($var as $key => $value) {
				self::$global[$key] = $value;
			}
		}
		else {
			self::$global[$var] = $value;
		}
	}
	
	/**
	 *	Set the value of a framework variable. If $var param is string, then a variable called $var will have the value of $value.
	 * If $var is array, it should be a key-pair value like this array('var_name'=>'132','2ndvar'=>123).
	 *		@param $var - string
	 * 					- array 
	 *		@param mixed $value 
	 *		@public
	**/
	public function assign($var,$value = FALSE) {
		if (is_array($var) && $value == FALSE) {
			foreach ($var as $key => $value) {
				self::$global[$key] = $value;
			}
		}
		else {
			self::$global[$var] = $value;
		}
	}

	/**
	 * 	Return value of framework variable, false if not found
	 *		@param string $var 
	 * 		@retval true
	 * 		@retval false
	 * 		@public	
	**/
	public function __get($name) {
		if (isset(self::$global[$var])) 
			return self::$global[$var];
		else 
			return false;
	}
	
	public function __toString()
	{
		return $this->getOutput();
	}
	
	public function escape($value) {
		if (func_num_args() == 1) {
			$value = htmlspecialchars($value);
		}
		else {
			$args = array_shift(func_get_args());
			foreach ($args as $func) {
				$value = call_user_func($func, $value);
			}
		}
		return $value;
	
	}
	
	/**
	 * Prints the  $tpl template after compiling
	 * 		@params string $tpl  
	 */
		
	public function view($tpl) {
		echo $this->getOutput($tpl);
	}
	
	/**
	* Compiles, executes, and filters a template source.
	* 		@access public
	*  		@param string $template The template to process; 
	* 		@return mixed The template output string
	*/
	public function getOutput($template) 	{
		if (!is_file($template)) {
			trigger_error("$template template can't be found");
		}		
		
		if (!$this->checkCompile($template)) {
			$this->compile($template);
		}
		// buffer output so we can return it instead of displaying.
		ob_start();
		extract($this->values);
		include ('temp/'.md5($template));
		$templateContents = ob_get_contents();
		ob_end_clean();
		return $templateContents;
	}
	
	/**
	 * Checks if the file has been compiled to the temp folder and if it is more recent than the last change to the template
	 * 		@param string $template 
	 * 		@retval true
	 * 		@retval false
	 */
	private function checkCompile($template) {
		if (file_exists('temp/'.md5($template)) && (filemtime($template)-filemtime('temp/'.md5($template)))<0 )
			return true;
		return false;
	}
	
	/**
	 * 	Performs the replacing of the shorthand tags to full PHP tags in the $template file. Places the results in 
	 * a temp folder, in a file with the name md5($template) 
	 * 		@param string $template
	 * 
	 */
	private function compile($template) {
		
		$template_code = file_get_contents( $template );
		
		$template_code = preg_replace('/{ignore}.+?{\/ignore}/','',$template_code);
		$template_code = preg_replace('/{noparse}(.+?){\/noparse}/','$1',$template_code);//@todo: fix this
		
		$template_code = preg_replace('/{if="(.+?)"}/','<?php if($1) { ?>',$template_code);
		$template_code = preg_replace('/{\/if}/','<?php } ?>',$template_code);
		$template_code = preg_replace('/{else}/','<?php } else { ?>',$template_code);
		$template_code = preg_replace('/{elseif="(.+?)"}/','<?php } elseif ($1) { ?>',$template_code);
		
		$template_code = preg_replace('/{foreach (\$.+?) as (\$.+?) ?}/','<?php foreach ($1 as $2) { ?>',$template_code);
		$template_code = preg_replace('/{\/foreach}/','<?php } ?>',$template_code);
		
		$template_code = preg_replace('/{include=(.+?)}/','<?php include(\'$1\'); ?>',$template_code);
		$template_code = preg_replace('/{{ (.+?) }}/','<?php echo $this->escape($$1); ?>',$template_code);
		
		if (!is_dir('temp'))
			mkdir('temp');
		file_put_contents('temp/'.md5($template),$template_code);
	}
}


?>