<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_review/Db_Pg_Review.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_reply/Db_Pg_Reply.php";
require_once "LogController.php";

class SlimeController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_project, $db_user, $db_review, $db_slime, $db_pg_reply;
	var $ID = 1;
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
		$this->view->attr = (Object) array( "title" => $this->config->project->planc->slime." Install's Graph" );
					
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->slime);
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_review = new Db_Pg_Review($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->slime->postgre);
		$this->db_pg_reply = new Db_Pg_Reply($this->config->resources->db->postgre);
		
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
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$this->_redirect("slime/installs");
	}
	
	public function reviewAction()
	{
		// Get Parameter 
		$param = (object) $this->getRequest()->getQuery();
		$size = count($this->getRequest()->getQuery());
		
		$q = "";
		if ($param->q != "") {
			$q = $param->q;
		}
		$dataToSearch = array(	"name" => $param->name,
								"email" => $param->email,
								"subject" => $param->subject,
								"feedback" => $param->review,
								"TO_CHAR(feedback_time, 'YYYY-MM-DD')" => $param->date );
		
		$start = 0;
		$limit = $this->config->slime->review->max->set;
		$result =  $this->db_slime->getMoreReview($q, $dataToSearch, $start, $limit, $param->start_date, $param->end_date);
		$exist = $this->db_pg_reply->getExists($this->config->project->id->slime);
		$temp = array();
		foreach($exist as $key=>$value):
			$temp[$value->feedback_id] = $value->replyid;
		endforeach;
		$data = array();
		foreach($result as $r):
			$r->reply = $temp[$r->id];
			array_push($data, $r);
		endforeach;
		$arrs = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($data);
		$this->view->feedback = ($size > 0) ? 1 : 0;
		$this->view->param = $param;
		$this->view->arrs = Zend_Json::encode($arrs);
		$this->view->totalData = $this->db_slime->getReviewCount($q, $dataToSearch, $param->start_date, $param->end_date);
	}
	function getreplyAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$result = $this->db_pg_reply->getReplyById($param->replyid);
		echo Zend_Json::encode($result);
	}
	public function getreviewAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$q = "";
		if ($param->q != "") {
			$q = $param->q;
		}
		
		$dataToSearch = array(	"name" => $param->name,
								"email" => $param->email,
								"subject" => $param->subject,
								"feedback" => $param->review,
								"TO_CHAR(feedback_time, 'YYYY-MM-DD')" => $param->date );
								
		$limit = $this->config->slime->review->max->set;
		if($param->page == '')
			$page=0;
		else
			$page = intval($param->page);
		# find out query stat point
		$start = $page;
		$result =  $this->db_slime->getMoreReview($q, $dataToSearch, $start, $limit);
		$exist = $this->db_pg_reply->getExists(1);
		$temp = array();
		foreach($exist as $key=>$value):
			$temp[$value->feedback_id] = $value->replyid;
		endforeach;
		$data = array();
		foreach($result as $r):
			$r->reply = $temp[$r->id];
			array_push($data, $r);
		endforeach;
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($data);
		echo Zend_Json::encode($arr);			
	}	
	
	public function sortAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$pos = "up";
		$param = (object) $this->getRequest()->getQuery();
		
		if($param->by=="DESC"||  is_null($param->by)){
			$method = "ASC";
			$pos = "down";
			}
		else{
			$method = "DESC";
			$pos = "up";
		}
		
		$result = $this->db_review->getReviewBy($this->ID, $param->sorts, $param->by);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);

		// Logging
		LogController::insert($this->config->resources->db->postgre,
					  $this->config->template->log->view,
					  $this->config->const->activity->view,
					  $this->mySession->id,
					  "SLIME REVIEW",
					  ""
					 );
		
		echo Zend_Json::encode($data);
	}
	
	public function installsAction()
	{	
		
	}
	
	public function carrierAction()
	{
		$this->view->attr->title = "S Lime Carrier's Graph";
	}
	
	public function devicesAction()
	{
		$this->view->attr->title = "S Lime Device's Graph";
	}
	
	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Query 
		$param = (object) $this->getRequest()->getPost();
		$limit = "";
		$start = 0;
		$data = array();
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/slime_review_all".$this->config->csv->ext;
			
			$dataToSearch = array(	"name" => $param->name,
								"email" => $param->email,
								"subject" => $param->subject,
								"feedback" => $param->review,
								"TO_CHAR(feedback_time, 'YYYY-MM-DD')" => $param->date );
		
			$result =  $this->db_slime->getMoreReview($param->q, $dataToSearch, $start, $limit);
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_review_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		
		echo $path;
	}		
	
	public function installscsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		//echo $param->export_t;
		
		if($param->start !="" && $param->end !=""){
			$result = $this->db_project->getCustomDaily($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_project->get30DayDaily($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_project->get7DayDaily($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_project->getAllDaily($this->ID);
				else{
					$result = $this->db_project->getAllDaily($this->ID);
					}
			}
			
		$path = "uploads/Slime_installs_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function reviewcsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getQuery();
		$result =  $this->db_review->getAllReview($this->ID);
		
		$path = "uploads/SLime_review_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function carriercsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();

		$result =  $this->db_project->getAllDataCarrier($this->ID);
			
		$path = "uploads/SLime_carrier_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function errorcsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		if($param->start !="" && $param->end !=""){
			$result = $this->db_project->getCustomErrorDaily($this->ID, $param->start, $param->end);
		}
		else
		{
			if($param->time_input == "30days")
				$result = $this->db_project->get30DayErrorDaily($this->ID);
			else if($param->time_input == "7days")
				$result = $this->db_project->get7DayErrorDaily($this->ID);
			else if($param->time_input == "all")
				$result = $this->db_project->getAllErrorDaily($this->ID);
			else
				$result = $this->db_project->getAllErrorDaily($this->ID);
		}
			
		$path = "uploads/SLime_error_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function devicescsvAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();

		$result =  $this->db_project->getAllDataDevices($this->ID);
			
		$path = "uploads/SLime_devices_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function pulluninAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		if($param->start !="" && $param->end !=""){
			$result = $this->db_project->getCustomDaily($this->ID, $param->start, $param->end);
		}
		else{
			if($param->time_input == "30days")
				$result = $this->db_project->get30DayDaily($this->ID);
			else if($param->time_input == "7days")
				$result = $this->db_project->get7DayDaily($this->ID);
			else if($param->time_input == "all")
				$result = $this->db_project->getAllDaily($this->ID);
			else
				$result = $this->db_project->getAllDaily($this->ID);
		}
	
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SLIME INSTALL",
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
			$result = $this->db_project->getCustomDailyApps($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_project->get30DayDailyApps($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_project->get7DayDailyApps($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_project->getAllDailyApps($this->ID);
				else{
					$result = $this->db_project->getAllDailyApps($this->ID);
					}
			}
			
		$path = "uploads/Slime_installs_".date("YmdHis").$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function pulluninappsAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		if($param->start !="" && $param->end !=""){
			$result = $this->db_project->getCustomDailyApps($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_project->get30DayDailyApps($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_project->get7DayDailyApps($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_project->getAllDailyApps($this->ID);
				else
					$result = $this->db_project->getAllDailyApps($this->ID);
			}
	
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SLIME INSTALL",
							  ""
							 );
					 
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);
	}
	
	//for carrier
	public function pullcarrierAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();

		 $result = $this->db_project->getAllCarrier($this->ID);
		 $result2 =  $this->db_project->getAllDataCarrier($this->ID);
		 $sum = $this->db_project->getAllTotalCarrierInstall($this->ID);
		 $count = $this->db_project->getAllTotalCarrier($this->ID);
			
		 $arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		 $arr2 = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result2);
		 
		 // Logging
		 LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SLIME CARRIER",
							  ""
							 );
		  
		echo Zend_Json::encode($arr).";".$sum.";".$count.";".Zend_Json::encode($arr2);
	}
	//for devices
	public function pulldevicesAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$result2 =  $this->db_project->getAllDataDevices($this->ID);
		$result = array_slice($result2, 0, 5);
		$sum = $this->db_project->getAllTotalDeviceInstall($this->ID);
		$count = $this->db_project->getAllTotalDevice($this->ID);
					
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$arr2 = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result2);
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
					  $this->config->template->log->view,
					  $this->config->const->activity->view,
					  $this->mySession->id,
					  "SLIME DEVICES",
					  ""
					 );

		echo Zend_Json::encode($arr).";".$sum.";".$count.";".Zend_Json::encode($arr2);
	}
	
	//slime_error
	public function errorreportAction()
	{
		$this->view->attr->title = "S Lime Error's Graph";
	}
	public function errorreportinAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$rec_count = $this->db_project->getTotalErrorDaily($this->ID);
		$param = (object) $this->getRequest()->getPost();
		if($param->start !="" && $param->end !=""){
			$result = $this->db_project->getCustomErrorDaily($this->ID, $param->start, $param->end);
		}
		else{
				if($param->time_input == "30days")
					$result = $this->db_project->get30DayErrorDaily($this->ID);
				else if($param->time_input == "7days")
					$result = $this->db_project->get7DayErrorDaily($this->ID);
				else if($param->time_input == "all")
					$result = $this->db_project->getAllErrorDaily($this->ID);
				else
					$result = $this->db_project->getAllErrorDaily($this->ID);
			}
		$data =$this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SLIME ERROR",
							  ""
							 );

		echo Zend_Json::encode($data);
	}
	
	public function errorpopupinAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		
		switch($param->flag) {
			case 1 : $result = $this->db_project->getAllErrorOs($this->ID, $param->tgl); break;
			case 2 : $result = $this->db_project->getAllErrorDevice($this->ID, $param->tgl); break;
			case 3 : $result = $this->db_project->getAllErrorAppversion($this->ID, $param->tgl); break;
			case 4 : $result = $this->db_project->getAllErrorDetail($this->ID, $param->tgl); break;
		}
		
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);	
	}
	
	public function replyAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$userid = $this->mySession->id;
		
		// Send Email
		$config = array("from" => $this->config->smtp->mail->username,
						"from_name" => $this->config->smtp->mail->from,
						"to" => $param->to_email,
						"subject" => "Reply for Feedback ".$param->feedback_id,
						"body" => $param->messages);
		
		$res = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
		
		if ($res == 1) {
			$dataToInsert = array( "userid" => $userid,
								   "to_email" => $param->to_email,
								   "feedback_id" => $param->feedback_id,
								   "projectid" => $this->config->project->id->slime,
								   "messages" => $param->messages);
			$sts = $this->db_pg_reply->insert($dataToInsert);
		}
		$result = array("reply" => $sts, "status" => $res);
		echo Zend_Json::encode($result);
	}
}