<?php
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "LogController.php";

class IndexController extends Zend_Controller_Action
{
	var $mail, $transport;
	var $db_user, $db_sgift;
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
		
		// Create Connection
		$this->conn = (object) array("host" => $this->config->resources->db->host,
									"username" => $this->config->resources->db->username,
									"pass" => $this->config->resources->db->pass,
									"db" => $this->config->resources->db->db);
		
		$this->conn_postgre = (object) array("host" => $this->config->resources->db->postgre->host,
									"username" => $this->config->resources->db->postgre->username,
									"pass" => $this->config->resources->db->postgre->pass,
									"db" => $this->config->resources->db->postgre->db);
										
		// Check Login Session
		$this->getFrontController()->getParam("bootstrap")->checkValid();
		
		// Create entity of Database
		$this->db_user = new Db_Pg_User($this->conn_postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		
		$this->initView();
    }
	
    public function indexAction()
    {
        // action body
    }
	
	// Added by kovan.c
	// 18 / 08 / 2014
	public function chkloginAction()
	{
		$username = $this->getRequest()->getPost("username");
		$password = $this->getRequest()->getPost("password");
		if($username=="") {
			$hasil="Please insert your username";
		} else if($password=="") {
			$hasil="Please insert your password";
		}
		else {
			$userList = $this->db_user->searchLogin($username,$password);
			$check = false;
			if( count($userList) <= 0 ) {
				$hasil="Username is not registered.";
			} else  {
				foreach ($userList as $user) : 
					if (strcmp($user->pass, $this->db_user->encrypt($password)) == 0) {
						$check =true;
						if ($user->active == 1) {
							$hasil="Success Login";
							$this->mySession->unlock();
							$this->mySession->id = $user->userID;
							$this->mySession->username = $username;
							$this->mySession->password = $password;
							$this->mySession->role = $user->roleID;
							$this->mySession->rolename = $user->rolename;
							$this->mySession->project = $this->db_user->getProjectByUser($user->userID);
							$this->mySession->provider_id = $user->provider_id;
							$this->mySession->publisher_id = $user->publisher_id;
							$this->mySession->provider_name = ($user->roleID == $this->config->role->id->merchant) ? $this->db_sgift->getMerchantNameByID($user->provider_id) : $this->db_sgift->getProviderNameByID($user->provider_id);
							$this->mySession->merchants = $this->db_user->getMerchantByUser($user->userID);
							$this->mySession->segments = $this->db_user->getSegmentByUser($user->userID);
							$this->mySession->lock();
							
							// Log the action
							LogController::insert($this->conn_postgre,
												  $this->config->template->log->view,
												  $this->config->activity->id->view,
												  $this->mySession->id,
												  "MAIN PAGE"
												  );
							
							$this->_redirect("main");
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
	}
	
	public function forgetpassAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$username = $this->getRequest()->getPost("user");
		
		if ($this->db_user->chkExists($username) == true)
		{
			$hasil = $this->db_user->forgetPass($username);
			
			// Send Email
			$config = array("from" => 'admin.reporting@srinapps.com',
							"from_name" => $this->config->smtp->mail->from,
							"to" => $username,
							"subject" => "Forgot Password",
							"body" => "<html><head></head>
										<body>
											Dear User,
											<br/><br/>
											We have processed your request to change your password, here is your new password : <strong>abc123</strong>
											<br/><br/>
											Warm Regards,
											Reporting System Administrator

										</body></html>");
			
			echo $this->getFrontController()->getParam("bootstrap")->sendEmail($config);
		}
		else
		{
			echo 2;
		}
	}
}

