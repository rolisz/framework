<?php
require_once 'template.php';

$tpl = new template();


// Assign values to the Savant instance.
$tpl->body = "Duma lunga strica furmososrtsr Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin at nibh at arcu placerat pharetra. Etiam ac lacus non libero vulputate pulvinar. 
										Nulla dolor lorem, condimentum sit amet adipiscing at, mattis eu lectus. Etiam commodo magna quis leo ornare aliquet. Mauris porttitor dignissim 
										vulputate. Maecenas interdum, urna sed malesuada pharetra, est augue facilisis ante, lobortis vulputate velit lacus a nibh. Sed in diam tortor. 
										Vestibulum tincidunt varius auctor. Mauris at erat ac est mollis tristique. Nunc nec ligula lacus, sed accumsan dui. Nullam aliquet, sapien a 
										lacinia adipiscing, eros lacus commodo augue, id sollicitudin massa turpis vel est. Duis luctus egestas lectus, in tempus dolor hendrerit 
										tincidunt. Morbi massa sem, consequat ut porttitor at, consequat non enim. Ut porttitor rhoncus scelerisque. Etiam iaculis dictum turpis sit amet 
										hendrerit. In arcu mi, hendrerit vitae viverra non, adipiscing sit amet erat. ";
$tpl->assign(array('category'=>'personal','slug'=>'blog/framework','title'=>'rolisz presents',
	'date'=>'2010-11-11','imageSrc'=>'image.jpg','author'=>'rolisz','tags'=>array('test','text1','text2')));
$tpl->assign('comments','');
// Display a template using the assigned values.
$tpl->view('templete_test.php');

?>