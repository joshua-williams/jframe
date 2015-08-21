<?php 

namespace JFrame{
	
	class Form{
		private $attributes = array();
		private $properties = array();
		private $fields = array();
		private $fieldsLoaded;
		/**
		 * 
		 * @desc Will gets/sets form property. 
		 * When prop is string and value is empty will return property value.
		 * When prop is string and value is set it will set single form property
		 * When prop is array it will set each array element as property.
		 * @param string $prop
		 * @param string $value
		 */
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
		
		public function loadFields(){
			if($this->fieldsLoaded) return false;
			if(method_exists($this, 'fields')){
				$formFields = $this->fields();
				if(is_array($formFields)) $this->addFields($formFields);
			}
			$this->fieldsLoaded = true;
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
		
		public function getField($name=null){
			$this->loadFields();
			if(!is_string($name)) return false;
			foreach($this->fields as $field){
				if($field->attr('name') == $name) return $field;
			}
			return false;
		}
		/**
		 * @param string $names An array of field names. Will return FormFields who's name matches.
		 * @return If name param is not passed will return array of FormField objects. If name matches a FormField name attribute will return FormField or false.
		 */
		public function getFields(Array $names = array()){
			if(!$names) return $this->fields;
			foreach($names as $name){
				$field = $this->getField($name);
				if($field) $fields[] = $field;
			}
			return isset($fields) ? $fields : array();
		}
		
		public function render(){
			if(!$this->fieldsLoaded) $this->loadFields();
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
			return $html;
		}
		
		public function renderHiddenFields(){
			$app = App::instance();
			// set form token to prevent attacks
			$encKey = ($k = $app->config('enc_key')) ? $k : ' ';
			$tokenName = md5($app->config('hash') . "submit");
			$formClass = get_class($this);
			$time = time();
			$sessionKey = md5($formClass);
			$json = json_encode(array('form'=>$formClass, 'time'=>$time));
			$app->session->set("token.form.$sessionKey", array('form'=>$formClass,'time'=>$time));
			$tokenValue = Util::encrypt($encKey, $json);
			$html = "<input type='hidden' name='$tokenName' value='$tokenValue' />";
			// set default return value
			$return = ($r = $this->prop('return')) ? $r : $app->config('site_url');
			$html.="<input type='hidden' name='return' value='$return'>";
			
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
				$label = ($l = $field->label()) ? "<label>$l</label>" : '';
				$html.="<div>$label" . $field->render() . "</div>"; 
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
			// automatically set the enctype if file form field exists and if not enctype is not explicitly defined
			if($this->hasFile() && !isset($this->attributes['enctype'])){
				$this->attributes['enctype'] = 'multipart/form-data';
			}
			// set default method as post if not explicitly defined
			if(!isset($this->attributes['method'])) $this->attributes['method'] = 'post';
			// set the action to the site url
			$this->attributes['action'] = App::instance()->config('site_url');
			
			foreach($this->attributes as $key=>$val){
				$attributes[] = $key . '="' . str_replace('"','\\"', $val) . '"';
			}
			
			return implode(' ', $attributes);
		}
		
		private function hasFile(){
			foreach($this->fields as $field){
				if($field->type() == 'file') return true;
			}
			return false;
		}		
		
		public function action(){
			
		}
	}
	
	
	/**
	 * @desc Form Field Class
	 */
	class FormField{
		private $type;
		private $label;
		private $value;
		private $options;
		private $attributes = array();
		
		function __construct(Array $prop){
			$this->type = Vars::getFrom($prop, 'type');
			$this->label = Vars::getFrom($prop, 'label');
			$this->value = Vars::getFrom($prop, 'value');
			$this->options = Vars::getFrom($prop, 'options');
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
				case 'submit':
				case 'radio': return $this->renderText(); break;
				case 'textarea': return $this->renderTextArea(); break;
				case 'dropdown': return $this->renderDropdown(); break;
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
					$options[] = $o;
				}
				if(!$options) return false;
				$this->options = $options;
			}
			return true;
		}
		
		private function renderAttributes(){
			$attributes = array();
			foreach($this->attributes as $key=>$val){
				$attributes[] = $key . '="' . str_replace('"','\\"', $val) . '"';
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
		
		private function renderDropdown(){
			$html = "<select " . $this->renderAttributes() . ">";
			foreach($this->options as $option){
				$html.= "<option value='" . $option['value'] . "'>" . $option['label'] . "</option>";
			}
			$html.="</select>";
			return $html;
		}
	}
}
?>