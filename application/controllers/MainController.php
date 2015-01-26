<?php
/*
	Created by a.riccia
	19 / 08 / 2014
*/
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class MainController extends Zend_Controller_Action
{
	var $url;
	var $conn, $conn_postgre;
	var $library;
	var $db_project, $db_user;
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
		
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		
		// Set Variable
		$this->first = $this->db_user->checkFirst($this->mySession->id);
		$this->view->projects = $this->db_project->getVisibleProject();
		$this->view->is_first = $this->first;		
		
		$this->initView();
	}
	
	public function checkFirst()
	{
		if($this->first==0){
			$this->_redirect("");
		}
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
	
	public function indexAction()
	{
		// Log the View main menu
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "MAIN PAGE"
							  );
	}
	
	public function signoutAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
									 
		// Logout
		$this->getFrontController()->getParam("bootstrap")->unsetSession();
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "LOG OUT",
							  ""
							 );
							 
		// Redirect
		$this->_redirect("");
	}
	
	public function changepassAction()
	{
		$this->checkFirst();
	}
	
	public function chkchangeAction()
	{
		// Check First Login
		$this->checkFirst();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		
		// Validation 
		$userID = $this->mySession->id;
		$error = "";
		if ($param->oldpass == "") { 
			$error = "Old Password must be filled.";
		} else if ($param->newpass == "") {
			$error = "New Password must be filled.";
		} else if (strlen($param->newpass) < 6) {
			$error = "Password at least contains 6 characters.";
		} else if ($this->db_user->chkValidPass($userID, $param->oldpass) == false) {
			$error = "Old password is wrong.";
		} else if ($this->library->validate->chkAlNum($param->newpass) == false) {
			$error = "Password must be alphanumeric.";
		} else if (strcmp($param->newpass, $param->confirm)!=0) {
			$error = "Confirm password must be same with the New Password.";
		} else
		{
			//insert to database
			$dataToEdit = array(
				"userID" => $userID,
				"newpass" => $param->newpass,
				);

			$this->db_user->changePassword($dataToEdit);
						
			$res = $this->db_user->changeFlag($this->mySession->id);
								 
			$error = "Success to change password.";
		}		
		$this->view->error = $error;
		$this->view->cache = $param;
	}
	
	public function firstchangeAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$userID = $this->mySession->id;
		
		// Validation
		if ($param->newpass == "") {
			$error = 1;
		} else if (strlen($param->newpass) < 6) {
			$error = 2;
		} else if ($this->library->validate->chkAlNum($param->newpass) == false) {
			$error = 3;
		} else if (strcmp($this->db_user->encrypt($param->newpass),$this->db_user->getPass($userID)) == 0) {
			$error = 5;
		} else if (strcmp($param->newpass, $param->confirm)!=0) {
			$error = 4;
		} else
		{
			//insert to database
			$dataToEdit = array(
								"userID" => $userID,
								"newpass" => $param->newpass,
								);

			$res = $this->db_user->changePassword($dataToEdit);
						
			$res = $this->db_user->changeFlag($this->mySession->id);
								 
			$error = 0;
		}
		echo $error;
	}
}