<?php

require_once 'C:\wamp\www\framework\router.php';

/**
 * Test class for router.
 * Generated by PHPUnit on 2011-03-31 at 13:10:08.
 */
class routerTest extends PHPUnit_Extensions_OutputTestCase
{
    /**
     * @var router
     */


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    	$_SERVER['REQUEST_METHOD']='GET';
		router::route('/blog/:arg',function($arg) {
			echo 'Argument: '.$arg;
		},'GET','blog');
		router::route('/blog/*',function() {
			echo 'CatchAll';
		});
		router::route('/blog/arg',function() {
			echo 'NoArg';
		});
		router::route('/test.php',function() {
			echo 'test.php';
		});	
		router::route('/proiecs/aed[]te\*',function() {
			echo '/proiecs/aed[]te\*';
		});
		router::route('/proiecs/:ala/bala/:korhaz/:edit/:buha',function($arg1, $arg2, $arg3) {
			echo 'proiecs '.$arg1.$arg2.$arg3;
		},'GET','proiecs');
		router::route('/proiecs/asfv',function() {
			echo '/proiecs/asfv/';
		});
		router::route(' /infinity',function() {
			echo 'infinity';
		},'GET','infinity');
		router::route('/infinity/ourubors/ternary',function() {
			echo '/infinity/ourubors/ternary';
		},'GET','infinity2');
		router::set('BASE','framework');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @todo Implement testRoute().
     */
    public function testRoute()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * 
	 * @dataProvider urlProvider
     */
     
     public function testRun($url,$expected) {
     	$_SERVER['REQUEST_URI']=$url;
		router::run();
		$this->expectOutputString($expected);
     }
	 
	 public function urlProvider() {
	 	return array(
		 	array('framework/blog/:arg','Argument: :arg'),
			array('framework/blog/Whatever/Catchall','CatchAll'),
			array('framework/blog/arg','NoArg'),
			array('framework/test.php','test.php'),	
			array('framework/proiecs/aed[]te\*','/proiecs/aed[]te\*'),
			array('framework/proiecs/:ala/bala/:korhaz/:edit/:buha','proiecs :ala:korhaz:edit'),
			array('framework/proiecs/asfv','/proiecs/asfv/'),
			array('framework/proiecs/asfv/','/proiecs/asfv/'),
			array('framework /infinity','infinity'),
			array('framework/infinity/ourubors/ternary','/infinity/ourubors/ternary')
		);
		
	 }

    /**
     * @dataProvider Urls
     */
    public function testUrlFor($name, $data,$expect)
    {
       $this->assertEquals($expect,router::urlFor($name,$data));
    }
	
	public function Urls() {
		return array(
			array('blog',array('arg'=>'123'),'framework/blog/123'),
			array('blog',array('arg'=>'testare'),'framework/blog/testare'),
			array('blog',array('arg'=>'complex/url'),'framework/blog/complex/url'),
			array('proiecs',array('ala'=>'arg1','korhaz'=>'arg2','edit'=>'arg3','buha'=>'arg4'),'framework/proiecs/arg1/bala/arg2/arg3/arg4'),
			array('proiecs',
				array('ala'=>'what/to','korhaz'=>'do/with','edit'=>'complex/arguments?','buha'=>'and\weird/slashes'),
					'framework/proiecs/what/to/bala/do/with/complex/arguments?/and\weird/slashes'),
			array('infinity',array(),'framework/infinity'),
			array('infinity2',array(),'framework/infinity/ourubors/ternary')
		);
	}
}
?>