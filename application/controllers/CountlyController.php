<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class CountlyController extends Zend_Controller_Action
{
	var $url;
	var $conn, $conn_postgre;
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
		
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		
		// Set Variable
		$this->view->projects = $this->db_project->getVisibleProject();
		
		$this->first = $this->db_user->checkFirst($this->mySession->id);
		$this->checkFirst();
		
		$this->initView();
	}
	
	public function checkFirst()
	{
		if($this->first==0){
			$this->_redirect("");
		}
	}
		
	public function indexAction()
	{
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "COUNTLY",
							  ""
							 );
	}
}