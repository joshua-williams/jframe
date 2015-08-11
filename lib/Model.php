<?php

namespace JFrame{
	
	class Model{
		
		function __construct($data = false){
			if($data) $this->set($data);
		}
		
		public function set($property, $val=null){
			switch(gettype($property)){
				case 'string':
					if(!$val) return false;
					if(!property_exists($this, $property)) return false;
					$this->{$property} = $val;
					break;
				case 'object': case 'array':
					foreach($property as $key=>$val){
						if(!property_exists($this, $key)) continue;
						$this->{$key} = $val;
					}
					break;
			}
		}
		/* depricated get() method.  se prop()*/
		public function get($property, $default=false){
			if(!property_exists($this, $property)) return $default;
			return $this->{$property};
		}
		
		function prop($property, $value='no_property_value'){
			if($value === 'no_property_value'){
				if(property_exists($this,$property)){
					return $this->{$property};
				}else{
					return null;
				}
			}
			if(!property_exists($this, $property)) return false;
			$this->{$property} = $value;
		}
		
		public function properties(){
			return get_object_vars($this);
		}
	}
}

?>