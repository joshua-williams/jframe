<?php

namespace JFrame{
	
	class Controller{
		protected $app;
		protected $view;
		protected $path;
		protected $renderView = true;
		
		function __construct(){
			$this->app = App::instance();
			$this->view = new View();
			$namespace = $this->app->get('module', $this->app->get('defaultModule'));
			$this->path = 'modules' . DS . $namespace;
			$this->view->addPath("modules" . DS . $namespace . DS . "views");
		}
		
		function view(){return $this->view;}
		
		function render(){
			
		}
	}
}

?>