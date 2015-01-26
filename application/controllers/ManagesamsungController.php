<?phprequire_once "db_install_samsungapp/Db_Install_Samsungapp.php";require_once "LogController.php";class ManagesamsungController extends Zend_Controller_Action{	var $url;	var $first;	var $conn;	var $db_install_samsungapp, $db_user, $db_project;	var $ID = 2;  	public function init()	{		//Init View		$this->url = $this->getRequest()->getBaseURL();		$this->view->baseUrl = $this->url;				//session		$this->mySession = new Zend_Session_Namespace("user_data");		$this->view->mySession = $this->mySession;				/*Get Configuration Value*/		$this->config=Zend_Registry::get('config');		$this->view->config = $this->config;						// Library 		$this->library = (object) array( "exporter" => new Exporter() );				// Property View		$this->view->attr = (Object) array( "title" => "Samsung App Management" );				// Check Session		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();				// Create entity of database		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);		$this->db_install_samsungapp = new Db_Install_Samsungapp($this->config->resources->db->postgre);				// Set Variable		$this->view->projects = $this->db_project->getVisibleProject();				$this->first = $this->db_user->checkFirst($this->mySession->id);				$this->checkFirst();				$this->initView();	}		public function checkAdmin()	{		if ((!isset($this->mySession->username) || $this->mySession->role != $this->config->role->id->superadmin) && (!isset($this->mySession->username) || $this->mySession->role != $this->config->role->id->admin)) {			$this->_redirect("");		}	}		public function checkFirst()	{		if($this->first==0){			$this->_redirect("");		}	}		public function indexAction(){		//$this->_redirect("managesamsung/insert");				$this->checkAdmin();				// Get Parameter 		$param = (object) $this->getRequest()->getQuery();		$this->view->notif = $param->notif;		$this->view->notif_message = $param->notif_message;				$data = array();			}		public function insertAction(){				$this->checkAdmin();				$param = (object) $this->getRequest()->getPost();				$returndb="nothing";		if($param->project_name!=null && $param->daily_install!=null && $param->install_date!=null){			$returndb  = $this->db_install_samsungapp->insertSamsungApp($param->project_name, $param->daily_install, $param->install_date);		}		$this->view->notif = $returndb;				if($returndb=='success') {			$this->view->param = null;			$this->_redirect("managesamsung?notif=success&notif_message=Success%20Insert%20Data");		}		else if($returndb=='failed') {			$this->view->param = $param;		} else if ($returndb=='not success') { 			$this->view->param = $param;			$this->view->notif = "Install Data for this date has already exists.";		} else{			$this->view->param = null;		}			}		public function editAction(){				$this->checkAdmin();				$param = (object) $this->getRequest()->getParams();				$this->view->managesamsung = $this->db_install_samsungapp->getDataById($param->id);			}		public function chkeditAction(){				$this->checkAdmin();				$param = (object) $this->getRequest()->getParams();		$this->view->param = $param;		 		$returndb="nothing";		if($param->daily_install!=null){			$returndb  = $this->db_install_samsungapp->editSamsungApp($param->id, $param->daily_install);		}		$this->view->notif = $returndb;				if($returndb=='success') {			$this->view->param = null;			$this->_redirect("managesamsung");		}		else if($returndb=='failed')			$this->view->param = $param;		else{			$this->view->param = $param;		}		}		public function deleteAction()	{				// Disable View		$this->_helper->viewRenderer->setNoRender();				$this->checkAdmin();				// Receive Parameter		$param = (object) $this->getRequest()->getQuery();				$this->db_install_samsungapp->deleteSamsungApp($param->id);					$this->_redirect("managesamsung?notif=success&notif_message=Success%20Delete%20Data");	}		public function pullslimeAction()	{			$this->_helper->viewRenderer->setNoRender();		$param = (object) $this->getRequest()->getPost();		$result = $this->db_project->getAllDailyApps(1);		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);		echo Zend_Json::encode($data);	}		public function pullsgiftAction()	{			$this->_helper->viewRenderer->setNoRender();		$param = (object) $this->getRequest()->getPost();		$result = $this->db_project->getAllDailyApps(2);		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);		echo Zend_Json::encode($data);	}		public function pullsalaamAction()	{			$this->_helper->viewRenderer->setNoRender();		$param = (object) $this->getRequest()->getPost();		$result = $this->db_project->getAllDailyApps(3);		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);		echo Zend_Json::encode($data);	}		public function pullsfitAction()	{			$this->_helper->viewRenderer->setNoRender();		$param = (object) $this->getRequest()->getPost();		$result = $this->db_project->getAllDailyApps(4);		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);		echo Zend_Json::encode($data);	}}