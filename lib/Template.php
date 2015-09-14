<?php

namespace JFrame{
	
	class Template{
		private $paths = array('default'=>array());
		private $parent;
		private $view;
		private $variables = array();
		
		public function render($view, $print=false){
			$html = $this->loadView($view);
			$this->loadIncludes($html);
			$this->joinParent($html);
			$this->parseIfStatements($this->parent, $this->variables);
			$this->replaceVariables($this->parent, $this->variables);
			if($print) echo $this->parent;
			return $this->parent;
		}
		
		private function joinParent($html){
			/*
			 * @desc - You can extend a template with
			 * $tpl->extend($view) or in the template
			 * <template:extend view="my/view.html"></template:extend/> or
			 */
			if($this->parent){
				$this->mergeBlocks($this->parent, $html);
				
			}else{
				$pattern = '/<template:extend view=(?:\'|\")([\w\@\.\_\/]+)(?:\'|\")>(.*)?<\/template:extend>/s';
				if(preg_match($pattern, $html, $match)){
					$this->extend($match[1]);
					$this->mergeBlocks($this->parent, $match[2]);
				}else{
					$this->parent = $html;
				}
				
			}
		}
		
		private function mergeBlocks(&$parent, $html){
			$parentBlocks = $this->blocks($this->parent);
			$viewBlocks = $this->blocks($html);
			if($parentBlocks){
				foreach($parentBlocks as $title=>&$block){
					if($viewBlocks && isset($viewBlocks[$title])){
						$block['innerHTML'].= $viewBlocks[$title]['innerHTML'];
					}
				}
				foreach($parentBlocks as $title=>&$block){
					$parent = str_replace($block['outterHTML'], $block['innerHTML'], $parent);
				}
			}
		}
		private function blocks($html){
			$blocks = array();
			$pattern = '/<block:(\w+)>(.*?)<\/block(:\w+)?>/s';
			if(preg_match_all($pattern, $html, $match)){
				for($a=0; $a<count($match[0]); $a++){
					$blocks[$match[1][$a]] = array(
						'title' => $match[1][$a],
						'innerHTML' => $match[2][$a],
						'outterHTML' => $match[0][$a],
					);
				}
			}
			if(preg_match_all('/<block:(\w+)\/>/', $html, $match)){
				for($a=0; $a<count($match[0]); $a++){
					$blocks[$match[1][$a]] = array(
						'title' => $match[1][$a],
						'innerHTML' => false,
						'outterHTML' => $match[0][$a]
					);
				}
			}
			return $blocks;
		}
		
		private function parentBlock($title){
			$pattern = "/<block:". preg_quote($title) . ">(.*?)<\/block:" . preg_quote($title) . ">/s";
			if(preg_match($pattern, $this->parent, $match)){
				return array('title'=>$title,'innerHTML'=>$match[1],'outterHTML'=>$match[0]);
			}elseif(preg_match("/<block:". preg_quote($title) . ">(.*?)<\/block>/s", $this->parent, $match)){
				return array('title'=>$title,'innerHTML'=>$match[1],'outterHTML'=>$match[0]);
			}elseif(preg_match("/<block:" . preg_quote($title) . "\/>/", $this->parent, $match)){
				return array('title'=>$title, 'innerHTML'=>false, 'outterHTML'=>false);
			}
			return false;
		}
		public function view($view=null){
			if($view === null) return $this->view;
			if(!is_string($view)) return false;
			$this->view = $view;
		}
		
		private function loadView($view){
			// Parse namespace syntax eg: @directoryNamespace/path/to/view.php
			$pattern = '/^\@(\w+)\//';
			if(preg_match($pattern, $view, $match)){
				if(!isset($this->paths[$match[1]])) return false;
				$path = $this->paths[$match[1]] . DS . preg_replace($pattern, '', $view);
				if(!is_file($path)) return false;
				foreach($this->variables as $key=>$val){
					${$key} = $val;
				}
				ob_start();
				include($path);
				return ob_get_clean();
			}else{
				foreach($this->paths['default'] as $path){
					$viewPath = $path . DS . $view;
					if(file_exists($viewPath)){
						foreach($this->variables as $key=>$val){ 
							${$key} = $val;
						}
						ob_start();
						include($viewPath);
						return ob_get_clean();
					}
				}
			}
		}
		
		public function loadIncludes(&$html){
			$pattern = '/<template:include view=(?:\'|\")(.*)?(?:\'|\")\/>/';
			if(preg_match_all($pattern, $html, $match)){
				for($a=0; $a<count($match[0]); $a++){
					if($content = $this->loadView($match[1][$a])){
						$html = str_replace($match[0][$a], $content, $html);
						//die("<xmp>$html");
					}else{
						die('Template error: Include not found '.$match[1][$a]);
					}
				}
			}
		}
		public function extend($view){
			if(!is_string($view)) return false;
			if(!$this->parent = $this->loadView($view)){
				die("Template error: Failed to extend view ($view)");
			}
			$this->loadIncludes($this->parent);
			return $this;
		}
		
		
		public function addPath($path, $namespace=null){
			if(!is_string($path)) return $this;
			if($namespace === null){
				if(in_array($path, $this->paths['default'])) return $this;
				$this->paths['default'][] = $path;
			}elseif(is_string($namespace)){
				$this->paths[$namespace] = $path;
			}
			return $this;
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

		public function replaceVariables(&$markup=false, $vars=false){
			$vars = ($vars && is_array($vars)) ? $vars : $this->variables;
			$this->replaceObjectVariables($markup, $vars);
			$pattern = '/\<\:([\w]+)(>|\/>?)/';
			if(preg_match_all($pattern, $markup, $matches)){
				foreach($matches[1] as $idx=>$key){
					$close = $matches[2][$idx];
					if(isset($vars[$key]) && (is_string($vars[$key]) || is_numeric($vars[$key]))){
						$markup = str_replace($matches[0][$idx], $vars[$key], $markup);
					}else{
						$markup = str_replace($matches[0][$idx], '', $markup);
					}
				}
			}
			return $markup;
		}

		private function replaceObjectVariables(&$markup=false, $vars=false){
			$pattern = '/<:(\w+\.\w+(\.\w+)?(\.\w+)?(\.\w+)?(\.\w+)?(\.\w+)?(\.\w+)?(\.\w+)?(\.\w+)?)(?:\/)?>/';
			if(preg_match_all($pattern, $markup, $matches)){
				for($a=0; $a<count($matches[0]); $a++){
					$str = $matches[1][$a];
					$val = Vars::getFrom($vars, $str);
					$markup = str_replace($matches[0][$a], $val, $markup);
				}
			}
		}
		
		public function parseIfStatements(&$markup, $vars){
			$ifStatements = $this->getIfStatementTags($markup);
			if(!$ifStatements) return false;
			
			foreach($ifStatements as $if){
				$pattern = '/<if:(!)?([\w\.]+)(?:[\s]+)?(<|>|==|!=)?(?:[\s]+)?((?:\'|")[\w\.]+(?:\'|"))?>/';
				/*
				 * opening tag regex array
				 * 0 - complete match
				 * 1 - negative operation (optional) !
				 * 2 - subject (what is being testes)
				 * 3 - conditional <, >, ==, !=, <=, >=
				 * 4 - test (what subject is being tested against)
				 * index 0-2 are for sure. the reset you have to do isset()
				 */
				if(preg_match($pattern, $if->tag, $match)){
					//die('<xmp>'.print_r($match,1));
					$var = Vars::getFrom($this->variables, $match[2]);
					$orgBlock = substr($markup, $if->start, $if->length);
					$start = $if->start + strlen($match[0]);
					$length = $if->length - (strlen($if->tag) +5);
					$newBlock = substr($markup, $start, $length);
					
					if($match[1] == '!'){
						if($var){
							$markup = str_replace($orgBlock, '', $markup);
						}else{
							$markup = str_replace($orgBlock, $newBlock, $markup);
						}
					}else{
						if($var){
							$markup = str_replace($orgBlock, $newBlock, $markup);
							
							$this->parseIfStatements($markup, $vars);
						}else{
							$markup = str_replace($orgBlock,'', $markup);
						}
					}
				}
				$this->parseIfStatements($markup, $vars);
				break;
			}
			return $markup;
		}

		private function getIfStatementTags($markup){
			$pattern1 = '/<if:(!)?([\w\.]+)(?:[\s]+)?(<|>|==|!=)?(?:[\s]+)?((?:\'|")[\w\.]+(?:\'|"))?>/';
			$pattern2 = '/<\/if>/';
			//$pattern2 = '/<\/if(?::[\w]+)?\>/'; // this pattern will match both closing tags </if> and </if:varname> the rest needs to be updated for this support
			preg_match_all($pattern1, $markup, $oMatches, PREG_OFFSET_CAPTURE);
			preg_match_all($pattern2, $markup, $cMatches, PREG_OFFSET_CAPTURE);
			$oTags = array_reverse($oMatches[0]);
			$cTags = $cMatches[0];
			$matches = array();
			$ifStatements = array();

			foreach($cTags as $cTag){
				foreach($oTags as $idx=>$oTag){
					if($oTag[1] > $cTag[1]) continue;
					
					$matches[$oTag[1]] = (object) array(
						'tag' => $oTag[0],
						'start' => $oTag[1],
						'end' => $cTag[1] + 5,
						'length' => ($cTag[1] + 5) - $oTag[1]
					);
					unset($oTags[$idx]);
					break;
				}
			}
			foreach($matches as $match){
				$ifStatements[$match->start] = $match;
			}
			ksort($ifStatements);
			return $ifStatements;
		}
	}
}

?>