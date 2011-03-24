<?php
include ('rolisz.php');
rolisz::connect('MySQLi','localhost','root','','rolisz');

include('db.php');

table::set('posts');
table::addRelationM2MS('posts','post_category','id','pid','category','id','cid');
table::addRelationS('posts','users','author','id');
table::addRelationS('posts','comments','pid');
//var_dump(table::findS('posts'));
$posts = new table('posts');
// $posts->addRelationM2M('post_category','id','pid','category','id','cid');
// $posts->addRelation('users','author','id');
// $posts->addRelation('comments','pid');
$posts->title='Test Post';
$posts->body='Lorem Ipsum Doloret coloret';
$posts->author=1;
//$posts->save();

$posts1 = new table('posts',1);
$test = $posts1->find('category');
//var_dump($test);
var_dump($posts1->find('comments',array('comments'=>array('parent'=>0))));
?>
