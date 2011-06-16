<?php
	/**
		\class template
		Provides basic templating capabilites
			@package rolisz
			@author Roland Szabo
	
	**/
include_once ('rolisz.php');

class template extends base {
		
	// Array to hold the values that will be substituted into the template. It's static
	// so that the values can be shared among templates and subtemplates.
	private static $values;
	
	/**
	 *  Empty constructor. It's created separately to prevent inheriting the singleton 
	 * 	pattern from base.
	 */
	public function __construct() {
		$this->assign('base',rolisz::get('BASE'));
	}
	
	/**
	 *	Set the value of a template variable magically. If $var param is string, then a variable called $var will have the value of $value.
	 * If $var is array, it should be a key-pair value like this array('var_name'=>'132','2ndvar'=>123). This is separate from the 
	 * rolisz global variables.
	 *		@param string|array $var 
	 *		@param mixed $value 
	 *		@public
	**/
	public function __set($var,$value = FALSE) {
		if (is_array($var) && $value == FALSE) {
			foreach ($var as $key => $value) {
				self::$values[$key] = $value;
			}
		}
		else {
			self::$values[$var] = $value;
		}
	}
	
	/**
	 *	Set the value of a template variable. If $var param is string, then a variable called $var will have the value of $value.
	 * If $var is array, it should be a key-pair value like this array('var_name'=>'132','2ndvar'=>123). This is separate from the 
	 * rolisz global variables.
	 *		@param string|array $var 
	 *		@param mixed $value 
	 *		@public
	**/
	public function assign($var,$value = FALSE) {
		if (is_array($var) && $value == FALSE) {
			foreach ($var as $key => $value) {
				self::$values[$key] = $value;
			}
		}
		else {
			self::$values[$var] = $value;
		}
	}

	/**
	 * 	Return value of template variable, false if not found. This is separate from the 
	 * rolisz global variables.
	 *		@param string $name 
	 * 		@retval true
	 * 		@retval false
	**/
	public function __get($name) {
		if (isset(self::$values[$name])) 
			return self::$values[$name];
		else 
			return false;
	}
	
	/**
	 * 	Magic method for the serialization of the template object. 
	 * 		@return string containing the template
	 */
	public function __toString()
	{
		return $this->getOutput();
	}
	
	/**
	 * Prints the  $tpl template after compiling
	 * 		@param string $tpl  
	 */
		
	public function view($tpl) {
		echo $this->getOutput($tpl);
	}
	
	/**
	* Compiles, executes, and filters a template source.
	*  		@param string $template The template to process; 
	* 		@return mixed The template output string
	*/
	public function getOutput($template) 	{
		if (rolisz::check('TEMPLATE')) {
			$template = rolisz::get('TEMPLATE').'/'.$template;
		}
		if (!is_file($template)) {
			throw new Exception("$template template can't be found");
		}		
		if (pathinfo($template,PATHINFO_EXTENSION)!='tpl' ) {
			throw new Exception("$template template has wrong extension. It should be .tpl");
		}
		
		if (!$this->checkCompile($template)) {
			$this->compile($template);
		}
		$template = pathinfo($template,PATHINFO_FILENAME).md5($template).'.php';
		// Buffer output so we can return it instead of displaying.
		ob_start();
		extract(self::$values);
		include ('temp/'.$template);
		$templateContents = ob_get_contents();
		ob_end_clean();
		return $templateContents;
	}
	
	
	/**
	 *  Includes another template
	 * 		@param string $template
	 */
	public function includeSub($template) {
		if (!is_file($template)) {
			throw new Exception("$template template can't be found");
		}		
		if (pathinfo($template,PATHINFO_EXTENSION)!='tpl' ) {
			throw new Exception("$template template has wrong extension. It should be .tpl");
		}	
		if (!$this->checkCompile($template)) {
			$this->compile($template);
		}
		$template = pathinfo($template,PATHINFO_FILENAME).md5($template).'.php';
		include ('temp/'.$template);
	}
	
	/**
	 * Checks if the file has been compiled to the temp folder and if it is more recent than the last change to the template
	 * 		@param string $template 
	 * 		@retval true
	 * 		@retval false
	 */
	private function checkCompile($template) {
		$compile = 'temp/'.pathinfo($template,PATHINFO_FILENAME).md5($template).'.php';
		if (file_exists($compile) && (filemtime($template)-filemtime($compile))<0 )
			return true;
		return false;
	}
	
	/**
	 * 	Performs the replacing of the shorthand tags to full PHP tags in the $template file. Places the results in 
	 * a temp folder, in a file called the md5 value of $template, to prevent collisions. 
	 * 		@param string $template
	 * 
	 */
	private function compile($template) {
		
		$template_code = file_get_contents( $template );
		
		$template_code = preg_replace('/{ignore}.+?{\/ignore}/','',$template_code);
		$template_code = preg_replace('/{noparse}(.+?){\/noparse}/','$1',$template_code);//@todo: fix this
		
		//Rules for substituting  ifs and elses
		$template_code = preg_replace('/{if="(.+?)"}/','<?php if($1) { ?>',$template_code);
		$template_code = preg_replace('/{\/if}/','<?php } ?>',$template_code);
		$template_code = preg_replace('/{else}/','<?php } else { ?>',$template_code);
		$template_code = preg_replace('/{elseif="(.+?)"}/','<?php } elseif ($1) { ?>',$template_code);
		
		// Rules for substituting the two kinds of foreaches and the ed foreach
		$template_code = preg_replace('/{foreach (.+?) as (.+?) => (.+?) ?}/','<?php if (is_array($$1))  foreach ($$1 as $$2=>$$3) { ?>',$template_code);
		$template_code = preg_replace('/{foreach (.+?) as (.+?) ?}/','<?php if (is_array($$1))  foreach ($$1 as $$2) { ?>',$template_code);
		$template_code = preg_replace('/{\/foreach}/','<?php  } ?>',$template_code);
		
		// Each include directive found in the template is compiled and then replaced 
		// with a static call to viewS 
		preg_match_all('/{include="(.+?)"}/',$template_code,$matches);
		if (rolisz::check('TEMPLATE')) {
			$tplFolder = rolisz::get('TEMPLATE').'/';
		}
		foreach ($matches[1] as $match) {
			$this->compile($tplFolder.$match);
		}
		$template_code = preg_replace('/{include="(.+?)"}/','<?php $this->view(\'$1\'); ?>',$template_code);
		
		// Replace rule for variables. Allows for multiple functions to be called arround it , separated by |
		$template_code = preg_replace_callback('/{{ (.*?)(\|(.+?))? }}/',function($match) {
			$str = '<?php echo ';
			if (isset ($match[3])) {
				$functions = explode('|',$match[3]);
				$nr = 0;
				$additionalArgs= array();
				foreach ($functions as $function) {
					$nr++;
					$matches;
					if (preg_match('/\((.+)\)/',$function, $matches)) {
						$function = preg_replace('/\(.+\)/','',$function);
						$additionalArgs[$nr] = $matches[1];
					}
					$str.= $function.'(';
				}
				if ($match[1]!='')
					$str.= '$'.$match[1];
				if (!empty($additionalArgs)) {
					foreach ($additionalArgs as $key=>$value) {
						$str = str_pad($str,strlen($str) + $nr - $key,')');
						if ($match[1]!='')
							$str.= ',';
						$str.= $value.')';
					}
				}
				else {
					$str = str_pad($str,strlen($str) + $nr,')');
				}
				$str.= '; ?>';
			}
			else {
				$str.= '$'.$match[1].'; ?>';
			}
			return $str;
		},$template_code);
		
		if (!is_dir('temp'))
			mkdir('temp');
		$compile = 'temp/'.pathinfo($template,PATHINFO_FILENAME).md5($template).'.php';
		file_put_contents($compile,$template_code);
	}
	
}


?>