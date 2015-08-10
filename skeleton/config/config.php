<?php 

return array(
	'debug' => false,
	'path' => rtrim(dirname(getcwd())),
	'application' => Vars::getFrom($_SERVER, 'HTTP_HOST', 'Application'),
	'site_url' => false,
	'site_url_ssl' => false,
	'sesson_timeout' => 30,
	'form_timeout' => 10,
	'hash' => 'application_hash',
	'enc_key' => 'my_encryption_key',
	'templateEngine' => false,
	'segmentOffset' => 0,
	'modules' => false,
	'defaultModule' => false,
);
?>