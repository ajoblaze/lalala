<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_promo/Db_Pg_Promo.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class CustslimeController extends Zend_Controller_Action
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
		$this->db_promo = new Db_Pg_Promo($this->config->resources->db->newslime->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->newslime->postgre);
		
		// Set Variable
		$this->view->projects = $this->db_project->getVisibleProject();
		
		$this->first = $this->db_user->checkFirst($this->mySession->id);
		$this->checkFirst();
		
		$this->initView();
	}
	
	public function checkFirst() {
		if($this->first==0){
			$this->_redirect("");
		}
	}
	
	public function indexAction(){
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$offset = 0;
		$limit = $this->config->slime->customer->max->set; // how many data will be shown 
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
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
		if($param->interests !='') {
			$data["interests"] = $param->interests;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->status !='') {
			$data["status"] = $param->status;
		}	
		
		$result = $this->db_slime->getCustomerSlime($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
				
		$this->view->param = $param;
	}
	
	public function getdeviceAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$product = $this->db_slime->getHistoryDevice($param->userid);
		
		echo Zend_Json::encode($product);
	}
	
	public function getdownloadAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$download = $this->db_slime->getHistoryDownload($param->userid);
		
		echo Zend_Json::encode($download);
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
		if($param->gender !='') {
			$data["gender"] = $param->gender;
		}
		if($param->start_dob !='') {
			$data["start_dob"] = $param->start_dob;
		}
		if($param->end_dob !='') {
			$data["end_dob"] = $param->end_dob;
		}
		if($param->interests !='') {
			$data["interests"] = $param->interests;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->status !='') {
			$data["status"] = $param->status;
		}	
		
		$count = $this->db_slime->getCustomerSlimeCount($q, $data);
		
		echo $count;
	}

	public function loadmoreAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->slime->customer->max->set;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
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
		if($param->interests !='') {
			$data["interests"] = $param->interests;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->status !='') {
			$data["status"] = $param->status;
		}	
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_slime->getCustomerSlime($q, $data, $offset, $limit);

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
		if($param->name != "") {
			$data["name"] = $param->name;
		}
		if($param->email!="") {
			$data["email"] = $param->email;
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
		if($param->interests !='') {
			$data["interests"] = $param->interests;
		}
		if($param->start_registered !='') {
			$data["start_registered"] = $param->start_registered;
		}
		if($param->end_registered !='') {
			$data["end_registered"] = $param->end_registered;
		}
		if($param->status !='') {
			$data["status"] = $param->status;
		}	
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/slime_customer_all".$this->config->csv->ext;
			
			$result = $this->db_slime->getCustomerSlime($q, $data, $offset, $limit);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		} 
		else { 
			$path = "uploads/slime_customer_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function apiAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$sts = "";
		
		if ($param->type == Db_Pg_Slime::$SUSPEND) {
			$this->db_slime->apiCustomer($param->device_id, Db_Pg_Slime::$SUSPEND);
			$data = array("deviceIdentifierId" => $param->device_id,
								  "deviceIdentifierType" => $param->device_type,
								  "reason" => "Testing saja...");
			$object = $this->getFrontController()->getParam("bootstrap")
												 ->hitServices($this->config->services->slime->suspend, $data);
			$sts = 1;
		} else if ($param->type == Db_Pg_Slime::$ACTIVATE) {
			$this->db_slime->apiCustomer($param->device_id, Db_Pg_Slime::$ACTIVATE);
			$data = array("deviceIdentifierId" => $row->device_id,
						  "deviceIdentifierType" => $row->device_type);
			$object = $this->getFrontController()->getParam("bootstrap")
												 ->hitServices($this->config->services->slime->unsuspend, $data);
			$sts = 0;
		}
		
		$result = array("status" => $sts,
						"code" => $object->result_code);
		echo Zend_Json::encode($result);
	}
}
