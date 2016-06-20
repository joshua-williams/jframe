<?php 

namespace JFrame{
	
	/**
	 * @desc Form Field Class
	 */
	class FormField{
		private $type;
		private $label;
		private $value;
		private $options;
		private $attributes = array('value'=>false, 'class'=>false, 'name'=>false);
		private $parent = array();
		
		function __construct(Array $prop){
			$this->type = Vars::getFrom($prop, 'type');
			$this->label = Vars::getFrom($prop, 'label');
			$this->value = Vars::getFrom($prop, 'value');
			$this->options = Vars::getFrom($prop, 'options');
			foreach($prop as $attr=>$val){
				switch($attr){
					case 'type': case 'label': break;
					case 'parent':
						if(!is_array($val)) continue;
						foreach($val as $parentKey=>$parentVal){
							if(is_array($parentVal) || is_object($parentVal)) continue;
							$this->parent[$parentKey] = $parentVal;
						}
						break;
					default: $this->attr($attr, $val);
				}
			}
			//$attr = Vars::getFrom($prop, 'attr', Vars::getFrom($prop, 'attributes'));
		}
		public function type(){
			return $this->type;
		}
	
		public function label(){
			return $this->label;
		}
	
		public function value($value='NO_VALUE'){
			if($value === 'NO_VALUE') $this->value;
			$this->value = $value;
		}
		
		public function parent(){
			return $this->parent;
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
		
		public function removeAttr($attr){
			if(!isset($this->attributes[$attr])) return false;
			unset($this->attributes[$attr]);
		}
		
		public function render(){
			
			switch($this->type){
				case 'text':
				case 'password':
				case 'hidden':
				case 'file':
				case 'checkbox':
				case 'submit': return $this->renderText(); break;
				case 'textarea': return $this->renderTextArea(); break;
				case 'dropdown': return $this->renderDropdown(); break;
				case 'radio': return $this->renderRadio(); break;
			}
		}
	
		function isValid(){
			$fieldTypes = array('text','email','password','number','textarea','radio','checkbox','dropdown', 'file','hidden','submit');
			if(!in_array($this->type, $fieldTypes)) return false;
			if($this->type == 'dropdown'){
				if(!$this->options || !is_array($this->options)) return false;
				$options = array();
				foreach($this->options as $option){
					if(!isset($option['value']) && !isset($option['label'])) continue;
					$o['value'] = Vars::getFrom($option,'value', $option['label']);
					$o['label'] = Vars::getFrom($option, 'label', $option['value']);
					$o['selected'] = Vars::getFrom($option, 'selected');
					$o['attributes'] = array();
					$attrs = Vars::getFrom($option, 'attributes');
					if(is_array($attrs) || is_object($attrs)){
						$attrs = (array) $attrs;
						foreach($attrs as $key=>$val){
							if(is_array($val) || is_object($val)) continue;
							$o['attributes'][$key] = $val;
						}
					}
					$options[] = $o;
				}
				if(!$options) return false;
				$this->options = $options;
			}
			return true;
		}
	
		private function renderAttributes(Array $source = array()){
			$attributes = array();
			foreach($source as $key=>$val){
				$attributes[] = $key . '="' . str_replace('"','\\"', $val) . '"';
			}
			return implode(' ', $attributes);
		}
	
		private function renderLabel(){
			return "<label>$this->label</label>";
		}
	
		private function renderValue(){
			return ($this->attributes['value']) ? ' value=\''.$this->attributes['value'] . '\'': '';
		}
	
		private function renderText(){
			return "<input type='$this->type' " . $this->renderAttributes($this->attributes) . $this->renderValue() . "/>";
				
		}
	
		private function renderTextarea(){
			return "<textarea " . $this->renderAttributes($this->attributes) . ">" . $this->attributes['value'] . "</textarea>";
		}
		
		private function renderRadio(){
			$html = '';
			$name = $this->attr('name');
			foreach($this->options as $o){
				$checked = ($this->attr('value') == $o['value']) ? 'checked':'';
				$value = $o['value']; $label = $o['label'];
				$html.="<input type='radio' value='$value' name='$name' $checked> <span>$label</span>";
			}
			return $html;
		}
	
		private function renderDropdown(){
			$html = "<select " . $this->renderAttributes($this->attributes) . ">";
			foreach($this->options as $option){
				$attributes = $this->renderAttributes($option['attributes']);
				$attributes.= ($this->attributes['value'] === $option['value']) ? ' selected' : "";
				$html.= "<option $attributes value='" . $option['value'] . "'>" . $option['label'] . "</option>".chr(10);
			}
			$html.="</select>";
			return $html;
		}
	}
}

?>