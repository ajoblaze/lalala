<?php
/*
	Created by a.riccia
	29 / 08 / 2014
*/
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_log/Db_Pg_Log.php";
require_once "db_pg_role/Db_Pg_Role.php";
require_once "db_pg_user/Db_Pg_User.php";

class LogController extends Zend_Controller_Action
{
	var $url;
	var $first;
	static $conn, $conn_postgre;
	var $db_project, $db_log, $db_user;
	static $LOG_ADD = 'LOG_ADD', $LOG_EDIT = 'LOG_EDIT', $LOG_DELETE = 'LOG_DELETE', $LOG_SEARCH = "LOG_SEARCH", $LOG_VIEW = "LOG_VIEW";
	static $s_db_log, $db_role;
	
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
		
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre );
		$this->db_log = new Db_Pg_Log($this->config->resources->db->postgre );
		$this->db_role = new Db_Pg_Role($this->config->resources->db->postgre );
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre );
		
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
		// Get Parameter 
		$param = (object) $this->getRequest()->getQuery();
		$size = count($this->getRequest()->getQuery());
		
		$q = "";
		if ($param->q != "") {
			$q = $param->q;
		}
		$dataToSearch = array(	"username" => $param->username,
								"mu.roleid" => $param->role,
								"activity_name" => $param->activity,
								"detail_activity" => $param->detail );
								
		$start = 0;
		$set = $this->config->log->max->set;
		$result =  $this->db_log->getLog($q, $dataToSearch, $start, $set, $param->start_date, $param->end_date);
		
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "LOG",
							  ""
							 );
		
		$this->view->param = $param;
		$this->view->roleList = $this->db_role->getRole();
		$this->view->totalLog = $this->db_log->getCount($q, $dataToSearch, $param->start_date, $param->end_date);
		$this->view->logData = Zend_Json::encode($data);
	}
	
	public function getlogAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$q = "";
		if ($param->q != "") {
			$q = $param->q;
		}
		$dataToSearch = array(	"username" => $param->username,
								"rl.rolename" => $param->role,
								"activity_name" => $param->activity,
								"detail_activity" => $param->detail );
								
		$offset = $param->offset;
		$set = $this->config->log->max->set;
		
		$result =  $this->db_log->getLog($q, $dataToSearch, $offset, $set, $param->start_date, $param->end_date);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		
		echo Zend_Json::encode($data);
	}
	
	public static function convertToString($array)
	{
		$editing = "";
		foreach ($array as $key => $value) :
			if ($value != "")
			{
				$editing .= $comma."'".$key."' = '".$value."'";
				$comma = ", ";
			}
		endforeach;
		return $editing;
	}
	
	public static function templateLog($flag, $menu, $dataBefore, $dataAfter="")
	{
		switch($flag)
		{
			case LogController::$LOG_ADD :  $editing = $dataBefore;
							if (is_array($dataBefore)){
								$editing = LogController::convertToString($dataBefore);
							}
							return "INSERTING DATA (".$editing.") ON MENU ".$menu; break;
			case LogController::$LOG_EDIT : $editing = $dataBefore;
							$after = $dataAfter;
							if (is_array($dataBefore)){
								$editing = LogController::convertToString($dataBefore);
							}
							
							if (is_array($dataAfter)){
								$after = LogController::convertToString($dataAfter);
							}
							return "EDITING DATA ON ".$menu." FROM ".$editing." TO ".$after; break;
			case LogController::$LOG_DELETE : $editing = $dataBefore;
								if (is_array($dataBefore)){
									$editing = LogController::convertToString($dataBefore);
								}
								return "DELETING DATA ON ".$menu." WITH DATA = ".$editing; break;
			case LogController::$LOG_SEARCH :  $editing = $dataBefore;
								if (is_array($dataBefore)){
									$editing = LogController::convertToString($dataBefore);
								}
								return "SEARCHING ON ".$menu." WITH PARAMETER ".$editing; break;
			case LogController::$LOG_VIEW : return "VIEWING ON PAGE ".$menu; break;
		}
	}
	
	public static function insert($conn, $flag, $activityID, $userID, $menu, $dataBefore, $dataAfter = "")
	{	
		$template = LogController::templateLog($flag, $menu, $dataBefore, $dataAfter);
		LogController::$s_db_log = new Db_Pg_Log($conn );
		$dataToInsert = array(
								"userID" => $userID,
								"activityID" => $activityID,
								"detail_activity" => $template
							  );
							  													 
		$result = LogController::$s_db_log->insert($dataToInsert);
	}
	
	public function logcsvAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getQuery();
		$result =  $this->db_log->getLog();
		
		$path = "uploads/Log".$this->config->csv->ext;
		$this->library->exporter->exportToCSVFromDB($result, $path);
		
		echo $path;
	}
}