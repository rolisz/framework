<?php 

include ('rolisz.php');

	rolisz::set('THROTTLE',1);
	rolisz::route('/blog','test|test1');
	rolisz::route('/blog/@arg','test|test2');

	rolisz::route('/test.php','test');

	rolisz::route('/blog/@buha','test1','GET|POST');

	rolisz::route('/proiecs/aed[]te\*','test2');
	rolisz::route('/proiecs/','test2');
	rolisz::route('/proiecs/asfv','test2');

	rolisz::route(' /infinity','test3|test','POST');

	rolisz::route('/infinity/ourubors/ternary','test1');
	
	$routes=rolisz::get('ROUTES');

	rolisz::run();
	
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
	}
	

?>