<?php 

include ('rolisz.php');

	rolisz::set('THROTTLE',1);
	rolisz::route('/blog/:arg/ala/:ar2/asd','test2','GET','blog2');
	rolisz::route('/blog/*','test1');

	rolisz::route('/blog/:asd','test3');
	rolisz::route('/test.php','test');



	rolisz::route('/proiecs/aed[]te\*','test2');
	rolisz::route('/proiecs/:ala/bala/:korhaz/:edit/:buha','test','GET','testare');
	rolisz::route('/proiecs/asfv','test2');

	rolisz::route(' /infinity','test3|test','POST');

	rolisz::route('/infinity/ourubors/ternary','test1');
	
	echo '<a href="'.rolisz::urlFor('blog2',array('arg'=>'miau','ar2'=>'cutzu')).'">blog2</a>';
	echo rolisz::urlFor('testare',array('ala'=>'miau','korhaz'=>'cutzu','edit'=>'buuulaaaan','buha'=>'ezaz'));
	$routes=rolisz::get('ROUTES');
	//var_dump($routes);
	rolisz::run();
	
	function test($arg='def') {
	echo 'works ';
	echo $arg;
	}
	function test1() {
	echo 'test1';
	}
	function test2($arg1, $arg2='def') {
	echo 'test2 ';
	echo $arg1.$arg2;
	}
	function test3($arg1) {
	echo 'test3'.$arg1;
	}
	

?>