<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_download/Db_Pg_Download.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class DownloadController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_project, $db_download, $db_user;
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
		$this->db_download = new Db_Pg_Download($this->config->resources->db->newslime->postgre);
		
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
		$limit = $this->config->slime->download->max->set; // how many data will be shown 
		$publisher = $this->mySession->publisher_id;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product!="") {
			$data["product"] = $param->product;
		}
		if($param->name!="") {
			$data["name"] = $param->name;
		}
		if($param->product!="") {
			$data["email"] = $param->email;
		}
		if($param->device_identifier!="") {
			$data["device_identifier"] = $param->device_identifier;
		}
		if($param->device_model!="") {
			$data["device_model"] = $param->device_model;
		}
		if($param->segment !='') {
			$data["segment"] = $param->segment;
		}
		if($param->start_download !='') {
			$data["start_download"] = $param->start_download;
		}
		if($param->end_download !='') {
			$data["end_download"] = $param->end_download;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$start = ($page * $limit) - $limit;

		$result = $this->db_download->getDownload($q, $data, $offset, $limit, $publisher);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
				
		$this->view->param = $param;
	}
	
	public function gettotaldownloadAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$publisher = $this->mySession->publisher_id;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product!="") {
			$data["product"] = $param->product;
		}
		if($param->name!="") {
			$data["name"] = $param->name;
		}
		if($param->product!="") {
			$data["email"] = $param->email;
		}
		if($param->device_identifier!="") {
			$data["device_identifier"] = $param->device_identifier;
		}
		if($param->device_model!="") {
			$data["device_model"] = $param->device_model;
		}
		if($param->segment !='') {
			$data["segment"] = $param->segment;
		}
		if($param->start_download !='') {
			$data["start_download"] = $param->start_download;
		}
		if($param->end_download !='') {
			$data["end_download"] = $param->end_download;
		}
		
		$count = $this->db_download->getDownloadCount($q, $data, $publisher);
		
		echo $count;
	}

	public function loadmoredownloadAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->slime->download->max->set;
		$publisher = $this->mySession->publisher_id;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->product!="") {
			$data["product"] = $param->product;
		}
		if($param->name!="") {
			$data["name"] = $param->name;
		}
		if($param->product!="") {
			$data["email"] = $param->email;
		}
		if($param->device_reg_id!="") {
			$data["device_reg_id"] = $param->device_reg_id;
		}
		if($param->device_model!="") {
			$data["device_model"] = $param->device_model;
		}
		if($param->segment !='') {
			$data["segment"] = $param->segment;
		}
		if($param->start_download !='') {
			$data["start_download"] = $param->start_download;
		}
		if($param->end_download !='') {
			$data["end_download"] = $param->end_download;
		}	
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_download->getDownload($q, $data, $offset, $limit, $publisher);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
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
		if($param->product!="") {
			$data["product"] = $param->product;
		}
		if($param->name!="") {
			$data["name"] = $param->name;
		}
		if($param->product!="") {
			$data["email"] = $param->email;
		}
		if($param->device_reg_id!="") {
			$data["device_reg_id"] = $param->device_reg_id;
		}
		if($param->device_model!="") {
			$data["device_model"] = $param->device_model;
		}
		if($param->segment !='') {
			$data["segment"] = $param->segment;
		}
		if($param->start_download !='') {
			$data["start_download"] = $param->start_download;
		}
		if($param->end_download !='') {
			$data["end_download"] = $param->end_download;
		}
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/slime_download_all".$this->config->csv->ext;
			
			$result = $this->db_download->getDownload($q, $data, $offset, $limit, $publisher);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_download_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
}
