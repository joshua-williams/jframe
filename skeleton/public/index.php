<?php

require_once("../vendor/swfx/jframe/lib/App.php");
$app = new JFrame\App(array(
	'debug' => true,
	'path' => dirname(__DIR__),
	'modules' => array('Site'),
	'default_module' => 'Site',
	'routes' => include('../config/routes.php'),

));

$app->init();
?>