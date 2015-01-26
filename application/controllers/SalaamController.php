<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_review/Db_Pg_Review.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class SalaamController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_user, $db_review, $db_pg_project;
	var $ID = 3;
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
						
		// Library 
		$this->library = (object) array( "exporter" => new Exporter() );
										
		// Configuration for View
		$this->view->attr = (Object) array( "title" => $this->config->project->planc->salaam." Install's Graph" );
					
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->salaam);
		
		// Create entity of database
		$this->db_pg_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_review = new Db_Pg_Review($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		
		// Set Variable
		$this->view->projects = $this->db_pg_project->getVisibleProject();
		
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
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$this->_redirect("salaam/installs");
	}
	
	public function reviewAction()
	{

	}
	
	public function sortAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$pos = "up";
		$param = (object) $this->getRequest()->getQuery();
		
		if($param->by=="ASC"||  is_null($param->by)){
			$method = "DESC";
			$pos = "down";
			}
		else{
			$method = "ASC";
			$pos = "up";
		}
		
		$result = $this->db_review->getReviewBy($this->ID, $param->sorts, $param->by);
		
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);

		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SALAAM REVIEW",
							  ""
							 );
		
		echo Zend_Json::encode($data);
	}
	
	public function installsAction()
	{	
		
	}
	
	public function carrierAction()
	{
		$this->view->attr->title = "Salaam Carrier's Graph";
	}
	
	public function devicesAction()
	{
		$this->view->attr->title = "Salaam Device's Graph";
	}
	
	public function installscsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		//echo $param->export_t;
		
		if($param->start !="" && $param->end !=""){
			$result = $this->db_pg_project->getCustomDaily($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_pg_project->get30DayDaily($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_pg_project->get7DayDaily($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_pg_project->getAllDaily($this->ID);
				else{
					$result = $this->db_pg_project->getAllDaily($this->ID);
					}
			}
			
		$path = "uploads/Salaam_installs_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function reviewcsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getQuery();

		$result =  $this->db_review->getAllReview($this->ID);
			
		$path = "uploads/Salaam_review_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function carriercsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();

		$result =  $this->db_pg_project->getAllDataCarrier($this->ID);
			
		$path = "uploads/Salaam_carrier_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function errorcsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		if($param->start !="" && $param->end !=""){
			$result = $this->db_pg_project->getCustomErrorDaily($this->ID, $param->start, $param->end);
		}
		else
		{
			if($param->time_input == "30days")
				$result = $this->db_pg_project->get30DayErrorDaily($this->ID);
			else if($param->time_input == "7days")
				$result = $this->db_pg_project->get7DayErrorDaily($this->ID);
			else if($param->time_input == "all")
				$result = $this->db_pg_project->getAllErrorDaily($this->ID);
			else
				$result = $this->db_pg_project->getAllErrorDaily($this->ID);
		}
			
		$path = "uploads/Salaam_error_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function devicescsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();

		$result =  $this->db_pg_project->getAllDataDevices($this->ID);
			
		$path = "uploads/Salaam_devices_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function pulluninAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
	 	if($param->start !="" && $param->end !=""){
			$result = $this->db_pg_project->getCustomDaily($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_pg_project->get30DayDaily($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_pg_project->get7DayDaily($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_pg_project->getAllDaily($this->ID);
				else
					$result = $this->db_pg_project->getAllDaily($this->ID);
		}

		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SALAAM INSTALL",
							  ""
							 );
	 
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);		
		echo Zend_Json::encode($data);
	}
	
	public function pulluninappsAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
	 	if($param->start !="" && $param->end !=""){
			$result = $this->db_pg_project->getCustomDailyApps($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_pg_project->get30DayDailyApps($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_pg_project->get7DayDailyApps($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_pg_project->getAllDailyApps($this->ID);
				else
					$result = $this->db_pg_project->getAllDailyApps($this->ID);
		}

		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SALAAM INSTALL",
							  ""
							 );
	 
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);		
		echo Zend_Json::encode($data);
	}
	
	public function installscsvappsAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		//echo $param->export_t;
		
		if($param->start !="" && $param->end !=""){
			$result = $this->db_pg_project->getCustomDailyApps($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_pg_project->get30DayDailyApps($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_pg_project->get7DayDailyApps($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_pg_project->getAllDailyApps($this->ID);
				else{
					$result = $this->db_pg_project->getAllDailyApps($this->ID);
					}
			}
			
		$path = "uploads/Salaam_installs_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}

	//for carrier
	public function pullcarrierAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();

		 $result = $this->db_pg_project->getAllCarrier($this->ID);
 		 $result2 =  $this->db_pg_project->getAllDataCarrier($this->ID);
		 $sum = $this->db_pg_project->getAllTotalCarrierInstall($this->ID);
		 $count = $this->db_pg_project->getAllTotalCarrier($this->ID); 
			
		 $arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		 $arr2 = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result2);
		 
		 // Logging
		 LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SALAAM CARRIER",
							  ""
							 );
		  
		echo Zend_Json::encode($arr).";".$sum.";".$count.";".Zend_Json::encode($arr2);
	}
	//for devices
	public function pulldevicesAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$result2 =  $this->db_pg_project->getAllDataDevices($this->ID);
		$result = array_slice($result2, 0, 5);
		$sum = $this->db_pg_project->getAllTotalDeviceInstall($this->ID);
		$count = $this->db_pg_project->getAllTotalDevice($this->ID);
					
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$arr2 = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result2);
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SALAAM DEVICES",
							  ""
							 );

		echo Zend_Json::encode($arr).";".$sum.";".$count.";".Zend_Json::encode($arr2);
	}
	
	//slime_error
	public function errorreportAction()
	{
		$this->view->attr->title = "Salaam Error's Graph";
	}
	public function errorreportinAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		if($param->start !="" && $param->end !=""){
			$result = $this->db_pg_project->getCustomErrorDaily($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_pg_project->get30DayErrorDaily($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_pg_project->get7DayErrorDaily($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_pg_project->getAllErrorDaily($this->ID);
				else
					$result = $this->db_pg_project->getAllErrorDaily($this->ID);
			}
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SALAAM ERROR",
							  ""
							 );

		echo Zend_Json::encode($data);
	}
	
	public function errorpopupinAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		
		switch($param->flag) {
			case 1: $result = $this->db_pg_project->getAllErrorOs($this->ID, $param->tgl); break;
			case 2: $result = $this->db_pg_project->getAllErrorDevice($this->ID, $param->tgl); break;
			case 3: $result = $this->db_pg_project->getAllErrorAppversion($this->ID, $param->tgl); break;
			case 4: $result = $this->db_pg_project->getAllErrorDetail($this->ID, $param->tgl); break;
		}
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);
	}
}