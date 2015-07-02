<?php

namespace JFrame{
	
	class View{
		protected $paths = array();
		protected $template;
		protected $templateEngine;
		private $variables = array();
		private $charset;
	
		public function render($view, $return_string=false){
			$html = null;
			switch(strtolower($this->templateEngine)){
				case 'jframe':
					$tpl = new Template();
					foreach($this->paths as $namespace=>$path){
						if($namespace == 'default'){
							foreach($path as $p){
								$tpl->addPath($p);
							}
						}else{
							$tpl->addPath($path, $namespace);
						}
					}
					$tpl->vars($this->variables);
					if($this->template) $tpl->extend($this->template);
					$html = $tpl->render($view);
					break;
				case 'twig':
					require_once(JFRAME_PATH . DS . 'vendor' . DS . 'autoload.php');
					$loader = new \Twig_Loader_Filesystem();
					foreach($this->paths as $namespace=>$path){
						if($namespace == 'default'){
							foreach($path as $p){
								$loader->addPath($p);
							}
						}else{
							$loader->addPath($path, $namespace);
						}
					}
					$twig = new \Twig_Environment($loader);
					$html = $twig->render(Util::path($view), $this->variables);		
					break;
				default:
					foreach($this->paths['default'] as $path){
						$viewPath = Util::path("$path/$view");
						if(!is_file($viewPath)) continue;
						ob_start();
						include($viewPath);
						$html = ob_get_clean();
						break;
					}
					if($html===null){
						die("view not found ($view)");
					}
			}
			if($return_string){
				return $html;
			}else{
				echo $html;
			}
			
		}
		
		public function addPath($path, $namespace=null){
			if(!is_string($path)) return false;
			if(preg_match('/[a-zA-Z\_]+/', $namespace)){
				$this->paths[$namespace] = $path;
			}else{
				$this->paths['default'][] = $path;
			}
		}
		
		public function config($name, $value='novalue'){
			if(is_string($name)){
				if($value === 'novalue'){
					if(property_exists($this, $name)) return $this->{$name};
					return null;
				}else{
					if(property_exists($this, $name)) $this->{$name} = $value;
					return $this;
				}
			}elseif(is_array($name)){
				foreach($name as $_name=>$_value){
					$this->config($_name, $_value);
				}
				return $this;
			}
		}
		
		public function vars($name, $value='novalue'){
			if(is_string($name)){
				if($value === 'novalue'){
					if(isset($this->variables[$name])) return $this->variables[$name];
					return null;
				}else{
					$this->variables[$name] = $value;
					return $this;
				}
			}elseif(is_array($name)){
				foreach($name as $_name=>$_value){
					$this->vars($_name, $_value);
				}
				return $this;
			}
			return $this;
		}
		
		public function addScript($src){
			
		}
		
		public function addStyle($src){
			
		}
		
		public function addMeta($metaData){
			
		}
	}
}

?>