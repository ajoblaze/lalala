<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class PublisherController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_project, $db_slime, $db_user;
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
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->newslime->postgre);
		
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
		$this->_helper->viewRenderer->setNoRender();
		$this->_redirect("publisher/content");
	}
	
	public function contentAction(){
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$offset = 0;
		$limit = $this->config->slime->content->max->set; // how many data will be shown 
		$publisher = $this->mySession->publisher_id;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product_name!="") {
			$data["product_name"] = $param->product_name;
		}
		if($param->category!="") {
			$data["category"] = $param->category;
		}
		if($param->publisher !='') {
			$data["publisher"] = $param->publisher;
		}
		if($param->group != '') {
			$data["group"] = $param->group;
		}	
		if($param->price != '') {
			$data["price"] = $param->price;
		}		
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$start = ($page * $limit) - $limit;
		
		$result = $this->db_slime->getContentsFragment($q, $data, $offset, $limit, $param->start_release, $param->end_release, $publisher);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
				
		$this->view->param = $param;
	}
	
	public function gettotalcontentAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$publisher = $this->mySession->publisher_id;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product_name!="") {
			$data["product_name"] = $param->product_name;
		}
		if($param->category!="") {
			$data["category"] = $param->category;
		}
		if($param->publisher !='') {
			$data["publisher"] = $param->publisher;
		}
		if($param->group != '') {
			$data["group"] = $param->group;
		}	
		if($param->price != '') {
			$data["price"] = $param->price;
		}
		$count = $this->db_slime->getContentCount($q, $data, $param->start_release, $param->end_release, $publisher);
		
		echo $count;
	}

	public function loadmorecontentAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->slime->content->max->set;
		$publisher = $this->mySession->publisher_id;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product_name!="") {
			$data["product_name"] = $param->product_name;
		}
		if($param->category!="") {
			$data["category"] = $param->category;
		}
		if($param->publisher !='') {
			$data["publisher"] = $param->publisher;
		}
		if($param->group != '') {
			$data["group"] = $param->group;
		}	
		if($param->price != '') {
			$data["price"] = $param->price;
		}		
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_slime->getContentsFragment($q, $data, $offset, $limit, $param->start_release, $param->end_release, $publisher);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
	}
	
	public function contentdetailAction()
	{
		//Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		// result
		$book = $this->db_slime->getContentDetail($param->id);
		$preview = $this->db_slime->getProductPreview($param->id);
		
		$result = array("book" => $book, "preview" => $preview);
		echo Zend_Json::encode($result);
	}
	
	public function downloaddetailAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		// result 
		$download = $this->db_slime->getContentDownload($param->product_id);
		
		echo Zend_Json::encode($download);
	}
	
	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$publisher = $this->mySession->publisher_id;
		$data = array();
		$q='';
		$limit = "";
		$offset = 0;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product_name!="") {
			$data["product_name"] = $param->product_name;
		}
		if($param->category!="") {
			$data["category"] = $param->category;
		}
		if($param->publisher !='') {
			$data["publisher"] = $param->publisher;
		}
		if($param->group != '') {
			$data["group"] = $param->group;
		}	
		if($param->price != '') {
			$data["price"] = $param->price;
		}	
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/slime_content_all".$this->config->csv->ext;
			
			$result = $this->db_slime->getContentsFragment($q, $data, $offset, $limit, $param->start_release, $param->end_release, $publisher);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_content_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
}