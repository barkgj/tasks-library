<?php

$loader = require __DIR__ . '/vendor/autoload.php';

// todo; fix autoloader of composer so this is not needed...
require __DIR__ . '/vendor/barkgj/datasink-library/src/datasink-entity.php';
require __DIR__ . '/vendor/barkgj/functions-library/src/filesystem.php';

require __DIR__ . '/tasks.php';

use barkgj\functions;
use barkgj\tasks;

if (false)
{
	function wp_parse_args(){return array();}
	function shortcode_parse_atts(){return array();}
	function do_shortcode(){}
}


$g=functions::create_guid();

echo "Hello world :)";
var_dump($g);
$result = tasks::createtaskinstance(1, 1, "", "", "", array("foo"=>"bar"));

var_dump($result);

die();