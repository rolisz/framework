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