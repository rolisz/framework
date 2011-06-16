<?php

/**
 *  \class form
 * 	Helper plugin to generate and validate forms.
 * 		@package rolisz
 * 		@author Roland Szabo
 */

class form extends plugin {
	
	//Contains each input, along with information about it
	private $inputs;
	//Contains details about the form, such as action and method
	private $form;
	//Contains the form HTML
	private $string;
	//Whether or not the form was built from a database
	private $model = FALSE;
	/**
	 *  Initializes a new form. 
	 * 		@param array $form details about the form
	 * 		@param array $model optional, a table class initialized with a database table
	 */
	public function __construct($form = array(),$model = FALSE) {
		if (is_array($form)) {
			$formDef = array('method'=>'GET','action'=>$_SERVER['REQUEST_URI'],'button'=>'Send');
			$allForm = array('accept-charset','action','autocomplete','enctype','method','name','novalidate','target',
					'id','class','style','tabindex','title','acceskey','contenteditable','draggable','hidden');
			if (count(array_diff(array_keys($form),$allForm))) {
				throw new Exception('Your form contains attributes that are not allowed');
			}
			$this->form = array_merge($formDef,$form);
		}
		elseif (is_string($form)) {
			
		}
		if (is_object($model) && $model instanceof table) {
			$this->model = TRUE;
			$this->inputs = table::$tables[$model->table];
			$key = array_keys($this->inputs);
			$size = sizeOf($key);
			for ($i=0; $i<$size; $i++) {
				$this->inputs[$key[$i]] = array('type'=>$this->inputs[$key[$i]],'label'=>ucwords($key[$i]).':');
			}	
			if ($model->isHydrated()) {
				$data = $model->getData();
				for ($i=0; $i<$size; $i++) {
					$this->inputs[$key[$i]]['value'] = $data[$key[$i]];
				}	
			}
			for ($i=0; $i<$size; $i++) {
				//Check by database type
				switch ($this->inputs[$key[$i]]['type']) {
					case 'text':
					case 'blob':
						$this->inputs[$key[$i]]['type'] = 'textarea';
						break;
					case 'int':
					case 'float':
					case 'real':
					case 'double':
						$this->inputs[$key[$i]]['type'] = 'number';
						$break;   
					default:
						$this->inputs[$key[$i]]['type'] = 'text';	
				}	
				//Check by column name		
				switch ($key[$i]) {
					case 'password':
					case 'pass':
						$this->inputs[$key[$i]]['type'] = 'password';
						break;
					case 'email':
						$this->inputs[$key[$i]]['type'] = 'email';
						break;
					case 'url':
					case 'website':
						$this->inputs[$key[$i]]['type'] = 'url';
						break;
				}	
			}
		}
	}
	
	/**
	 *  Adds an input element to the form. For checkboxes that have multiple values, the name is automatically suffixed with []
	 * 		@param string $type
	 * 		@param array $options
	 * 		@return $this
	 */
	 public function input($type, $options) {
	 	$minParams = array('name');
		if (count(array_diff($minParams,array_keys($options)))) {
			throw new Exception("$type input is missing one of the following obligatory parameters:".implode(', ',$minParams));
		}
		$options['type'] = $type;
		$name = $options['name'];
		unset($options['name']);
		if (!isset($this->inputs[$name])) {
			$this->inputs [$name] = $options;
		}
		else {
			$this->inputs[$name] = array_merge($this->inputs[$name],$options);	
		}

		return $this;
	 }
	 
	 /**
	  *  Magic method for adding new inputs by calling their type directly. Throws an errors if trying to call an inexistent input type.
	  * 	@param string $name
	 * 		@param array $options
	 * 		@return $this
	  */
	public function __call($name, $options) {
		if (in_array($name,array('text','password','select','textarea','checkbox','radio','file','email',
						'url','number','range','date','time','month','week','datetime','search','color',
						'keygen','hidden'))) {
			return $this->input($name,$options[0]);			
		}	
		else {
			throw new Exception("$name is not a proper input element");
		} 
	}
	
	/**
	 *  Remove an input from the form. Useful for the forms generated automatically from databases
	 * 		@param string $name
	 */
	public function removeInput($name) {
		if (!isset($this->inputs[$name])) {
			throw new Error($name.' input element is not defined');
		}	
		unset($this->inputs[$name]);
		return $this;
	}
	
	/**
	 *  Outputs the form
	 * 		
	 */
	public function show() {
		if ($this->string=='') {
			$this->buildString();
		} 
		echo $this->string;
	}
	
	/**
	*  Returns a string containing the form.
	* 		@return string
	*/	
	public function getString() {
		if ($this->string=='') {
			$this->buildString();
		} 
		return $this->string;
	}
	 
	/**
	 *  Builds the HTML string with the inputs wrapped in unordered list items. 
	 */
	private function buildString() {
		$this->string = '<form';
		$button = $this->form['button'];
		unset($this->form['button']);
		foreach ($this->form as $key=>$property) {
			$this->string.= " {$key}='{$property}'";
		}
		$this->string.='>'.PHP_EOL.'<ul>'.PHP_EOL;
		
		$key = array_keys($this->inputs);
		$size = sizeOf($key);
		for ($i=0; $i<$size; $i++) {	
			$this->string.= "<li>";	
			if (isset($this->inputs[$key[$i]]['label']) && $this->inputs[$key[$i]]['type'] != 'hidden') {
					$this->string.= "<label for='{$key[$i]}'>{$this->inputs[$key[$i]]['label']}</label>";
			}
			if ($this->inputs[$key[$i]]['type']=='checkbox' && is_array($this->inputs[$key[$i]]['value'])) {
				$size2 = sizeOf($this->inputs[$key[$i]]['value']);
				for ($j=0; $j<$size2; $j++) {
					$this->string.= '<input name=\''.$key[$i].'[]\'';
					foreach ($this->inputs[$key[$i]] as $type=>$property) {
						if (is_string($property)) {
							$this->string.=" {$type}='{$property}'";
						}
					}
					$this->string.= ' value=\''.$this->inputs[$key[$i]]['value'][$j].'\'';
					$this->string.= " />".$this->inputs[$key[$i]]['value'][$j];
				}
			}
			elseif (!in_array($this->inputs[$key[$i]]['type'],array('textarea','select'))) {
				$this->string.= '<input name=\''.$key[$i].'\'';
				foreach ($this->inputs[$key[$i]] as $type=>$property) {
					if (is_string($property)) {
						$this->string.=" {$type}='{$property}'";
					}
				}
				$this->string.= " />";
			}	
			elseif ($this->inputs[$key[$i]]['type']=='textarea')  {
				$this->string.= '<textarea name=\''.$key[$i].'\'';
				unset($this->inputs[$key[$i]]['type']);
				foreach ($this->inputs[$key[$i]] as $type=>$property) {
					if ($type!='value' && is_string($property)) {
						$this->string.=" {$type}='{$property}'";
					}
				}
				$this->inputs[$key[$i]]['value'] = isset($this->inputs[$key[$i]]['value']) ? $this->inputs[$key[$i]]['value']:'';
				$this->string.=">{$this->inputs[$key[$i]]['value']}</textarea>".PHP_EOL;
			}
			elseif ($this->inputs[$key[$i]]['type']=='select') {
				$this->string.= '<select name=\''.$key[$i].'\'';
				unset($this->inputs[$key[$i]]['type']);
				foreach ($this->inputs[$key[$i]] as $type=>$property) {
					if ($type!='options')
						$this->string.=" {$type}='{$property}'";
				}
				$this->string.='>'.PHP_EOL;
				foreach ($this->inputs[$key[$i]]['options'] as $value=>$name) {
					$this->string.= "<option value='{$value}'>{$name}</option>".PHP_EOL;
				} 
				$this->string.="</select>".PHP_EOL;
			}
			$this->string.="</li>".PHP_EOL;	
		}
		
		
		$this->string.="<li><button name='".strtolower($button)."' id='".strtolower($button)."' value='".strtolower($button)."'>{$button}</button></li>".PHP_EOL;
		$this->string.='</ul>'.PHP_EOL.'</form>'.PHP_EOL;	
	}
	/**
	*  Validates this form
	* 	@retval true
	* 	@retval false
	*/
	public function validate() {
		if ($_SERVER['REQUEST_METHOD'] != $this->form['method']) {
			throw new Exception('This form was not sent with the proper HTTP method.');
			return false;
		}
		if ($_SERVER['REQUEST_METHOD']=='GET') {
			$inputs = $_GET;
		}		
		if ($_SERVER['REQUEST_METHOD']=='POST') {
			$inputs = $_POST;
		}		
		$key = array_keys($this->inputs);
		$size = sizeOf($key);
		for ($i=0; $i<$size; $i++) {
			if (isset($inputs[$key[$i]]) || isset($this->inputs[$key[$i]]['optional'])) {
				if (isset($this->inputs[$key[$i]]['pattern'])) {
					if (is_string ($this->inputs[$key[$i]]['pattern']) 
					&& !preg_match('/^'.$this->inputs[$key[$i]]['pattern'].'$/',$inputs[$key[$i]])) {
						throw new Exception ($key[$i].' did not validate, having this value: '.$inputs[$input['name']]);
					}
					elseif (is_callable($this->inputs[$key[$i]]['pattern']) && !$this->inputs[$key[$i]]['pattern']($inputs[$key[$i]])) {
						throw new Exception ($key[$i].' did not validate, having this value: '.$inputs[$key[$i]]);
					}
				}
			}
			else {
				throw new Exception ($key[$i].' parameter not sent');
			}
		}
		return true;
	}
	
	/**
	 *  Return the plugin name
	 */
	public function getName() {
		return get_class($this);
	}
	
	/**
	 *  Default method is getString()
	 */
	public static function getDefaultMethod() {
		return 'getString';
	}
}
?>
