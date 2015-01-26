<?php
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "LogController.php";

class DslimedashboardController extends Zend_Controller_Action
{
	var $url;
	var $config;
	var $first;
	var $library;
	var $conn, $conn_postgre;
	static $SLIME_ID = 1, $SGIFT_ID = 2;
	var $db_project, $db_user, $db_slime;
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
		$this->library = (object) array( "validate" => new Validate(),
										 "exporter" => new Exporter());
		
		// Property
		$this->view->attr = (object) array("title" => "");
		
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		// Create entity of Database
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->newslime->postgre);
		
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
		$this->view->attr->title = "S-Lime Dashboard";
		$monthPie = $this->db_slime->getMonthPieRevenue();
		$downloadPie = $this->db_slime->getMontlyPieDownload();
		$top5Con = $this->db_slime->getDeviceContribution(true);
		$this->view->dashboardData = $this->db_project->getSlimeDashboard();
		$this->view->top5Magz = $this->db_slime->getTop5Stats(Db_Pg_Slime::$TYPE_MAGAZINE);
		$this->view->top5News = $this->db_slime->getTop5Stats(Db_Pg_Slime::$TYPE_NEWSPAPER);
		$this->view->top5Books = $this->db_slime->getTop5Stats(Db_Pg_Slime::$TYPE_BOOK);
		$this->view->revenueTable = $this->db_slime->getRevenueTable();
		$this->view->top5Con = $top5Con;
		$this->view->monthPie = Zend_Json::encode($monthPie);
		$this->view->downloadPie = Zend_Json::encode($downloadPie);
		
		// Another Property
		$date = date_create(date('Y-m-d'));
		$date = date_add($date, date_interval_create_from_date_string("-2 days"));
		$this->view->lastGoogleUpdate = $date;
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "DASHBOARD",
							  ""
							 );
	}
	
	public function retrtimeAction() 
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Param
		$param = (object) $this->getRequest()->getPost();
		
		$timely_graph = $this->db_slime->getGraphRevenue($param->time, true);	
		echo Zend_Json::encode($timely_graph);
	}
	
	public function exportgraphcsvAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Param
		$param = (object) $this->getRequest()->getPost();
		$param->time = ($param->time == "") ? Db_Pg_Slime::$GRAPH_MONTHLY : $param->time;
		$path = "uploads/slime_statistics".$this->config->csv->ext;
		$result = $this->db_slime->getGraphRevenue($param->time, false);	
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
	
	public function publisherAction()
	{
		$this->view->attr->title = "Publisher Dashboard";
		$publisher = $this->mySession->publisher_id;
		$monthRevenue = $this->db_slime->getPublicationPieRevenue($publisher);
		$revenueTable = $this->db_slime->getRevenuePublisherTable($publisher);
		$devContribution = $this->db_slime->getDeviceContribution(false, $publisher);
		$top5Con = $this->db_slime->getDeviceContribution(true, $publisher);
		$downloadMonth = $this->db_slime->getDownloadPerMonth($publisher);
		$publicationCount = $this->db_slime->getPublicationCount($publisher);
		$publisherList = $this->db_slime->getPublisher($publisher);

		$this->view->monthRevenue = Zend_Json::encode($monthRevenue);
		$this->view->devContribution = Zend_Json::encode($devContribution);
		$this->view->revenueTable = $revenueTable;
		$this->view->top5Con = $top5Con;
		$this->view->downloadMonth = Zend_Json::encode($downloadMonth);
		$this->view->publicationCount = $publicationCount;
		$this->view->publisherList = $publisherList;
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "PUBLISHER DASHBOARD",
							  "" );
	}
	
	public function retrpubAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Param
		$param = (object) $this->getRequest()->getPost();
		$param->time = ($param->time == "") ? Db_Pg_Slime::$GRAPH_MONTHLY : $param->time;
		$param->publisher = ($this->mySession->publisher_id == "") ? $param->publisher : $this->mySession->publisher_id;
		$timely_graph = $this->db_slime->getGraphPublicationRevenue($param->time, true, $param->publisher);	
		echo Zend_Json::encode($timely_graph);
	}

	public function retrdataAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Param
		$param = (object) $this->getRequest()->getPost();
		$publicationCount = $this->db_slime->getPublicationCount($param->publisher);
		$pubPieRevenue = $this->db_slime->getPublicationPieRevenue($param->publisher);
		$downloadPie = $this->db_slime->getDownloadPerMonth($param->publisher);
		$top5Con = $this->db_slime->getDeviceContribution(true, $param->publisher);
		$revenueTable = $this->db_slime->getRevenuePublisherTable($param->publisher);
		$devContribution = $this->db_slime->getDeviceContribution(false, $param->publisher);
		$result = array("pubCount" => $publicationCount, "pubPieRevenue" => $pubPieRevenue, "downloadPie" => $downloadPie, "top5Con" => $top5Con, "devContribution" => $devContribution, "revenueTable" => $revenueTable);
		echo Zend_Json::encode($result);
	}
	
	public function exportpubcsvAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Param
		$param = (object) $this->getRequest()->getPost();
		$param->time = ($param->time == "") ? Db_Pg_Slime::$GRAPH_MONTHLY : $param->time;
		$path = "uploads/slime_publisher".$this->config->csv->ext;
		$result = $this->db_slime->getGraphPublicationRevenue($param->time, false, $param->publisher);	
		$this->library->exporter->exportToCSVFromDB($result, $path);
		echo $path;
	}
}