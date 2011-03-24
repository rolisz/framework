<?php
	/**
		\class template
		Provides basic templating capabilites
			@package rolisz
			@author Roland Szabo
	
	**/
include_once ('rolisz.php');

class template extends base {

	/**
		Vars
	**/
	private $values = array();
	private $filters = array();

	public function __construct() {
	
	}
	
	public function __set($name,$val = FALSE) {
		if (is_array($name)) {
			$this->values = array_merge($this->values,$name);
		}
		else {
			$this->values[$name]=$this->escape($val);
		}
	}
	
	public function assign($name,$val = FALSE) {
		if (is_array($name)) {
			$this->values = array_merge($this->values,$name);
		}
		else {
			$this->values[$name]=$this->escape($val);
		}
	}

	public function __get($name) {
		if (isset($this->values[$name])) {
			return $this->values[$name];
		}
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
	
		
	public function view($tpl) {
		echo $this->getOutput($tpl);
	}
	
	/**
	 * Returns output, including error_text if an error occurs.
	 * 
	 * @param string $tpl 
	 * 
	 * @return string The template output.
	 */
	public function getOutput($tpl) {
		$output = $this->fetch($tpl);
		return $output;
 
	}
	
	
	/**
	* 
	* Compiles, executes, and filters a template source.
	* 
	* @access public
	* 
	* @param string $tpl The template to process; if null, uses the
	* default template set with setTemplate().
	* 
	* @return mixed The template output string, or a Savant3_Error.
	* @todo Change documentation
	*/
	public function fetch($template) 	{
		if (!is_file($template)) {
			trigger_error("$template template can't be found");
		}		
		
		// buffer output so we can return it instead of displaying.
		ob_start();
		extract($this->values);
		include $template;
		$templateContents = ob_get_contents();
		ob_end_clean();
		return $templateContents;
	}
	
}


?>