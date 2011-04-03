<?php
include ('rolisz.php');
rolisz::connect('MySQLi','localhost','root','','wordpress');

include('db.php');

$posts = new table('wpb_posts','59');
$posts->addRelationM2M('wpb_term_relationships','id','object_id','wpb_terms','term_id','term_taxonomy_id');
$posts->addRelation('wpb_users','post_author','ID');
$posts->addRelation('wpb_comments','comment_post_ID');
$term_rel = new table('wpb_terms');
$term_rel->addRelation('wpb_term_taxonomy','term_id');
//$posts->save();


$test = $posts->find('wpb_posts',array('wpb_terms'=>array('wpb_term_taxonomy'=>array('taxonomy'=>'category'),'name'=>'Customization')));
var_dump($test);
echo 'yooooo';
$test = $posts->find('wpb_terms');
var_dump($test);
?>