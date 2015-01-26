<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_sent/Db_Pg_Sent.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_historysuspend/Db_Pg_Historysuspend.php";
require_once "db_pg_customer/Db_Pg_Customer.php";
require_once "LogController.php";

class CustomerController extends Zend_Controller_Action
{
	var $url, $serverUrl;
	var $config;
	var $first;
	var $conn, $conn_postgre, $conn_sgift, $conn_sgift_postgre, $conn_slime, $conn_slime_postgre;
	var $library;
	var $db_project, $db_user, $db_sent, $db_slime, $db_sgift, $db_historysuspend, $db_customer;
	var $CHG_EMAIL = "email", $SUSPEND = "suspend", $UNSUSPEND = "activate";
	var $STATE_TEST = "test", $STATE_RELEASE = "release";
	var $state = "test";
	
	public function init()
	{
		//Init View
		$this->url = $this->getRequest()->getBaseURL();
		$this->serverUrl = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost();
		$this->view->baseUrl = $this->url;
		
		//session
		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->view->mySession = $this->mySession;
		
		/*Get Configuration Value*/
		$this->config = Zend_Registry::get('config');
		$this->view->config = $this->config;
		
		// Attach Library
		$this->library = (object) array( "exporter" => new Exporter(),
										 "html" => new Html() );
		$this->library->html->useCSS($this->config->mail->css);
		
		// Configuration for View
		$this->view->attr = (Object) array( "title" => "Customers" );
										  
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_sent = new Db_Pg_Sent($this->config->resources->db->postgre);
		$this->db_historysuspend = new Db_Pg_Historysuspend($this->config->resources->db->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->slime->postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		$this->db_customer = new Db_Pg_Customer($this->config->resources->db->postgre);

		// Set Variable
		$this->view->projects = $this->db_project->getVisibleProject();
		
		$this->first = $this->db_user->checkFirst($this->mySession->id);
		
		$this->initView();
	}
	
	public function checkFirst()
	{
		if($this->first==0){
			$this->_redirect("");
		}
	}
	
	public function exportcsvAction()
	{	
		// Check First
		$this->checkFirst();		
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		// Get Query 
		$param = (object) $this->getRequest()->getPost();
		
		$dataToSearch = array(	
								"gi.name" => $param->username,
								"ge.email" => $param->email,
								"di.name" => $param->imei,
								"device_model" => $param->device,
								"app" => $param->app,
								"status" => $param->status,
							 );
					
		$offset = 0;
		$set = "";	 
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/customer_all".$this->config->csv->ext;
			
			
			$customer_sgift = $this->db_sgift->getCustomerToday($param->q, $dataToSearch);
			$customer_slime = $this->db_slime->getCustomerToday($param->q, $dataToSearch);
			$today_customer = array_merge($customer_slime, $customer_sgift);
			$total = sizeof($today_customer);
			$result = $this->db_customer->getCustomer($param->q, $dataToSearch, 0, $set);
			
			$data = array();	
			foreach ($today_customer as $key => $value) : 
				array_push($data, $value);
			endforeach;
			
			foreach ($result as $row) :
				if (strcasecmp($app, $this->config->project->kode->sgift) == 0) {
					if (isset($customer_sgift[$row->imei])) {
						if ($customer_sgift[$row->imei]->last_download_date != "") {
							$row->last_download_date = $customer_sgift[$row->imei]->last_download_date;
						}
					}		
				} else {
					if (isset($customer_slime[$row->imei])){
						if ($customer_slime[$row->imei]->last_download_date != "") {
							$row->last_download_date = $customer_slime[$row->imei]->last_download_date;
						}
					}
				}
				array_push($data, $row);
			endforeach;
		
			$json = Zend_Json::encode($data);
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}
		else { 
			$path = "uploads/customer_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		
		echo $path;
	}
	
	public function checkProject()
	{
		$search_sgift = array_search($this->config->project->id->sgift, $this->mySession->project);
		$search_slime = array_search($this->config->project->id->slime, $this->mySession->project);
		if (is_numeric($search_sgift) && is_numeric($search_slime)) {
			$project = "";
		} else if (is_numeric($search_sgift)) {
			$project = $this->config->project->kode->sgift;
		} else if (is_numeric($search_slime)) {
			$project = $this->config->project->kode->slime;
		}
		return $project;
	}
	
	public function indexAction()
	{	
		// Check First
		$this->checkFirst();
		// Get Parameter
		$param = (object) $this->getRequest()->getQuery();
		
		$set_customer = $this->config->customer->max->set;
		
		// Searching S GIFT
		$dataToSearch = array(	
								"name" => $param->username,
								"email" => $param->email,
								"imei" => $param->imei,
								"device_model" => $param->device,
								"app" => $param->app,
								"status" => $param->status
							 );
		
		// Checking Project 
		$project = $this->checkProject();
		
		if ($project == "") {
			$customer_sgift = $this->db_sgift->getCustomerToday($param->q, $dataToSearch);
			$customer_slime = $this->db_slime->getCustomerToday($param->q, $dataToSearch);
		} else if ($project == $this->config->project->kode->slime) {
			$customer_slime = $this->db_slime->getCustomerToday($param->q, $dataToSearch);
			$customer_sgift = array();
		} else if ($project == $this->config->project->kode->sgift) {
			$customer_slime = array();
			$customer_sgift = $this->db_sgift->getCustomerToday($param->q, $dataToSearch);
		}
		$today_customer = array_merge($customer_slime, $customer_sgift);
		$total = sizeof($today_customer);
		
		$result = $this->db_customer->getCustomer($param->q, $dataToSearch, 0, $set_customer, $project);
		
		$data = array();	
		foreach ($today_customer as $key => $value) : 
			array_push($data, $value);
		endforeach;
		
		foreach ($result as $row) :
			if (strcasecmp($app, $this->config->project->kode->sgift) == 0) {
				if (isset($customer_sgift[$row->imei])) {
					if ($customer_sgift[$row->imei]->last_download_date != "") {
						$row->last_download_date = $customer_sgift[$row->imei]->last_download_date;
					}
				}		
			} else {
				if (isset($customer_slime[$row->imei])){
					if ($customer_slime[$row->imei]->last_download_date != "") {
						$row->last_download_date = $customer_slime[$row->imei]->last_download_date;
					}
				}
			}
			
			array_push($data, $row);
		endforeach;
		
		$this->view->arrs = Zend_Json::encode($data);

		$result_user = $this->db_user->getUserForTable();
		$user_mail = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result_user);
		$this->view->user_mail = $user_mail;
		$this->view->user_mail_list = Zend_Json::encode($user_mail);
		$this->view->param = $param;
		
		// Logging
		$dataToSearch["search"] = $param->q;
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "SEARCH CUSTOMER",
							  $dataToSearch );
	}
	
	public function parse_user($userList){
		$temp_data = array();
		$counter=-1;
		$temp_projectName = "";
		$temp_username = "";
		
		foreach($userList as $row) :
				
			if( $row->username == $temp_username){	
				$temp_data[$counter]['projectName'] = $row->projectName .= ", ".$temp_projectName;
				
			}
			else{
				$counter++;	
				$temp_data[$counter]['projectName'] = $row->projectName;	
			}
			$temp_data[$counter]['userID'] = $row->userID;
			$temp_data[$counter]['username'] = $row->username;
			$temp_data[$counter]['roleName'] = $row->roleName;	
			$temp_data[$counter]['created_date'] = $row->created_date;
			$temp_data[$counter]['active'] = $row->active;
			$temp_projectName = $row->projectName;
			
			$temp_username = $row->username;
		
		endforeach;
		
		return $temp_data;
	}
	
	public function suspendOrActivate($app, $device_identifier, $type = "enabled")
	{
		if ($app == $this->config->project->kode->sgift) {
			$this->db_sgift->suspendOrActivateCustomer($device_identifier, $type);
		} else if ($app == $this->config->project->kode->slime) {
			$this->db_slime->suspendOrActivateCustomer($device_identifier, $type);
		}
		$this->db_customer->suspendorActivate($app,$device_identifier, $type);
	}
	
	public function gettotalAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$project = $this->checkProject();
		
		$dataToSearch = array(	
								"name" => $param->username,
								"email" => $param->email,
								"imei" => $param->imei,
								"device_model" => $param->device,
								"app" => $param->app,
								"status" => $param->status,
							 );
							 
		$totalData = $this->db_customer->getCustomerCount($param->q, $dataToSearch, $project);
		
		if ($project == "") {
			$totalSlimeToday = $this->db_slime->getCustomerTodayCount($param->q, $dataToSearch);
			$totalSgiftToday = $this->db_sgift->getCustomerTodayCount($param->q, $dataToSearch);
		} else if ($project == $this->config->project->kode->slime) {
			$totalSlimeToday = $this->db_slime->getCustomerTodayCount($param->q, $dataToSearch);
			$totalSgiftToday = 0;
		} else if ($project == $this->config->project->kode->sgift) {
			$totalSlimeToday = 0;
			$totalSgiftToday = $this->db_sgift->getCustomerTodayCount($param->q, $dataToSearch);
		}
		$totalAll = $totalData + $totalSlimeToday + $totalSgiftToday;

		echo $totalAll;
	}
	
	public function loadmoreAction()
	{
		// Disable View 
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		$q = "";
		if($param->q!=''){
			$q = $param->q;
		}
		
		// Data to search
		$dataToSearch = array(	
								"name" => $param->username,
								"email" => $param->email,
								"imei" => $param->imei,
								"device_model" => $param->device,
								"app" => $param->app,
								"status" => $param->status,
							 );
							 
		$project = $this->checkProject();
		$set_customer = $this->config->customer->max->set;
		
		$result = $this->db_customer->getCustomer($q, $dataToSearch , $param->offset, $set_customer, $project);
		
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		
		echo Zend_Json::encode($data);
	}
	
	public function sendconfirmAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$userID = $this->mySession->id;
		
		if ($param->to != "")
		{
			$mode = "";
			$kode = "";
			if (strcasecmp($param->mode,$this->CHG_EMAIL) == 0) {
				$mode = "Change Email to ".$param->newemail;
			} else {
				$mode = $param->mode;
			}
			// Insert history
			$dataToInsert = array(
									"email" => $param->email,
									"device_identifier" => $param->device_identifier,
									"device_type" => $param->device_type,
									"app" => $param->app,
									"actions" => $mode,
									"reason_actions" => $param->reason,
									"userID" => $userID 
								  );
			$historyID = $this->db_historysuspend->insert($dataToInsert);

			//CUSTOM SUBJECT KETIKA CS REQUEST KE ADMIN 
			if(strcasecmp($param->app,$this->config->project->kode->slime)== 0) {
				$kode = $this->config->project->kode->slime;
			} else if(strcasecmp($param->app,$this->config->project->kode->sgift)== 0) {
				$kode = $this->config->project->kode->sgift;
			}
			
			if(strcasecmp($param->mode,$this->CHG_EMAIL)!=0) {
				if(strcasecmp($param->mode,$this->UNSUSPEND)== 0) {
					$subject = $this->config->mail->subject->activate->$kode;
				} else {
					$subject = $this->config->mail->subject->suspend->$kode;
				}
			} else {
				$subject = $this->config->mail->subject->approval->$kode;
			}
			
			// email 
			$to = explode(",",$param->to);
			
			if (strcasecmp($param->mode,$this->CHG_EMAIL)!=0) {
				// Send Email REQUEST KE PM BUAT SUSPEND USER
				$link_yes = $this->serverUrl.$this->url."/mailer/confirm?ans=1&app=".$param->app."&mode=".$param->mode."&id=".$historyID;
				$link_no = $this->serverUrl.$this->url."/mailer/noconfirm?ans=2&app=".$param->app."&mode=".$param->mode."&id=".$historyID;
				$btn_yes = $this->library->html->createLink("Yes", $link_yes, Html::$TARGET_NEW_TAB, "btn btn-primary");
				$btn_no = $this->library->html->createLink("No", $link_no, Html::$TARGET_NEW_TAB, "btn btn-primary");
				
				$template = $this->library->html->openTemplate($this->config->mail->body->suspend);
				$prm = array( "mode" => $param->mode,
							  "name" => $param->name,
							  "app" => $param->app,
							  "reason" => $param->reason,
							  "btn_yes" => $btn_yes,
							  "btn_no" => $btn_no );
				$content = $this->library->html->compileHtmlParam($template, $prm, true);
			} else {
				// Send Email BUAT KASIH TAU USER KALO EMAILNYA DISUSPEND SEMENTARA SAMPE ADMIN BERAKSI
				$template = $this->library->html->openTemplate($this->config->mail->body->suspendChange);
				$prm = array( "name" => $param->name,
							  "historyID" => $historyID,
							  "date" => date('Y-m-d H:i:s'),
							  "email" => $param->email,
							  "newemail" => $param->newemail,
							  "app" => $param->app,
							  "reason" => $param->reason );
				$content = $this->library->html->compileHtmlParam($template, $prm, true);							
							
				$config = array("from" => $this->config->smtp->mail->username,
								"from_name" => $this->config->smtp->mail->from,
								//"to" => $param->email,
								"to" => "operak004@gmail.com",
								"subject" => $param->app." - Change Email [REQ]",
								"body" => $this->library->html->openHtmlTag($content));
				$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
				
				$dataToInsert = array(
										"userID" => $userID,
										"to_email" => $param->email,
										"subject" => $subject,
										"messages" => $this->library->html->openHtmlTag($content));
				$res = $this->db_sent->insert($dataToInsert);
				
				//suspend user
				//kalo slime
				if(strcasecmp($param->app, $this->config->project->kode->slime)== 0)
				{
					$data = array("deviceIdentifierId" => $param->device_identifier,
								  "deviceIdentifierType" => $param->device_type,
								  "reason" => $reason);
					$object = $this->getFrontController()->getParam("bootstrap")
														 ->hitServices($this->config->services->slime->suspend, $data);
					$this->suspendOrActivate($this->config->project->kode->slime, $param->device_identifier, "disabled");
				}
				else if(strcasecmp($param->app, $this->config->project->kode->sgift)== 0)
				{
					$data = array("deviceIdentifierId" => $param->device_identifier,
								  "deviceIdentifierType" => $param->device_type,
								  "reason" => $reason);
					
					// No Action to API
					$this->suspendOrActivate($this->config->project->kode->sgift, $param->device_identifier, "disabled");
				}
				
				//send to admin REQUEST KE PM BUAT GANTI EMAIL
				// $val = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);							
				$link_yes = $this->serverUrl.$this->url."/mailer/confirm?ans=1&app=".$param->app."&mode=".$param->mode."&id=".$historyID."&newemail=".$param->newemail;
				$link_no = $this->serverUrl.$this->url."/mailer/noconfirm?ans=2&app=".$param->app."&mode=".$param->mode."&id=".$historyID."&newemail=".$param->newemail;
				$btn_yes = $this->library->html->createLink("Yes", $link_yes, Html::$TARGET_NEW_TAB, 'btn btn-primary');
				$btn_no = $this->library->html->createLink("No", $link_no, Html::$TARGET_NEW_TAB, 'btn btn-primary');
				
				$template = $this->library->html->openTemplate($this->config->mail->body->changeEmail);
				$prm = array("name" => $param->name,
							 "email" => $param->email,
							 "newemail" => $param->newemail,
							 "app" => $param->app,
							 "reason" => $param->reason,
							 "btn_yes" => $btn_yes,
							 "btn_no" => $btn_no);
				$content = $this->library->html->compileHtmlParam($template, $prm, true);
			}
			
			$from_name = (strcasecmp($param->app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
			$config = array("from" => $this->config->smtp->mail->username,
							"from_name" => $this->config->smtp->mail->from,
							"to" => "",
							"subject" => $subject,
							"body" => $this->library->html->openHtmlTag($content));
							
			// Insert Database
			$userID = $this->mySession->id;
			
			foreach ($to as $t) : 
				$config["to"] = $t;
				$dataToInsert = array(
										"userID" => $userID,
										"to_email" => $t,
										"subject" => $subject,
										"messages" => $this->library->html->openHtmlTag($content));
				$res = $this->db_sent->insert($dataToInsert);
				
				$val = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
			endforeach; 
			
			echo 1;
		}
		else
		{
			echo -1;
		}
	}
		
	public function gethistoryAction()
	{
		// Disable View 
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		$this->getFrontController()->getParam("bootstrap")->logToFirebug($param->deviceid);
		$res = $this->db_historysuspend->getHistorySuspendByImei($param->deviceid, $param->app);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($res);
		echo Zend_Json::encode($data);
	}
	
	// -- Testing Purpose --
	public function changeemailAction(){
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) array( "deviceid" => "356001060065495",
								 "app" => $this->config->project->kode->slime,
								 "newemail" => "test123@gmail.com"
								);
		
		$res = "";
		switch(strtolower($param->app))
		{
			case $this->config->project->kode->slime :
					$this->changeEmail($this->config->project->kode->slime, $param->deviceid, $param->newemail);
					/* $data = array("deviceIdentifierId" => $param->deviceid, 
								  "deviceIdentifierType" => $param->type,
								  "email" => $param->newemail);
					$object = $this->getFrontController()->getParam("bootstrap")
														 ->hitServices($this->config->services->slime->reset, $data); */ break;
			case $this->config->project->kode->sgift :
					$this->changeEmail($this->config->project->kode->sgift, $param->deviceid, $param->newemail);break;
		}
		echo "Success ";
	}
	
	public function suspendAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) array( "deviceid" => "356001060065495",
								 "app" => $this->config->project->kode->slime,
								 "type" => "enabled"
								);
		
		$res = "";
		switch(strtolower($param->app))
		{
			case $this->config->project->kode->slime :
					$this->suspendOrActivate($this->config->project->kode->slime, $param->deviceid, $param->type);
					/* $data = array("deviceIdentifierId" => $param->deviceid, 
								  "deviceIdentifierType" => $param->type,
								  "email" => $param->newemail);
					$object = $this->getFrontController()->getParam("bootstrap")
														 ->hitServices($this->config->services->slime->reset, $data);  */break;
			case $this->config->project->kode->sgift :
					$this->suspendOrActivate($this->config->project->kode->sgift, $param->deviceid, $param->type);break;
		}
		echo "Success ";
	}
}