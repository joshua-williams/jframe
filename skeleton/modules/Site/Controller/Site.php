<?php

namespace Site\Controller{
	
	class Site extends \JFrame\Controller{
		
		function index(){
			echo $this->view->render('index.phtml');
		}
	}
}

?>
