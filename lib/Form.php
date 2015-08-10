<?php 

namespace JFrame{
	
	class Form{
		private $attributes = array();
		private $properties = array();
		private $fields = array();

		public function prop($prop=null, $value='no_val'){
			if(!$prop){
				return $this->properties();
			}elseif(is_string($prop)){
				if($value == 'no_val') return Vars::getFrom($this->properties, $prop);
				if(!is_string($value)) return false;
				$this->properties[$prop] = $value;
			}elseif(is_array($prop)){
				foreach($prop as $key=>$val){
					if(!is_string($key)) continue;
					if(!is_string($val)) continue;
					$this->properties[$key] = $val;
				}
			}
		}
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
		
		public function getField($name){
			if(!is_string($name)) return false;
			foreach($this->fields as $field){
				if($field->attr('name') == $name) return $field;
			}
			return false;
		}
		
		public function addField(Array $prop){
			$field = new FormField($prop);
			if($field->isValid()) $this->fields[] = $field;
		}
		
		public function addFields(Array $fields){
			foreach($fields as $field){
				if(!is_array($field)) continue;
				$this->addField($field);
			}
		}

		
		public function render(){
			$html = "<form " . $this->renderAttributes() . ">".chr(10);
			$html.= $this->renderHiddenFields();
			$fields = array();
			switch(strtolower($this->prop('type'))){
				case 'table': $html.= $this->renderTable(); break;
				case 'div': $html.= $this->renderDiv(); break;
				case 'ol': $html.= $this->renderList('ol'); break;
				default: $html.= $this->renderList();
			}
			$html.="</form>";
			die($html);
		}
		
		public function renderHiddenFields(){
			$html = '';
			foreach($this->fields as $field){
				if($field->type() != 'hidden') continue;
				$html.= $field->render();
			}
			return $html;
		}
		
		private function renderList($type=null){
			$tag = ($type == 'ol') ? 'ol' : 'ul';
			$html = "<$tag>";
			foreach($this->fields as $field){
				if($field->type() == 'hidden') continue;
				$html.="<li><label>" . $field->label() . "</label>" . $field->render() . "</li>";
			}
			return $html.="</$tag>";
		}
		
		private function renderDiv(){
			$html = '';
			foreach($this->fields as $field){
				if($field->type() == 'hidden') continue;
				$html.="<div><label>" . $field->label() . "<label>" . $field->render() . "</div>"; 
			}
			return $html;
		}
		
		private function renderTable(){
			$fields = '';
			foreach($this->fields as $field){
				if($field->type() == 'hidden') continue;
				if(!$field->isValid()) continue;
				$fields.="<tr><td>" . $field->label() . "<td><td>" . $field->render() . "</td></tr>".chr(10);
			}
			 return "<table>".chr(10) . "<tbody>" . chr(10) . $fields . "</tbody>" . chr(10) . "</table>";
		}
		
		private function renderAttributes(){
			$attributes = array();
			if($this->hasFile() && !isset($this->attributes['enctype'])){
				$this->attributes['enctype'] = 'multipart/form-data';
			}
			foreach($this->attributes as $key=>$val){
				
				$attributes[] = $key . '="' . addslashes($val) . '"';
			}
			return implode(' ', $attributes);
		}
		
		private function hasFile(){
			foreach($this->fields as $field){
				if($field->type() == 'file') return true;
			}
			return false;
		}		
	}
	
	
	/**
	 * @desc Form Field Class
	 */
	class FormField{
		private $type;
		private $label;
		private $value;
		private $attributes = array();
		
		function __construct(Array $prop){
			$this->type = Vars::getFrom($prop, 'type');
			$this->label = Vars::getFrom($prop, 'label');
			$this->value = Vars::getFrom($prop, 'value');
			foreach($prop as $attr=>$val){
				if($attr === 'type') continue;
				$this->attr($attr, $val);
			}
			//$attr = Vars::getFrom($prop, 'attr', Vars::getFrom($prop, 'attributes'));
		}
		public function type(){
			return $this->type;
		}
		
		public function label(){
			return $this->label;
		}
		
		public function value(){
			return $this->attr('value');
		}
		
		public function attr($attr=null, $value='no_val'){
			if(!$attr) return $this->attributes;
			if(is_string($attr)){
				if($value === 'no_val'){
					return Vars::getFrom($this->attributes, $attr);
				}
				if(!is_string($value)) return false;
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
				case 'hidden':
				case 'file':
				case 'radio': return $this->renderText(); break;
				case 'textarea': return $this->renderTextArea(); break;
			}
		}
		
		function isValid(){
			$fieldTypes = array('text','password','number','textarea','radio','checkbox','file','hidden');
			if(!in_array($this->type, $fieldTypes)) return false;
			return true;
		}
		
		private function renderAttributes(){
			$attributes = array();
			foreach($this->attributes as $key=>$val){
				$attributes[] = $key . '="' . addslashes($val) . '"';
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
			return "<input type='$this->type' " . $this->renderAttributes() . $this->renderValue() . "/>";
			
		}
		
		private function renderTextarea(){
			return "<textarea " . $this->renderAttributes() . ">" . $this->renderValue() . "</textarea>";
		}
		
		
	}
}
?>