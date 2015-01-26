<?php 
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_sent/Db_Pg_Sent.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_historysuspend/Db_Pg_Historysuspend.php";
require_once "db_pg_customer/Db_Pg_Customer.php";
require_once "LogController.php";

class MailerController extends Zend_Controller_Action
{
	static $CHG_EMAIL = "email", $SUSPEND = "suspend", $UNSUSPEND = "activate";
	static $STATE_TEST = "test", $STATE_RELEASE = "release";
	static $state = "test";
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
	
	public function indexAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();

		$this->_redirect("mailer/confirm");
	}
	
	// ---------------------------------------------------------------------------------
	// USEFUL FUNCTION
	// ---------------------------------------------------------------------------------
	
	public function suspendOrActivate($app, $device_identifier, $type = "enabled")
	{
		if ($app == $this->config->project->kode->sgift) {
			$this->db_sgift->suspendOrActivateCustomer($device_identifier, $type);
		} else if ($app == $this->config->project->kode->slime) {
			$this->db_slime->suspendOrActivateCustomer($device_identifier, $type);
		}
		$this->db_customer->suspendorActivate($app,$device_identifier, $type);
	}
	
	public function changeEmail($app, $device_identifier, $new_email)
	{
		if ($app == $this->config->project->kode->sgift) {
			$this->db_sgift->changeEmail($device_identifier, $new_email);			
		} else if ($app == $this->config->project->kode->slime) {
			$this->db_slime->changeEmail($device_identifier, $new_email);
		}
		$this->db_customer->changeEmail($app, $device_identifier, $new_email);
	}
	
	// ---------------------------------------------------------------------------------
	// SUSPEND AND ACTIVATE 
	// ---------------------------------------------------------------------------------
	
	//KETIKA PM SAY YES BUAT SUSPEND dan ACTIVATE dan CHANGE EMAIL
	public function confirmAction(){
		//Get Parameter 
		$param = (object) $this->getRequest()->getQuery();
		
		// Check exists
		if ( $this->db_historysuspend->isApprove($param->id) == true) {
			$this->_redirect("error/error999");
		}
		
		$historyID = $param->id;
		$row = $this->db_historysuspend->getHistorySuspendById($historyID);
		
		$mode = (strcasecmp($param->mode, "email") == 0) ? "change email" : $param->mode;
		$subject = 'Approve to '.$param->mode.' user '. $row->email;
		
		
		// Add Subject
		$this->view->param = $param;
		$this->view->subject = $subject;
	}
	
	public function sendMailSuspendtoAll($param, $row, $content, $subject, $from = "")
	{
		if(strcasecmp($row->app, $this->config->project->kode->slime)==0) {
			$to = $this->db_user->getUserByProject($this->config->project->id->slime);
		} else if(strcasecmp($row->app, $this->config->project->kode->sgift)==0) {
			$to = $this->db_user->getUserByProject($this->config->project->id->sgift);
		}
		// if ($this->state == self::$STATE_RELEASE) {
			// foreach($to as $target):
				// Send E-mail
				$config = array("from" => $this->config->smtp->mail->username,
								"from_name" => $from,
								//"to" => $target->username,
								"to" => "operak004@gmail.com",
								"subject" => $subject,
								"body" => $this->library->html->openHtmlTag($content));
				$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
				
				$dataToInsert = array(
										"userID" => $row->userID,
										"to_email" => $target->username,
										"subject" => $subject,
										"messages" => $this->library->html->openHtmlTag($content));
				 $res = $this->db_sent->insert($dataToInsert);
			// endforeach;
		// }
	}
	
	//KETIKA PM SAY YES TO SUSPEND
	public function saveconfirmAction(){
		// Get Parameter Post
		$param = (object) $this->getRequest()->getPost();
		$username = $param->username;
		$password = $param->password;
		$newemail = $param->hid_mail_newemail;
		$reason = $param->reason;
		
		$success = 0;
		if($username=="") {
			$hasil="Please insert your username";
		} else if($password=="") {
			$hasil="Please insert your password";
		}
		else {
			$res = $this->db_user->searchLogin($username,$password);
			$check = false;
			if( count($res) <= 0 ) {
				$hasil="Username is not registered.";
			} else  {
				foreach ($res as $user) :
					
					if (strcmp($user->pass, $this->db_user->encrypt($password)) == 0) {
						$check =true;
						if ($user->active == 1) {
						
							// Save Session
							$hasil = "Success Login";
							$this->mySession->unlock();
							$this->mySession->id = $user->userID;
							$this->mySession->username = $username;
							$this->mySession->password = $password;
							$this->mySession->role = $user->roleID;
							$this->mySession->project = $this->db_user->getProjectByUser($user->userID);
							$this->mySession->lock();
							
							// Log the action
							$userID = $this->mySession->id;
							$dataToEdit = array(
													"historyID" => $param->hid_mail_id,
													"approve_status" => $param->hid_confirm,
													"approveBy" => $userID,
													"reason_approve" => $reason
												);
							
							$res_sus = $this->db_historysuspend->update($dataToEdit);
												
							LogController::insert($this->config->resources->db->postgre,
												  $this->config->template->log->add,
												 $this->config->const->activity->view,
												  $this->mySession->id,
												  "APPROVAL FORM",
												  $dataToEdit
												  );
							
							// Hit the API to suspend
							$row = $this->db_historysuspend->getHistorySuspendById($param->hid_mail_id);
							$user = $this->db_user->getUserById($row->userID);
							if ($row != NULL)
							{								
								if ($param->hid_confirm == 1) 
								{
									if (strcasecmp($param->hid_mode, self::$CHG_EMAIL) != 0)
									{
										switch(strtolower($param->hid_app))
										{
											case $this->config->project->kode->slime :
													if (strcasecmp($param->hid_mode, self::$SUSPEND) == 0) {
														$data = array("deviceIdentifierId" => $row->device_identifier,
																	  "deviceIdentifierType" => $row->device_type,
																	  "reason" => $reason);
														$object = $this->getFrontController()->getParam("bootstrap")
																							 ->hitServices($this->config->services->slime->suspend, $data);
																							 
														// print_r($object);
														$this->suspendOrActivate($this->config->project->kode->slime, $row->device_identifier, "disabled");
													
														// Send mail to All
														$template = $this->library->html->openTemplate($this->config->mail->body->yesSuspend);
														$prm = array( "username" => $target->username,
																	  "historyID" => $historyID,
																	  "date" => date('Y-m-d H:i:s'),
																	  "email" => $row->email,
																	  "app" => $param->hid_app,
																	  "reason" => $row->reason_actions);
														$content = $this->library->html->compileHtmlParam($template, $prm, true);
														$subj = "SRIN ".ucfirst($row->app)." - Suspend User [APP]";
														$this->sendMailSuspendtoAll($param, $row, $content, $subj, $this->config->smtp->mail->slime->from);
														
														// Send E-mail
														$template = $this->library->html->openTemplate($this->config->mail->body->yesInformSuspend);
														$prm = array("app" => $row->app);
														$content = $this->library->html->compileHtmlParam($template, $prm, true);
														$config = array("from" => $this->config->smtp->mail->username,
																		"from_name" => $this->config->smtp->mail->from,
																		"to" => $row->email,
																		"subject" => ucfirst($row->app)." - Your account has been suspended",
																		"body" => $this->library->html->openHtmlTag($content));
														$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
														
														// Insert into Table Sent
														$dataToInsert = array(
																				"userID" => $row->userID,
																				"to_email" => $row->email,
																				"subject" => "Suspended Account confirmation to user",
																				"messages" => $this->library->html->openHtmlTag($content));
														$res = $this->db_sent->insert($dataToInsert);	
														

													} else if (strcasecmp($param->hid_mode, self::$UNSUSPEND) == 0) {
														$data = array("deviceIdentifierId" => $row->device_identifier,
																	  "deviceIdentifierType" => $row->device_type);
														$object = $this->getFrontController()->getParam("bootstrap")
																							 ->hitServices($this->config->services->slime->unsuspend, $data);
														$this->suspendOrActivate($this->config->project->kode->slime, $row->device_identifier, "enabled");
													
														// Send E-mail
														$template = $this->library->html->openTemplate($this->config->mail->body->yesActivate);
														$prm = array("username" => $target->username,
																	 "historyID" => $historyID,
																	 "date" => date('Y-m-d H:i:s'),
																	 "email" => $target->email,
																	 "app" => $param->app,
																	 "reason" => $row->reason_actions);
														$content = $this->library->html->compileHtmlParam($template, $prm, true);					
														$subj = "SRIN ".ucfirst($row->app)." - Activate User [APP]";
														$from_name = (strcasecmp($row->app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
														$this->sendMailSuspendtoAll($param, $row, $content, $subj, $from_name);																

														//inform user
														// Send E-mail
														$template = $this->library->html->openTemplate($this->config->mail->body->userInform);
														$prm = array("app" => $row->app);
														$content = $this->library->html->compileHtmlParam($template, $prm, true);
														$config = array("from" => $this->config->smtp->mail->username,
																		"from_name" => $this->config->smtp->mail->from,
																		"to" => $row->email,
																		"subject" => ucfirst($row->app)." - Your account has been activated",
																		"body" => $this->library->html->openHtmlTag($content));
														$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
														
														// Insert into table sent
														$dataToInsert = array(
																				"userID" => $row->userID,
																				"to_email" => $row->email,
																				"subject" => "Activation Account confirmation to user",
																				"messages" => $this->library->html->openHtmlTag($content));
														$res = $this->db_sent->insert($dataToInsert);
														
													} ; break;
											case $this->config->project->kode->sgift : 
													if (strcasecmp($param->hid_mode, self::$SUSPEND) == 0) {
														$this->suspendOrActivate($this->config->project->kode->sgift, $row->device_identifier, "disabled");
														// No API To hit --
														
														// Send mail to All
														$template = $this->library->html->openTemplate($this->config->mail->body->yesSuspend);
														$prm = array( "username" => $target->username,
																	  "historyID" => $historyID,
																	  "date" => date('Y-m-d H:i:s'),
																	  "email" => $row->email,
																	  "app" => $param->hid_app,
																	  "reason" => $row->reason_actions);
														$content = $this->library->html->compileHtmlParam($template, $prm, true);
														$subj = "SRIN ".ucfirst($row->app)." - Suspend User [APP]";
														$this->sendMailSuspendtoAll($param, $row, $content, $subj, $this->config->smtp->mail->sgift->from);
														
													} else if (strcasecmp($param->hid_mode, self::$UNSUSPEND) == 0) {
														$this->suspendOrActivate($this->config->project->kode->sgift, $row->device_identifier, "enabled");
														// No API To hit --
													} ; break;
											
										}
										$this->view->message="You have approved to ".$param->hid_mode." ".$row->email;
										
										// Mode
										$md = (strcasecmp($param->hid_mode, self::$SUSPEND) == 0) ? "suspended" : "activated";
										
										// Send Email
										$from_name = (strcasecmp($param->hid_app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
										
										$template = $this->library->html->openTemplate($this->config->mail->body->suspendTicket);
										$prm = array("email" => $row->email,
													 "app" => $param->hid_app,
													 "mode" => $md,
													 "historyID" => $row->historyID,
													 "date" => date('d M y H:i',strtotime($row->created_date)),
													 "reason" => $param->reason);
													 
										$content = $this->library->html->compileHtmlParam($template ,$prm, true);
										$config = array("from" => $this->config->smtp->mail->username,
														"from_name" => $from_name,
														"to" => "operak004@gmail.com",
														"subject" => "SRIN ".strtoupper($param->hid_app)." a user has been ".strtoupper($param->hid_mode)." [APP]",
														"body" => $content);
										$this->getFrontController()->getParam("bootstrap")->sendEmail($config);
										
										// Send E-mail to Customer Service
										$config["to"] = $row->username;
										$config["subject"] = "[APPROVED] - ".strtoupper($param->hid_app)." - ACCOUNT ".strtoupper($param->hid_mode);
										$this->getFrontController()->getParam("bootstrap")->sendEmail($config);
									}
									//KETIKA PM SAY YES DAN KEMUDIAN USER YG HARUS MILIH SAY YES OR NO BUAT CHANGE EMAIL
									else 
									{
										$row->historyID = trim($row->historyID);
										switch(strtolower($param->hid_app))
										{
											//KHUSUS SLIME-----------------------------------
											case $this->config->project->kode->slime :
											$template = $this->library->html->openTemplate($this->config->mail->body->requestUserChange);
											$link_confirm = $this->serverUrl.$this->url."/mailer/confirmchangeemail?id=".$row->historyID."&hash=".sha1($row->historyID."IJKL")."&newemail=".$param->hid_mail_newemail;
											$link_notconfirm = $this->serverUrl.$this->url."/mailer/notconfirmemail?id=".$row->historyID."&hash=".sha1($row->historyID."IJKL")."&newemail=".$param->hid_mail_newemail;
											$prm = array( "historyID" => $row->historyID,
														 "date" => $row->created_date,
														 "email" => $row->email,
														 "newemail" => $param->hid_mail_newemail,
														 "app" => $row->app,
														 "reason" => $row->reason_actions,
														 "btn_confirm" => $link_confirm,
														 "btn_notconfirm" => $link_notconfirm );
											$content = $this->library->html->compileHtmlParam($template, $prm, true);
														
											$config = array("from" => $this->config->smtp->mail->username,
														"from_name" => $this->config->smtp->mail->slime->from,
														"to" => "operak004@gmail.com", // Harus diganti entar $row->email,
														"subject" => "S Lime - Change Email [REQ]",
														"body" => $this->library->html->openHtmlTag($content));
											$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
											// $config["to"] = $param->hid_mail_newemail;
											$config["to"] = "operak004@gmail.com";
											$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
											
											$dataToInsert = array(
																	"userID" => $userID,
																	"to_email" => $row->email,
																	"subject" => "S Lime - Change Email [REQ]",
																	"messages" => $this->library->html->openHtmlTag($content));
											$res = $this->db_sent->insert($dataToInsert);		
											
											//INFORM KE SEMUA KALO ADMIN UDAH APPROVE								
											// Send E-mail
											$template = $this->library->html->openTemplate($this->config->mail->body->afterAdminApproveChangeEmail);
											$prm = array("username" => $target->username,
														 "email" => $row->email,
														 "newemail" => $param->hid_mail_newemail);
											$content = $this->library->html->compileHtmlParam($template,$prm, true);
											$subj = ucfirst($row->app)." - Change Email [APP]";
											$this->sendMailSuspendtoAll($param, $row, $content, $subj, $this->config->smtp->mail->slime->from);
											// ------------
											
											$this->db_slime->changeEmail($row->device_identifier, $param->hid_mail_newemail);
											$data = array("deviceIdentifierId" => $row->device_identifier, 
														  "deviceIdentifierType" => $row->device_type,
														  "email" => $param->hid_mail_newemail);
											$object = $this->getFrontController()->getParam("bootstrap")
																				 ->hitServices($this->config->services->slime->reset, $data);
																				 
											$this->view->message = "Change Email for ".$row->email.", account SLime";
											break;
											
											//KHUSUS SGIFT-----------------------------------
											case $this->config->project->kode->sgift :
											
											$link_confirm = $this->serverUrl.$this->url."/mailer/confirmchangeemailsgift?id=".$row->historyID."&hash=".sha1($row->historyID."IJKL")."&newemail=".$param->hid_mail_newemail;
											$link_notconfirm = $this->serverUrl.$this->url."/mailer/notconfirmemail?id=".$row->historyID."&hash=".sha1($row->historyID."IJKL")."&newemail=".$param->hid_mail_newemail;
											
											$prm = array( "historyID" => $row->historyID,
														  "created_date" => $row->created_date,
														  "email" => $row->email,
														  "newemail" => $param->hid_mail_newemail,
														  "app" => $row->app,
														  "reason" => $row->reason_actions,
														  "link_confirm" => $link_confirm,
														  "link_notconfirm" => $link_notconfirm );
											$content = $this->library->html->compileHtmlParam($this->config->mail->body->requestUserChange, $prm); 
														
											$config = array("from" => $this->config->smtp->mail->username,
															"from_name" => $this->config->smtp->mail->sgift->from,
															"to" => "operak004@gmail.com", // Diganti entar $row->email,
															"subject" => "S Gift - Change Email [REQ]",
															"body" => $this->library->html->openHtmlTag($content));
											$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
											// $config["to"] = $param->hid_mail_newemail;
											$config["to"] ="operak004@gmail.com";
											$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
											
											$dataToInsert = array(
																	"userID" => $userID,
																	"to_email" => $row->email,
																	"subject" => "S Gift - Change Email [REQ]",
																	"messages" => $this->library->html->openHtmlTag($content));
											$res = $this->db_sent->insert($dataToInsert);		
											
											//INFORM KE SEMUA KALO ADMIN UDAH APPROVE
																					
											$to = $this->db_user->getUserByProject($this->config->project->id->sgift);
											
											foreach($to as $target):
												$content = "Dear <strong>".$target->username."</strong>, <br/><br/>
															Request to change ".$row->email." into ".$param->hid_mail_newemail." is approved by admin";
															
												$config = array("from" => $this->config->smtp->mail->username,
															"from_name" => $this->config->smtp->mail->sgift->from,
															"to" => $target->username,
															"subject" => "S Gift - Change Email [APP]",
															"body" => $this->library->html->openHtmlTag($content));
												$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
												$dataToInsert = array(
																		"userID" => $userID,
																		"to_email" => $target->username,
																		"subject" => "S Gift - Change Email [APP]",
																		"messages" => $this->library->html->openHtmlTag($content));
												$res = $this->db_sent->insert($dataToInsert);	
											endforeach;
											
											$this->db_sgift->changeEmail($row->device_identifier, $param->hid_mail_newemail);
											$data = array( "deviceIdentifierId" => $row->device_identifier, 
															"deviceIdentifierType" => $row->device_type,
															"email" => $param->hid_mail_newemail );
											$object = $this->getFrontController()->getParam("bootstrap")
																				 ->hitServices($this->config->services->slime->reset, $data);
											
											break;
										}
									}
								}
								// if admin rejects
								else{
									if (strcasecmp($param->hid_mode, self::$CHG_EMAIL) != 0) { 
										
										$mssg = 'Refuse to '.$param->hid_mode.' user '. $row->email;	
										$this->view->message = $mssg;	
										if(strcasecmp($param->hid_mode,$this->UNSUSPEND)== 0) {
											// Send E-mail
											$template = $this->library->html->openTemplate($this->config->mail->body->rejectActivate);
											$prm = array( "username" => $target->username,
														  "historyID" => $row->historyID,
														  "date" => date('Y-m-d H:i:s'),
														  "email" => $row->email,
														  "app" => $param->hid_app,
														  "reason" => $row->reason_actions );
											$content = $this->library->html->compileHtmlParam($template, $prm, true);
											$subj = "SRIN ".ucfirst($row->app)." - Activate User [REJ]";
											$from_name = (strcasecmp($row->app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
											$this->sendMailSuspendtoAll($param, $row, $content, $subj, $from_name);	
										} else {
											// Send E-mail
											$template = $this->library->html->openTemplate($this->config->mail->body->rejectSuspend);
											$prm = array( "username" => $target->username,
														  "historyID" => $row->historyID,
														  "date" => date('Y-m-d H:i:s'),
														  "email" => $row->email,
														  "app" => $param->hid_app,
														  "reason" => $row->reason_actions);
											$content = $this->library->html->compileHtmlParam($template, $prm, true);
											$subj = "SRIN ".$row->app." - Suspend User [REJ]";
											$from_name = (strcasecmp($row->app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
											$this->sendMailSuspendtoAll($param, $row, $content, $subj, $from_name);	
										}
									}
									else{
										//KHUSUS SLIME
										//case $this->config->project->name->slime :
			
										//send to user, he got rejected		
										$template = $this->library->html->openTemplate($this->config->mail->body->rejectUserChange);
										$prm = array("email" => $row->email,
													 "newemail" => $param->hid_mail_newemail,
													 "app" => $row->app);
										$content = $this->library->html->compileHtmlParam($template, $prm, true);
										$config = array("from" => $this->config->smtp->mail->username,
														"from_name" => $this->config->smtp->mail->slime->from,
														//"to" => $row->email,
														"to" => "operak004@gmail.com",
														"subject" => $row->app." - Change Email [REJ]",
														"body" => $this->library->html->openHtmlTag($content));
										$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
										$dataToInsert = array(
																"userID" => $userID,
																"to_email" => $row->email,
																"subject" =>"Change Email [REJ]",
																"messages" => $this->library->html->openHtmlTag($content));
										$res = $this->db_sent->insert($dataToInsert);	
										
										
										$this->view->message = "You have refused to change email ".$row->email;
										
										//inform customer service
										
										$template = $this->library->html->openTemplate($this->config->mail->body->rejectUserChange);
										$prm = array( "email" => $row->email,
													  "newemail" => $param->hid_mail_newemail,
													  "app" => $row->app);
										$content = $this->library->html->compileHtmlParam($template, $prm, true);
										$config = array("from" => $this->config->smtp->mail->username,
													"from_name" => $this->config->smtp->mail->slime->from,
													//"to" => $user->username,
													"to" => "a.riccia@partner.samsung.com",
													"subject" => $row->app." - Change Email [REJ]",
													"body" => $this->library->html->openHtmlTag($content));
										$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
										$dataToInsert = array(
																"userID" => $userID,
																"to_email" => $user->username,
																"subject" => "Change Email [REJ]",
																"messages" => $this->library->html->openHtmlTag($content));
										$res = $this->db_sent->insert($dataToInsert);
									}
								}
							} 
																 
							$hasil = "Success";
							$success = 1;
						}
						else {
							$hasil = "Account is not active.";
						}
					}
				endforeach;
				
				if ($check == false ) {
					$hasil = "Password is invalid.";
				}
			}
		}
		$this->view->error = $hasil;
		$this->view->success = $success;
		$this->view->param = (object) $this->getRequest()->getPost();
	}
	
	// -------------------------------------------------------------------------------------------------------------------
	
	//PM SAY NO BUAT SUSPEND
	public function noconfirmAction(){
		//Get Parameter 
		$param = (object) $this->getRequest()->getQuery();
		
		//Check exists
		if ( $this->db_historysuspend->isApprove($param->id) == true) {
			$this->_redirect("error/error999");
		}
		
		$this->view->param = $param;	
	}
	
	// ---------------------------------------------------------------------------------------------
	// Change E-mail
	// ---------------------------------------------------------------------------------------------
	
	//SETELAH USER SAY YES BUAT CHANGE EMAIL
	//KHUSUS SLIME
	public function confirmchangeemailAction(){
		$param = (object) $this->getRequest()->getQuery();
		$historyID = $param->id;
		$row = $this->db_historysuspend->getHistorySuspendById($historyID);
		$user = $this->db_user->getUserById($row->userID);
		$admin= $this->db_user->getUserById($row->approveBy);
		if(sha1($historyID."IJKL")==$param->hash)
		{
			//unsuspend
			$data = array("deviceIdentifierId" => $row->device_identifier,
						  "deviceIdentifierType" => $row->device_type);
			$object = $this->getFrontController()->getParam("bootstrap")
												 ->hitServices($this->config->services->slime->unsuspend, $data);
			$this->suspendOrActivate($this->config->project->kode->slime, $row->device_identifier, "enabled");
			
			//change email
			// $this->changeEmail($this->config->project->kode->slime, $row->device_identifier, $param->newemail);
			$data = array("deviceIdentifierId" => $row->device_identifier, 
							  "deviceIdentifierType" => $row->device_type,
							  "email" => $param->newemail);
			$object = $this->getFrontController()->getParam("bootstrap")
												 ->hitServices($this->config->services->slime->reset, $data);	
			//inform user
			$template = $this->library->html->openTemplate($this->config->mail->body->lastCsInfo);
			$prm = array(  "app" => $row->app );
			$content = $this->library->html->compileHtmlParam($template, $prm, true);

			$config = array("from" => $this->config->smtp->mail->username,
						"from_name" => $this->config->smtp->mail->from,
						"to" => "operak004@gmail.com", //diganti dengan $param->newemail,
						"subject" => "S Lime - Your mail address is changed",
						"body" => $this->library->html->openHtmlTag($content));
			$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
			
			$dataToInsert = array(
									"userID" => $row->userID,
									"to_email" => $row->email,
									"subject" => ucfirst($row->app)." - Your mail address is changed",
									"messages" => $this->library->html->openHtmlTag($content));
			$res = $this->db_sent->insert($dataToInsert);	
			
			//inform KE SEMUA KALO USER UDAH ACCEPT
			$template = $this->library->html->openTemplate($this->config->mail->body->lastAllInfo);
			$prm = array(  	"username" => $target->username,
							"email" => $row->email,
							"newemail" => $param->newemail,
							"app" => $row->app );
			$content = $this->library->html->compileHtmlParam($template, $prm, true);
			$subj = "SRIN ".ucfirst($row->app)." - user email address has changed [APP]";
			$from_name = (strcasecmp($row->app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
			$this->sendMailSuspendtoAll($param, $row, $content, $subj, $from_name);
			// --------
			
			$dataToEdit = array(
									"historyID" => $historyID,
									"approve_status" => 3,
									"approveBy" => $row->userID,
									"reason_approve" => ""
								);
							
			$res_sus = $this->db_historysuspend->update($dataToEdit);		     
		}
	}
	
	//KETIKA USER SAY NO BUAT CHANGE EMAIL
	public function notconfirmemailAction(){
		$param = (object) $this->getRequest()->getQuery();
		$historyID = $param->id;
		$row = $this->db_historysuspend->getHistorySuspendById($historyID);
		$user = $this->db_user->getUserById($row->userID);
		$admin = $this->db_user->getUserById($row->approveBy);
		if(sha1($historyID."IJKL")==$param->hash)
		{ 
			//unsuspend
			$data = array("deviceIdentifierId" => $row->device_identifier,
						  "deviceIdentifierType" => $row->device_type);
			$object = $this->getFrontController()->getParam("bootstrap")
												 ->hitServices($this->config->services->slime->unsuspend, $data);
			//INI MASIH BUAT YG SLIME DOANG
			$this->suspendOrActivate($this->config->project->kode->slime, $row->device_identifier, "enabled");
														
			//inform user
			$template = $this->library->html->openTemplate($this->config->mail->body->lastNotCsInfo);
			$prm = array(  "app" => $row->app );
			$content = $this->library->html->compileHtmlParam($template, $prm, true);
			
			$config = array("from" => $this->config->smtp->mail->username,
						"from_name" => $this->config->smtp->mail->from,
						//"to" => $row->email,
						"to" => "operak004@gmail.com",
						"subject" => $row->app." - Cancelled request Email [REJ]",
						"body" => $this->library->html->openHtmlTag($content));
			$val2 = $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
			
			
			$dataToInsert = array(
									"userID" => $row->userID,
									"to_email" => $row->email,
									"subject" => "Cancelled request Email by user [REJ]",
									"messages" => $this->library->html->openHtmlTag($content));
			$res = $this->db_sent->insert($dataToInsert);	
			
			//inform KE SEMUA KALO USER CANCEL GANTI EMAIL
			$template = $this->library->html->openTemplate($this->config->mail->body->lastNotAllInfo);
			$prm = array(  "username" => $target->username,
						   "email" => $row->email,
						   "newemail" => $param->newemail,
						   "app" => $row->app );
			$content = $this->library->html->compileHtmlParam($template, $prm, true);
			$subj = $row->app." - Change Email [REJ]";
			$from_name = (strcasecmp($param->hid_app, $this->config->project->kode->slime) == 0) ? $this->config->smtp->mail->slime->from : $this->config->smtp->mail->sgift->from;
			$this->sendMailSuspendtoAll($param, $row, $content, $subj, $from_name);
			
			$dataToEdit = array(
									"historyID" => $historyID,
									"approve_status" => 4,
									"approveBy" => $row->userID,
									"reason_approve" => ""
								);
								
			$res_sus = $this->db_historysuspend->update($dataToEdit);
		}											 
	}
}