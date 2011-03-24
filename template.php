<?php
	/**
		\class template
		Provides basic templating capabilites
			@package rolisz
			@author Roland Szabo
	
	**/
include_once ('rolisz.php');

class filter {

	
}
class template extends base {

	/**
		Vars
	**/
	private $values = array();
	private $filters = array();

	public function __construct($filters = array()) {
	
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
	
	private function checkCompile($template) {
		if (file_exists('temp/'.md5($template)) && (filemtime($template)-filemtime('temp/'.md5($template)))<0 )
			return true;
		return false;
	}
	
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
		
		$template_code = preg_replace('/{{ (.+?) }}/','<?php echo $this->escape($$1); ?>',$template_code);
		
		if (!is_dir('temp'))
			mkdir('temp');
		file_put_contents('temp/'.md5($template),$template_code);
	}
}


?>