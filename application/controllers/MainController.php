<?php
/*
	Created By jacky.w
	09 / 03 / 2015

*/
require_once "db_user/db_user.php";

class MainController extends Zend_Controller_Action
{
	
	var $db,$user;
	
	public function init()
	{
		//Init View
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;
		
		//session
		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->view->mySession = $this->mySession;
		
		/*Get Configuration Value*/
		$this->config=Zend_Registry::get('config');
		$this->view->config = $this->config;
		
		// Create entity of database
		$this->db = new Db_User($this->config->resources->db);
		
		
		$this->initView();
	}
	
	public function indexAction()
	{
		$this->user = $this->db->getUser();
		$this->view->user = $this->user;
	}
	
	public function logoutAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		$this->mySession->unsetAll();
		
		//Redirect to Login
		$this->_redirect("index");
	}
	
	public function checkValid()
	{
		// Back to login if session is invalid
		if (!isset($this->mySession->username)) {
			$this->_redirect("");
		} else if ($this->mySession->role != $this->config->role->id->admin) {
			// Redirect  to Customer Service Page
		}
	}
}

