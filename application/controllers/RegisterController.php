<?php
require_once "db_user/Db_User.php";
class RegisterController extends Zend_Controller_Action{
   var $url, $session, $db_user;
   var $username, $name, $pass, $role;

   public function init(){
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;

		$this->config = Zend_Registry::get("config");
		$this->view->config = $this->config;

      $this->db_user = new Db_User($this->config->resources->db);
      $this->initView();
   }

   public function indexAction(){
		// $this->view->user = $this->db_user->getUsername();
      //get
      $param = (object) $this->getRequest()->getQuery();
      $error = "";

      if(isset($param->err)){
         switch($param->err){
            case 1: $error = "Password must be max 8 characters long"; break;
            case 3: $error = "Username must not be empty"; break;
            case 4: $error = "Password must not be empty"; break;
            case 5: $error = "Name must not be empty"; break;
            case 6: $error = "Role must be chosen"; break;
            case 7: $error = "Username has been chosen"; break;
         }
      }
      $this->view->error = $error;
   }

   public function validasiAction(){
      //disable view
      $this->_helper->viewRenderer->setNoRender();
      //dapetin parameter post
      $param = (object) $this->getRequest()->getPost();
      $paramget = (object) $this->getRequest()->getQuery();
      //assign var
      $this->username = $param->username;
      $this->name = $param->name;
      $this->pass = $param->pass;
      $this->role = $param->role;

      //if
      if(strlen($param->pass)>8){
         $this->_redirect("register?err=1");
      }
      else if(strlen($param->username)==0){
         $this->_redirect("register?err=3");
      }
      else if(strlen($param->pass)==0){
         $this->_redirect("register?err=4");
      }
      else if(strlen($param->name)==0){
         $this->_redirect("register?err=5");
      }
      else if($param->role==-1){
         $this->_redirect("register?err=6");
      }
      else if($this->checkuser($param->username)==0){
         $this->_redirect("register?err=7");
      }
      else{
         $this->_forward("insertdb", "register", null, array("username" => $this->username,
                               "password" => $this->pass,
                               "name" => $this->name,
                               "role" => $this->role));
      }
   }

   public function insertdbAction(){
      //disable
      $this->_helper->viewRenderer->setNoRender();

      $this->username = $this->_getParam("username");
      $this->pass = $this->_getParam("pass");
      $this->name = $this->_getParam("name");
      $this->role = $this->_getParam("role");
      $dataToInsert = array("username" => $this->username,
                            "password" => md5($this->pass),
                            "name" => $this->name,
                            "role" => $this->role);
		
		$temp = $this->db_user->getUsername($this->username);
		
        $this->db_user->insertUser($dataToInsert);
        $this->_redirect("index?err=2");
   }

   public function checkuser($username){
      $temp = $this->db_user->getUsername($this->username);
	  $nilai = count($temp);
      return ($nilai == 0) ? 1 : 0;
   }
}
