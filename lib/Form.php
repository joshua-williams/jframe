<?php 

namespace JFrame{
	
	class Form{
		private $attributes = array();
		private $fields = array();
		
		public function attr($attr=null, $value=null){
			if(!$attr) return $this->attributes;
			if(is_string($attr)){
				$this->attributes[$attr] = $value;
			}elseif(is_array($attr)){
				foreach($attr as $_attr=>$_value){
					$this->attr($_attr, $_value);
				}
			}
		}
		
		public function addField(Array $prop){
			if(!$attr['label'] = Vars::getFrom($prop, 'label')) return false;
			if(!$type = strtolower(Vars::getFrom($prop, 'type'))) return false;
			if(!in_array)
		}
		
		public function addFields(Array $fields){
			
		}
	}
	
	
	
	/*
	 * @desc - Form Field Class
	 */
	class FormField{
		private $attributes = array();
		private $type;
		private $label;
		private $value;
		
		public function attr($attr=null, $value=null){
			if(!$attr) return $this->attributes;
			if(is_string($attr)){
				$this->attributes[$attr] = $value;
			}elseif(is_array($attr)){
				foreach($attr as $_attr=>$_value){
					$this->attr($_attr, $_value);
				}
			}
		}
		
		public function render(){
			switch($this->type){
				case 'text':
				case 'password': 
				case 'radio': return $this->renderText(); break;
				case 'textarea': return $this->renderTextArea(); break;
			}
		}
		private function renderAttributes(){
			$attributes = array();
			foreach($this->attributes as $key=>$val){
				$attributes[] = $key . '="' . $val . '"';
			}
			return implode(' ', $attributes);
		}
		
		private function renderLabel(){
			return "<label>$this->label</label>";
		}
		
		private function renderValue(){
			return ($this->value) ? ' '.$this->value : '';
		}
		
		private function renderText(){
			return $this->renderLabel() . "<input type='text' " . $this->attributes() . $this->renderValue() . "/>";
			
		}
		
		private function renderTextarea(){
			return $this->renderLabel() . "<textarea " . $this->attributes() . ">" . $this->renderValue() . "</textarea>";
		}
		
		
	}
}
?>