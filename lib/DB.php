<?php

namespace JFrame {
	use \PDO;
	use \PDOException;
	use \Config;
	use \DEBUG;
	define('NO_DB_CONNECTION', 'No Database Connection');
	
	class DB{
		private $config;
		private $query;
		private $stmt;
		private $sql;
		public $message;
		public $has_connection;
		private static $instance = array();
	
		public static function getInstance($instance='default'){
			if(isset(self::$instance[$instance])) return self::$instance[$instance];
			$config = App::getConfig('databases');
			if(!$config) return false;
			self::$instance[$instance] = new DB(Vars::getFrom($config, $instance));
			return self::$instance[$instance];
		}
	
		function __construct(Array $config=array()){
			if($config) $this->connection = $this->getConnection($config);
		}
	
		function getConnection(Array $config = array()){
			if(!$host = Vars::getFrom($config, 'host')) return false;
			if(!$database = Vars::getFrom($config, 'database')) return false;
			if(!$username = Vars::getFrom($config, 'username')) return false;
			if(!$password = Vars::getFrom($config, 'password')) return false;
			$this->config = $config;
			
			$dsn = 'mysql:dbname='.$database.';host='.$host;
			try{
				$connection = new PDO($dsn, $username, $password);
				$this->has_connection = true;
				return $connection;
			}catch(PDOException $e){
				$this->message = $e->getMessage();
				$this->has_connection = false;
				return false;
			}
		}
		
		function setQuery($query){
			$this->query = $query;
			return $this->stmt = $this->connection->prepare($query);
		}
		
		function execute($map=null){
			if(!$this->stmt) return false;
			if(!$this->stmt->execute($map)) return false;
			if(preg_match('/^INSERT/', trim($this->query))){
				if($this->rowsAffected()) return $this->connection->lastInsertId();
				return false;
			}
			if(preg_match('/^UPDATE/', trim($this->query))){
				return $this->stmt->rowCount();
			}
			return true;
		}
		function query($query, $map=null){
			try{
				$this->setQuery($query);
				$this->stmt->execute($map);
				
				if(preg_match('/^INSERT/i',trim($query))) {
					if($this->rowsAffected()) return $this->connection->lastInsertId();
					return false;
				}
				if(preg_match('/^UPDATE/i', trim($query))){
					return $this->stmt->rowCount();
				}
				return true;
			}catch(PDOException $e){
				$this->message = $e->getMessage();
				return false;
			}
				
				
			return true;
		}

		function call($sp, $in=false, $out=false, $clear_out=true){
			$sql = "CALL $sp(";
			if($in){
				if(gettype($in)=='array'){
					//$sql.= ':'.implode(', :', $in);
					$placeholders = array_fill(0, count($in), '?');
					$sql.= implode(',', $placeholders);
				}else{
					$sql.=$in;
				}
			}
			if($out){
				if(gettype($out)=='array'){
					$sql.= ', @' . implode(', @', $out);
				}else{
					$sql.=','.$out;
				}
			}
			$sql.=')';
			$this->setQuery($sql);
			
			if($in){
				$this->execute($in);
			}else{
				$this->execute();
			}
			if($out){
				if(gettype($out)=='array'){
					foreach($out as $_out){
						$rtn[$_out] = $this->loadResult("SELECT @$_out");
						if($clear_out) $this->query("SET @_$out = NULL");
					}
					return ($rtn) ? $rtn : false;
				}
			}
		}
		
		function rowsAffected(){
			return $this->stmt->rowCount();
		}
		
		function load($query=false,$dataType='object',$mode='record', $map=null){
			if($query) $this->setQuery($query);
			if(!$this->stmt) return false;
			$this->execute($map);
			$dataTypes = array('object'=>PDO::FETCH_OBJ, 'array'=>PDO::FETCH_ASSOC, 'assoc'=>PDO::FETCH_ASSOC);
			switch($mode){
				case 'result':
					try{
						$r = $this->stmt->fetch(PDO::FETCH_NUM);
						$rtn = $r[0];
					}catch(PDOException $e){
						$rtn = false;
					}
					break;
				case'list':
					try{
						$rtn = $this->stmt->fetchAll($dataTypes[$dataType]);
					}catch(PDOException $e){
						$rtn = array();
					}
					break;
				case 'record':
					try{
						$rtn = $this->stmt->fetch($dataTypes[$dataType]);
					}catch(PDOException $e){
						$rtn = ($dataType=='object')? new StdClass() : array();
					}
					break;
				default: return false;
			}
			return $rtn;
		}
		function tables(){
			$tables = $this->loadObjectList("SHOW TABLES");
			foreach($tables as $table){
				$table = (array)$table;
				$rtn[] = $table['Tables_in_'.$this->config['database']];
			}
			return isset($rtn)? $rtn : array();
		}
		
		function fields($table){
			return $this->loadObjectList("SHOW FIELDS FROM `$table`");
		}
		function columns($table){
			return $this->fields($table);
		}
		public static function table_exists($table){
			$db = self::getInstance();
			foreach($db->tables() as $tbl){ if($table==$tbl) return true; }
			return false;
		}
		public static function nextId($table){
			$db = self::getInstance();
			return $db->loadResult("SELECT (id+1) FROM $table ORDER BY id DESC LIMIT 1");
		}
		function loadResult($query=null, $map=null){
			return $this->load($query,null,'result', $map);
		}
		function loadObjectList($query=null, $map=null){
			$r = $this->load($query,'object','list', $map);
			return $r;
		}
		function loadAssocList($query=null, $map=null){
			return $this->load($query,'assoc','list', $map);
		}
		function loadObject($query=null, $map=null){
			return $this->load($query,'object','record', $map);
		}
		function loadAssoc($query=null, $map=null){
			return $this->load($query,'array','record', $map);
		}
		function loadArray($query=null, $map=null){
			$r = $this->load($query,'array','record', $map);
			if(!$r) return false;
			return array_values($r);
		}
		function inTransaction(){
			return $this->connection->inTransaction();
		}
		function beginTransaction(){
			$this->connection->beginTransaction();
		}
		function commitTransaction(){
			$this->connection->commit();
		}
		function rollBackTransaction(){
			$this->connection->rollBack();
		}
		function truncate($table){
			
		}
		function errorInfo(){
			return $this->connection->errorInfo();
		}
		function setError($msg){$this->message=$msg; return false;}
	}
}
?>