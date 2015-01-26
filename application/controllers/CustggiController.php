<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_promo/Db_Pg_Promo.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class CustggiController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_project, $db_promo, $db_user, $db_slime;
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
		$this->db_promo = new Db_Pg_Promo($this->config->resources->db->postgre->staging);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		
		// Set Variable
		$this->view->projects = $this->db_project->getVisibleProject();
		
		$this->first = $this->db_user->checkFirst($this->mySession->id);
		$this->checkFirst();
		
		$this->initView();
	}
	
	public function checkFirst() {
		if($this->first==0) {
			$this->_redirect("");
		}
	}
	
	public function indexAction(){
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$offset = 0;
		$limit = $this->config->sgift->customer->max->set; // how many data will be shown 
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
		}
		if($param->phone!="") {
			$data["phone"] = $param->phone;
		}
		if($param->gender !='') {
			$data["gender"] = $param->gender;
		}
		if($param->start_dob !='') {
			$data["start_dob"] = $param->start_dob;
		}
		if($param->end_dob !='') {
			$data["end_dob"] = $param->end_dob;
		}
		if($param->marital_status !='') {
			$data["marital_status"] = $param->marital_status;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->address !='') {
			$data["address"] = $param->address;
		}
		if($param->holiday !='') {
			$data["holiday"] = $param->holiday;
		}
		if($param->device_model !='') {
			$data["device_model"] = $param->device_model;
		}
		if($param->imei !='') {
			$data["imei"] = $param->imei;
		}
		
		$result = $this->db_sgift->getCustomerSgift($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
				
		$this->view->param = $param;
	}

	public function gettotalAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
		}
		if($param->phone!="") {
			$data["phone"] = $param->phone;
		}
		if($param->gender !='') {
			$data["gender"] = $param->gender;
		}
		if($param->start_dob !='') {
			$data["start_dob"] = $param->start_dob;
		}
		if($param->end_dob !='') {
			$data["end_dob"] = $param->end_dob;
		}
		if($param->marital_status !='') {
			$data["marital_status"] = $param->marital_status;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->address !='') {
			$data["address"] = $param->address;
		}
		if($param->holiday !='') {
			$data["holiday"] = $param->holiday;
		}
		if($param->device_model !='') {
			$data["device_model"] = $param->device_model;
		}
		if($param->imei !='') {
			$data["imei"] = $param->imei;
		}
		
		$count = $this->db_sgift->getCustomerSgiftCount($q, $data);
		
		echo $count;
	}

	public function loadmoreAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->sgift->customer->max->set;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
		}
		if($param->phone!="") {
			$data["phone"] = $param->phone;
		}
		if($param->gender !='') {
			$data["gender"] = $param->gender;
		}
		if($param->start_dob !='') {
			$data["start_dob"] = $param->start_dob;
		}
		if($param->end_dob !='') {
			$data["end_dob"] = $param->end_dob;
		}
		if($param->marital_status !='') {
			$data["marital_status"] = $param->marital_status;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->address !='') {
			$data["address"] = $param->address;
		}
		if($param->holiday !='') {
			$data["holiday"] = $param->holiday;
		}
		if($param->device_model !='') {
			$data["device_model"] = $param->device_model;
		}
		if($param->imei !='') {
			$data["imei"] = $param->imei;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_sgift->getCustomerSgift($q, $data, $offset, $limit);

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
		$partial = 500;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
		}
		if($param->phone!="") {
			$data["phone"] = $param->phone;
		}
		if($param->gender !='') {
			$data["gender"] = $param->gender;
		}
		if($param->start_dob !='') {
			$data["start_dob"] = $param->start_dob;
		}
		if($param->end_dob !='') {
			$data["end_dob"] = $param->end_dob;
		}
		if($param->marital_status !='') {
			$data["marital_status"] = $param->marital_status;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->address !='') {
			$data["address"] = $param->address;
		}
		if($param->holiday !='') {
			$data["holiday"] = $param->holiday;
		}
		if($param->device_model !='') {
			$data["device_model"] = $param->device_model;
		}
		if($param->imei !='') {
			$data["imei"] = $param->imei;
		}
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/ggi_customer_email".$this->config->csv->ext;
			
			$allData = array();
			do {
				$result = $this->db_sgift->getCustomerSgift($q, $data, $offset, $partial);
				$offset += sizeof($result); 
				array_push($allData, $result);
			} while (sizeof($result) > $partial); 
			$json = Zend_Json::encode($allData);
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		} else if ($param->export == 2) {
			$path = "uploads/ggi_customer_device".$this->config->csv->ext;
			
			$result = $this->db_sgift->getCustomerSgiftDevice($q, $data, $offset, $limit);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/ggi_customer_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
}
