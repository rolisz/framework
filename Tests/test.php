<?php 

include ('../rolisz.php');
include ('../plugin.php');
rolisz::set('BASE','framework/');

class test1 extends plugin {
	
	
}
class test2 extends plugin {

}
class test4 extends plugin {
	public function boo () {
		echo 'test4';
	}
}
class test3 extends plugin {
	
	public function boo () {
		echo 'miau';
	}
	
	public static function getDefaultMethod() {
		return 'boo';
	}
}
$test2 = new test2;
rolisz::plugin()->registerPlugin('test1','beforeMatch');
rolisz::plugin()->registerPlugin('test3','beforeMatch');
rolisz::plugin()->registerPlugin($test2,'afterMatch');
rolisz::plugin()->registerPlugin(array('test4','boo'),'afterMatch');

include ('../plugins/acl.php');
$acl = new acl('rolisz',array(
		'requesters'=>array('users'=>array('test','rolisz'),'mods'=>array('bad_mod'),'admins'),
		'resources'=>array('posts','stats','comments','users'),
		'actions'=>array('add','view','edit','delete','ban'),
		'relations'=>array(array('users','posts','view'),
						   array('users','comments','view'),
						   array('users','comments','add')
						   )
						   )
			 	);
$db = rolisz::connect('MySQLi','localhost','root','','rolisz');
$acl2= new acl('rolisz','',$db);
//$acl->init('',$db);
rolisz::plugin()->registerPlugin($acl2,'afterMatch');
$_SERVER['REQUEST_URI']='framework/test.php';
	rolisz::set('THROTTLE',1);
	rolisz::route('/blog/:arg/ala/:ar2/asd','test2','GET','blog2');
	rolisz::route('/blog/*','test1');
	rolisz::route('/test','test1');
	
	rolisz::route('/blog/:asd','test3');
	rolisz::route('/test.php','test');



	rolisz::route('/proiecs/aed[]te\*','test2');
	rolisz::route('/proiecs/:ala/bala/:korhaz/:edit/:buha','test','GET','testare');
	rolisz::route('/proiecs/asfv','test2');

	rolisz::route(' /infinity','test3|test','POST');

	rolisz::route('/infinity/ourubors/ternary','test1');
	

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
	
/* $db->dropTable('_requesters');
$db->dropTable('_actions');
$db->dropTable('_permissions');
$db->dropTable('_resources');
 */
?>