<?php 

/*include ('rolisz.php');

	rolisz::set('THROTTLE',1);
	rolisz::route('GET /blog','test|test1');
//	rolisz::route('GET /blog/@arg','test|test2');

//	rolisz::route('GET /test.php','test');

//	rolisz::route('GET|POST /blog/@buha','test1');

//	rolisz::route('GET /proiec#$!@%$\'"s/aed[]te\*','test2');

//	rolisz::route('POST /infinity','test3|test');
	echo '<br/>';

	rolisz::route('/infinity/beyond/ternary','test1');
	
	
	//rolisz::run();
	
	function test($arg='def') {
	echo 'works';
	echo $arg;
	}
	function test1() {
	echo 'works1';
	}
	function test2() {
	echo 'works';
	}
	function test3() {
	echo 'works';
	}*/
	
$array= array('ceva','level3','ternary','tz');


$array=recursify($array);
function recursify ($array) {
	if (count($array)==0) 
		return '';
	$returnarray[$array[0]]=recursify(array_slice($array,1));
	return $returnarray;
	
}
var_dump($array);

?>