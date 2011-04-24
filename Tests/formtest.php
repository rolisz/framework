<?php
include ('../rolisz.php');
$form1 = new form(array('id'=>'test1'));
$form1->input('text',array('name'=>'user','pattern'=>'.{6,}'))
	  ->input('password',array('name'=>'pass','pattern'=>'[0-9]{3,5}'))
	  ->input('textarea',array('name'=>'textareaname','value'=>'value'))
	  ->input('select',array('name'=>'selectname','options'=>array('o'=>'opt','n'=>'noopt')));
$form2 = new form(array('id'=>'test2','action'=>'testare'));
$form2->input('text',array('name'=>'user'))
	  ->input('password',array('name'=>'pass'))
	  ->input('radio',array('name'=>'radio'))
	  ->textarea(array('name'=>'textareaname','value'=>'value'))
	  ->input('select',array('name'=>'selectname','options'=>array('o'=>'opt','n'=>'noopt')));
//var_dump($form1->getString());		
//var_dump($form2->getString());
$form1->show();
$form2->show();
//$form1->validate();

$posts = rolisz::connect('MySQLi','localhost','root','','rolisz');
$posts = rolisz::table('posts',1);
$form3 = new form(array('id'=>'test3'),$posts);
//$form3->input('text',array('name'=>'author','value'=>'rolisz'));
var_dump($form3->getString());
$form3->show();
?>
