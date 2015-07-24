<?php

require_once("../jframe/lib/App.php");
$app = new JFrame\App(array(
	'debug' => true,
	'path' => dirname(__DIR__),
	'modules' => array('Site'),
	'defaultModule' => 'Site',
	'routes' => include('../config/routes.php'),

));

?>