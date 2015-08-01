<?php 

require_once(PATH_JFRAME . '/lib/DB.php');

$config['host'] = ($host = $this->opt('host')) ? $host : $this->getResponse('Please enter database host');
$config['database'] = ($db = $this->opt('database')) ? $db : $this->getResponse('Please enter database name');
$config['username'] = ($username = $this->opt('username')) ? $username : $this->getResponse('Please enter username');
$config['password'] = ($password = $this->opt('password')) ? $password : $this->getResponse('Please enter password');
$config['name'] = ($name = $this->opt('name')) ? $name : $this->getResponse('Please enter connection name');

$db = new \JFrame\DB($config);
if(!$db->has_connection) $this->setError('Database setup failed. '. $db->message);

// WRITE CONFIG FILE
$content = '';
$tpl ="
		'{{name}}' => array(
			'host' => '{{host}}',
			'database' => '{{database}}',
			'username' => '{{username}}',
			'password' => '{{password}}',
		),
";
$configPath = 'config/databases.php';
if(file_exists($configPath)){
	$_config = include($configPath);
	if(is_array($_config)){
		if(in_array($config['name'], array_keys($_config) )){
			$this->setError('Connection name aready exists');
		}
		foreach($_config as $name=>$settings){
			if(!is_array($settings)) continue;
			$c = str_replace('{{name}}', $name, $tpl);
			foreach($settings as $key=>$val){
				$c = str_replace("{{".$key."}}", $val, $c);
			}
			$content.= $c;
		}
	}
}else{
	if(!file_exists('config')) mkdir('config');
}

$c = str_replace('{{name}}', $config['name'], $tpl);
foreach($config as $key=>$val){
	if($key=='name') continue;
	$c = str_replace('{{'.$key.'}}', $val, $c);
}
$content.= $c; 
$content = "<?php
	return array(
		$content	
	);	
?>";

$file = fopen($configPath, 'w');
fwrite($file, $content);
fclose($file);
$this->write('Database connection complete');
?>