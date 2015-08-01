<?php

$path = getcwd();
$skeleton = PATH_JFRAME . '/skeleton';
$dir = opendir($skeleton);
$override = $this->opt_exists('f');

while(($file = readdir($dir)) !== false){
	if(preg_match('/^[\.]+$/', $file)) continue;
	if(file_exists($file)){
		if($override){
			exec("rm -rf $file ; cp -r $skeleton/$file .");
		}else{
			$this->setError('Install failed. ' . $path . '/' . $file . ' already exists. To override existing directories pass the -f option.');
		}
	}else{
		exec("cp -r $skeleton/$file .");
	}
}	

$dbRequired = $this->getResponse('Does this application require database? [y/n]');
if($dbRequired == 'y'){
	$this->dbsetup();
}

$this->write('Installation complete');
?>