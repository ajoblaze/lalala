<?php
/*
	Created By jacky.w
	09 / 03 / 2015

*/
class MainController extends Zend_Controller_Action
{
	
	var $db;
	
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
					
		// Include Library
		$this->library = (object) array( "validate" => new Validate() );
		
		
		// Create entity of database
		$this->db = new Db_User($this->config->resources->db);
		
		
		$this->initView();
	}
	
	public function indexAction()
	{
		$userlist = $this->db_user->getUser();
		$this->view->userList = $userList;
		
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

