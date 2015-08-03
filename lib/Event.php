<?php 

namespace JFrame{
	
	class Event{
		private $_preventDefault = false;
		
		function __construct(Array $properties = array()){
			foreach($properties as $key=>$val){
				$this->{$key} = $val;
			}
		}
		
		public function data($property, $default=null){
			return Vars::getFrom($this, $property, $default);
		}
		
		public function preventDefault($set=null){
			if($set === null) return $this->_preventDefault;
			$this->_preventDefault = ($set) ? true : false;
		}
		
	}
}

?>