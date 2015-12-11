<?php 
/**
 *  example usage
 *  jframe addmodel -classname "Namespace\Model\Products
 */


if(!is_file('./config/databases.php')) $this->setError('Please run command from project root');
use \JFrame\Util;
use \JFrame\Vars;

require_once(PATH_JFRAME.'/lib/DB.php');
require_once(PATH_JFRAME.'/lib/Util.php');
require_once(PATH_JFRAME.'/lib/Vars.php');
$config = include('./config/databases.php');

if(!is_array($config) || !isset($config['default'])) $this->setError('Please set up default database connection');
$db = new \JFrame\DB($config['default']);
if(!$db->has_connection) $this->setError('Failed to connect to database');

$className = ($mod = $this->opt('classname')) ? $mod : $this->getResponse('Please enter fully qualified class name');
$tableName = ($mod = $this->opt('tablename')) ? $mod : $this->getResponse('Please enterdatabase table name');
$defaultValue = ($mod = $this->opt('default')) ? $mod : $this->getResponse('Please enter default values or leave empty for none');

$dirPath = getcwd() . dirname(Util::path('/modules/' . $className));
$namespace = dirname(Util::path($className));
$class = basename(Util::path($className));
$scriptName = $class . '.php';
if(!is_dir($dirPath)) $this->setError('Path not found: ' . $dirPath);
if(file_exists("$dirPath/$scriptName")){
	$override = $this->getResponse("Model file already exists: $dirPath/$scriptName".PHP_EOL. 'Do you want to override it? [y/n]');
	if(!$override) exit;
}

$databaseName = $db->loadResult("select database()");
$tables = $db->loadAssocList("SHOW TABLES");
$tableData = false;

foreach($tables as $table){
	if($table["Tables_in_$databaseName"] == $tableName){
		$tableData = $db->loadObjectList("DESC $tableName");
		break;
	}
}

if(!$tableData) $this->setError("table $tableName does not exist in $databaseName");

$namespace = str_replace('/', '\\', $namespace);
$class = str_replace('/','\\', $class);
switch(trim($defaultValue)){
	case "false": $value = " = false"; break;
	case "null": $value = " = null"; break;
	case "": $value = ""; break;
	default: $value = " = '$defaultValue'";
}
$txt = "<?php

namespace $namespace{
	
	class $class extends \JFrame\Model{
";
foreach($tableData as $data){
	$txt.=chr(9).chr(9) . "protected $$data->Field{$value};".chr(10);
}
$txt.="
	}
}

?>";

$file = fopen("$dirPath/$scriptName", "w");
fwrite($file, $txt);
fclose($file);

$this->write("$className model has been created");

?>