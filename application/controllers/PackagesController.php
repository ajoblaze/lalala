<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_packages/Db_Pg_Packages.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class PackagesController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_project, $db_packages, $db_user;
	var $ID = 2;  
	public function init()
	{
		//Init View
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;
		
		//session
		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->view->mySession = $this->mySession;
		
		/*Get Configuration Value*/
		$this->config = Zend_Registry::get('config');
		$this->view->config = $this->config;
		
		// Library 
		$this->library = (object) array( "exporter" => new Exporter() );
		
		// Configuration for View
		$this->view->attr = (Object) array( "title" => "" );
											
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->slime);
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_packages = new Db_Pg_Packages($this->config->resources->db->newslime->postgre);
		
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
	
	public function indexAction(){
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$offset = 0;
		$limit = $this->config->slime->content->max->set; // how many data will be shown 
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->package_name!="") {
			$data["package_name"] = $param->package_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->end_start_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_expire_date !='') {
			$data["start_expire_date"] = $param->start_expire_date;
		}
		if($param->end_expire_date !='') {
			$data["end_expire_date"] = $param->end_expire_date;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$start = ($page * $limit) - $limit;

		$result = $this->db_packages->getPackages($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
				
		$this->view->param = $param;
	}
	
	public function gettotalpackageAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->package_name!="") {
			$data["package_name"] = $param->package_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->end_start_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_expire_date !='') {
			$data["start_expire_date"] = $param->start_expire_date;
		}
		if($param->end_expire_date !='') {
			$data["end_expire_date"] = $param->end_expire_date;
		}
		$count = $this->db_packages->getPackagesCount($q, $data);
		
		echo $count;
	}

	public function loadmorepromoAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->slime->content->max->set;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->package_name!="") {
			$data["package_name"] = $param->package_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->end_start_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_expire_date !='') {
			$data["start_expire_date"] = $param->start_expire_date;
		}
		if($param->end_expire_date !='') {
			$data["end_expire_date"] = $param->end_expire_date;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_packages->getPackages($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
	}

	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "";
		$offset = 0;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->package_name!="") {
			$data["package_name"] = $param->package_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->end_start_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_expire_date !='') {
			$data["start_expire_date"] = $param->start_expire_date;
		}
		if($param->end_expire_date !='') {
			$data["end_expire_date"] = $param->end_expire_date;
		}	
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/slime_promo_all".$this->config->csv->ext;
			
			$result = $this->db_packages->getPackages($q, $data, $offset, $limit);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_promo_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
}
