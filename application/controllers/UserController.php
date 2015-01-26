<?php
// Load Needed Model
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_role/Db_Pg_Role.php";
require_once "db_pg_segment/Db_Pg_Segment.php";
require_once "LogController.php";

class UserController extends Zend_Controller_Action
{
	var $first;
	var $url;
	var $conn;
	var $mySession;
	var $library;
	var $config;
	var $db_user, $db_project, $db_role, $db_sgift, $db_segment, $db_slime;
	public function init()
	{
		//Init View
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;
		
		//Initialize Session
		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->view->mySession = $this->mySession;
	
		/*Get Configuration Value*/
		$this->config = Zend_Registry::get('config');
		$this->view->config = $this->config;
				
		// Include Custom Library
		$this->library = (object) array( "validate" => new Validate() );
		
		// Check Valid Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		$this->view->attr = (object) array( "title" => "User Management" );
		
		// Create entity of Database
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_role = new Db_Pg_Role($this->config->resources->db->postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->newslime->postgre);
		$this->db_segment = new Db_Pg_Segment($this->config->resources->db->postgre);
		
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

	// Created by :  Kelvin Tham
	// ++ 15 / 08 / 2014
	// View User 
	public function indexAction()
	{
		// Get Parameter 
		$param = (object) $this->getRequest()->getQuery();
		
		// Set Notification
		$notif = "";
		if ($this->mySession->flagAdd == 1) {
			$notif = "User has been added successfully.";
			unset($this->mySession->flagAdd);
		} else if ($this->mySession->flagAdd == 2) {
			$notif = "User has been edited successfully.";
			unset($this->mySession->flagAdd);
		} else if ($this->mySession->flagAdd == 3) {
			$notif = "User has been deleted successfully.";
			unset($this->mySession->flagAdd);
		}
		
		//set amount of page for left & right of active page
		$side=2;
		//amount of data to be shown
		$limit=4;
		//calculate total page
		$rec_count=$this->db_user->getCountByActive();
		$total_page= ceil($rec_count/$limit);//get total page
		if ( $this->getRequest()->getQuery('page')  != "" ){
			$page=$this->getRequest()->getQuery('page')+1;
			$offset=$limit * ($page-2);// offset where we begin the search 
		}
		else{
			$page=2;
			$offset=0;
		
		}
		$this->view->offset= $offset;
		$left_rec=$rec_count - (($page-1) * $limit);
			$temp_projectName = "";
			$temp_username = "";
			$temp_data = array();
			$counter=-1;
			
			// Search
			$dataToSearch = array(
									"username" => $param->username,
									"mu.roleid" => $param->role,
									"TO_CHAR(mu.created_date,'YYYY-MM-DD')" => $param->created_date,
									"active::text" => $param->active
								  );
									
			 $userList = $this->db_user->getUserForTable($param->q, $dataToSearch);
			
			// Logging Function 
			LogController::insert($this->config->resources->db->postgre,
								  $this->config->template->log->view,
								  $this->config->const->activity->view,
								  $this->mySession->id,
								  "USER",
								  ""
								 );
								 
			foreach($userList as $row) :
				
				if( $row->username == $temp_username){	
					$temp_projectName = $row->projectName;
					$temp_data[$counter]['projectName'] .= ", ".$temp_projectName;
					
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
				$temp_username = $row->username;
			
			endforeach;
			$last=$page-2;
			
			$bound_r=($page+$side<$total_page)?($page+$side):$total_page;
			$bound_l=($page-$side>0)?($page-$side):1;
			if($bound_r-$page < $side){
			  $bound_l=$bound_l - ($side-($bound_r-$page));     
			}
			if($page-$bound_l<$side){
			  $bound_r=$bound_r+($side-($page-$bound_l));
			}
			if($bound_l<0){
				$bound_l=1;
			}
			
			$this->view->roleList = $this->db_role->getRole();
			$this->view->userList = $temp_data;
			$this->view->userData = Zend_Json::encode($temp_data);
			$this->view->limit = $limit;
			$this->view->last = $last;
			$this->view->bound_r = $bound_r;
			$this->view->bound_l = $bound_l;
			$this->view->total_page = $total_page;
			$this->view->page = $page;
			$this->view->param = $param;
			$this->view->notif = $notif;
	}
	
	// Created by :  Pinto Luhur
	// ++ 15 / 08 / 2014
	// Create Admin / user
	public function insertAction()
	{
		$this->view->merchantList = $this->db_sgift->getMerchantType();
		$this->view->providerList = $this->db_sgift->getProvider();
		$this->view->segmentList = $this->db_segment->getSegment();
		$this->view->publisherList = $this->db_slime->getPublisher();
		$this->view->roles = $this->db_role->getRole();
	}
	
	public function chkinsAction()
	{
		//Receive Request
		$param = (object) $this->getRequest()->getPost();
		
		//Validation
		$error = "";
		$error_email = "";
		$error_pass = "";
		$error_retype = "";
		$error_project = "";
		$valid = false;
		
		if(!$valid)
		{
			if ($param->email == "") {
				$error_email = "Email must be filled.";
			} else if ($this->library->validate->chkEmail($param->email) == false) {
				$error_email = "Your email is in invalid format.";
			} else if ($this->db_user->chkExists($param->email) == true) {
				$error_email = "Email has been taken, please use another email.";
			} else if ($param->merchant_type == 0 && $param->roleRadio == $this->config->role->id->merchant) {
				$error_merchant = "Please choose the merchant name first.";
			} else if ($param->aggregator_type == 0 && $param->roleRadio == $this->config->role->id->aggregator) {
				$error_aggregator = "Please choose the aggregator name first.";
			} else if ($param->publisher_type == "" && $param->roleRadio == $this->config->role->id->publisher) {
				$error_publisher = "Please choose the publisher name first.";
			}else if (!$param->cbPro && $param->roleRadio != $this->config->role->id->superadmin && $param->roleRadio != $this->config->role->id->publisher 
					  && $param->roleRadio != $this->config->role->id->merchant && $param->roleRadio != $this->config->role->id->aggregator ) {
				$error_project = "Please choose project first.";
			} else {
				$valid = true;
			}
			$valid2 = false;
			
			if (strlen($param->pass) < 6) {
				$error_pass = "Password at least contains 6 characters.";
			} else if ($this->library->validate->chkAlNum($param->pass) == false) {
				$error_pass = "Password must be alphanumeric.";
			} else if (strcmp($param->retype,$param->pass)!=0) {
				$error_retype = "Retype password must be same with the password.";
			} else {
				$valid2 = true;
			}
		}
		
		if($valid && $valid2)
		{
			//insert to database
			$dataToInsert = array(
									"username" => $param->email,
									"pass" => $param->pass,
									"roleID" => $param->roleRadio,
									"active" => ($param->chkactive == "") ? 0 : $param->chkactive,
									"provider_id" => ($param->roleRadio == $this->config->role->id->merchant) ? $param->merchant_type : $param->aggregator_type,
									"publisher_id" => $param->publisher_type
									);
									
			if ($param->roleRadio == $this->config->role->id->aggregator || $param->roleRadio == $this->config->role->id->merchant) {
				$projects = array($this->config->project->id->sgift);
			} else if ($param->roleRadio == $this->config->role->id->publisher) {
				$projects = array($this->config->project->id->slime);
			} else if ($param->roleRadio == $this->config->role->id->superadmin) {
				$projects = array($this->config->project->id->slime,
								  $this->config->project->id->sgift,
								  $this->config->project->id->salaam,
								  $this->config->project->id->sfit,
								  $this->config->project->id->sair);
			} else {
				$projects = $param->cbPro;
			}
			$merchants = $param->cbMerchant;
			$segments = $param->cbSegment;
			
			$res = $this->db_user->insert($dataToInsert, $projects, $merchants, $segments);
			
			// Logging
			$dataToInsert['pass'] = "*********";
			LogController::insert($this->config->resources->db->postgre,
								  $this->config->template->log->add,
								  $this->config->const->activity->add,
								  $this->mySession->id,
								  "ADD USER",
								  $dataToInsert
								 );
								 
			$config = array("from" => 'admin.reporting@srinapps.com',
							"from_name" => $this->config->smtp->mail->from,
							"to" => $param->email,
							"subject" => "Invited to SRIN Reporting System",
							"body" => "<html><head></head>
										<body>
											Dear User,
											<br/><br/>
											You are invited in SRIN Reporting System, here is your password : <strong>$param->pass</strong>
											<br/><br/>
											Warm Regards,
											Reporting System Administrator
										</body></html>");
			
			$this->getFrontController()->getParam("bootstrap")->sendEmail($config);
			// Set Session
			$this->mySession->unlock();
			$this->mySession->flagAdd = 1;
			$this->mySession->lock();
			
			$this->_redirect("user");
		}
		
		$this->view->error_email = $error_email;
		$this->view->error_pass = $error_pass;
		$this->view->error_retype = $error_retype;
		$this->view->error_project = $error_project;
		$this->view->merchantList = $this->db_sgift->getMerchantType();
		$this->view->providerList = $this->db_sgift->getProvider();
		$this->view->segmentList = $this->db_segment->getSegment();
		$this->view->publisherList = $this->db_slime->getPublisher();
		$this->view->roles = $this->db_role->getRole();
		$this->view->cache = $param;
	}
	
	// Created by :  Albert Ricia
	// ++ 15 / 08 / 2014
	// Edit Admin / user
	public function editAction()
	{
		// Receive Parameter
		$userID = $this->getRequest()->getQuery("id");
		
		$this->view->merchantList = $this->db_sgift->getMerchantType();
		$this->view->providerList = $this->db_sgift->getProvider();
		$this->view->segmentList = $this->db_segment->getSegment();
		$this->view->publisherList = $this->db_slime->getPublisher();
		$this->view->user = $this->db_user->getUserById($userID);
		$this->view->project = $this->db_user->getProjectByUser($userID);
		$this->view->merchant = $this->db_user->getMerchantByUser($userID);
		$this->view->segment = $this->db_user->getSegmentByUser($userID);
		$this->view->roles = $this->db_role->getRole();
	}
	
	public function chkeditAction()
	{	
		// Receive Request
		$param = (object) $this->getRequest()->getPost();
		
		//Validation
		$error = "";
		$error_email = "";
		$error_project = "";
		$valid = false;
		
		if(!$valid)
		{
			if ($param->email == "") {
				//$error = "Username must be filled.";
				$error_email = "Username must be filled.";
			} else if ($this->library->validate->chkEmail($param->email) == false) {
				//$error = "Your email is in invalid format.";
				$error_email = "Your email is in invalid format.";
			} else if ($this->db_user->chkExistsNotOld($param->email, $param->userID) == true) {
				//$error = "Email has been taken, please use another email.";
				$error_email = "Email has been taken, please use another email.";
			} else if ($param->merchant_type == 0 && $param->roleRadio == $this->config->role->id->merchant) {
				$error_merchant = "Please choose the merchant name first.";
			} else if ($param->aggregator_type == 0 && $param->roleRadio == $this->config->role->id->aggregator) {
				$error_aggregator = "Please choose the aggregator name first.";
			} else if ($param->publisher_type == "" && $param->roleRadio == $this->config->role->id->publisher) {
				$error_publisher = "Please choose the publisher name first.";
			}else if (!$param->cbPro && $param->roleRadio != $this->config->role->id->superadmin && $param->roleRadio != $this->config->role->id->publisher 
					  && $param->roleRadio != $this->config->role->id->merchant && $param->roleRadio != $this->config->role->id->aggregator ) {
				$error_project = "Please choose project first.";
			}else {
				$valid = true;
			}
		}
		
		if($valid)
		{
			// Edit to Database
			$dataToEdit = array(
								"userID" => $param->userID,
								"roleID" => $param->roleRadio,
								"username" => $param->email,
								"pass" => $param->pass,
								"active" => ($param->chkactive == "") ? 0 : $param->chkactive,
								"provider_id" => ($param->roleRadio == $this->config->role->id->merchant) ? $param->merchant_type : $param->aggregator_type,
								"publisher_id" => $param->publisher_type
								);
								
			if ($param->roleRadio == $this->config->role->id->aggregator || $param->roleRadio == $this->config->role->id->merchant) {
				$projects = array($this->config->project->id->sgift);
			} else if ($param->roleRadio == $this->config->role->id->publisher) {
				$projects = array($this->config->project->id->slime);
			} else if ($param->roleRadio == $this->config->role->id->superadmin) {
				$projects = array($this->config->project->id->slime,
								  $this->config->project->id->sgift,
								  $this->config->project->id->salaam,
								  $this->config->project->id->sfit,
								  $this->config->project->id->sair);
			} else {
				$projects = $param->cbPro;
			}
			
			$merchants = $param->cbMerchant;
			$segments = $param->cbSegment;
			
			$res = $this->db_user->update($dataToEdit, $projects, $merchants, $segments);
			
			// Logging
			$dataToEdit['pass'] = "*********";
			LogController::insert($this->config->resources->db->postgre,
								  $this->config->template->log->edit,
								  $this->config->const->activity->edit,
								  $this->mySession->id,
								  "UPDATE USER",
								  $dataToEdit
								 );
			
			// Set Session
			$this->mySession->unlock();
			$this->mySession->project = $this->db_user->getProjectByUser($this->mySession->id);
			$this->mySession->flagAdd = 2;
			$this->mySession->lock();
		
			$this->_redirect("user");
		}
		
		$userID = $param->userID;
		//$this->view->error = $error;
		$this->view->error_email = $error_email;
		$this->view->error_aggregator = $error_aggregator;
		$this->view->error_merchant = $error_merchant;
		$this->view->error_project = $error_project;
		$this->view->merchantList = $this->db_sgift->getMerchantType();
		$this->view->providerList = $this->db_sgift->getProvider();
		$this->view->publisherList = $this->db_slime->getPublisher();
		$this->view->segmentList = $this->db_segment->getSegment();
		$this->view->user = $this->db_user->getUserById($userID);
		$this->view->project = $this->db_user->getProjectByUser($userID);
		$this->view->merchant = $this->db_user->getMerchantByUser($userID);
		$this->view->cache = $param;
		$this->view->roles = $this->db_role->getRole();
	}
	
	public function deleteAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Receive Parameter
		$id = $this->getRequest()->getQuery("id");
		
		if ($id != $this->mySession->id) {
			$this->db_user->delete($id);
		}
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->delete,
							  $this->config->const->activity->delete,
							  $this->mySession->id,
							  "DELETE USER",
							  $id
							 );
		// Set Session
		$this->mySession->unlock();
		$this->mySession->flagAdd = 3;
		$this->mySession->lock();
			
		$this->_redirect("user");
	}
}