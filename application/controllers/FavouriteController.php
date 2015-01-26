<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class FavouriteController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $config;
	var $conn, $conn_slime, $conn_postgre, $conn_slime_postgre;
	var $library;
	var $db_project, $db_slime, $db_user;
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
				
		// Attach Library
		$this->library = (object) array( "exporter" => new Exporter() );
		
		// Configuration for View
		$this->view->attr = (Object) array( "title" => "Favourite Download" );
										  
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->slime->postgre);
		$this->db_slime_staging = new Db_Pg_Slime($this->config->resources->db->newslime->postgre);
		
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

	/* create by kovan for favourite download_time */
	public function indexAction()
	{
		$param = (object)$this->getRequest()->getQuery();
		$offset = 0;
		$set = $this->config->slime->favourite->max->set;
		$publisher = $this->mySession->publisher_id;
		$category = $param->cat;
		$dataToSearch=array(
						"p.name" => $param->title,
						"pb.name" => $param->publisher
					  );
		$result=$this->db_slime_staging->getFavouriteAll($param->search,$dataToSearch, $offset, $set, $publisher, $category);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->favouriteAll= Zend_Json::encode($data);
		$this->view->param = $param;
	}
	
    public function detailfavouriteAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        $search=$this->getRequest()->getPost("title_name");
        $result=$this->db_slime_staging->getDetailFavourite($search);
        $data=$this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
        echo json_encode($data, JSON_NUMERIC_CHECK);
    }
	
	public function gettotalAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$category = $param->cat;
		$publisher = $this->mySession->publisher_id;

		$dataToSearch=array(
						"p.name" => $param->title,
						"pb.name" => $param->publisher
					  );
							 
		$totalData = $this->db_slime_staging->getFavouriteCount($param->search, $dataToSearch, $publisher, $category);
		echo $totalData;
	}
	
	/* end of favourite download */
	
	public function loadmoreAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$offset = $param->offset;
		$set = $this->config->slime->favourite->max->set;
		$category = $param->cat;
		$publisher = $this->mySession->publisher_id;
		$dataToSearch = array(
						"p.name" => $param->title,
						"pb.name" => $param->publisher
					  );
		
		$result = $this->db_slime_staging->getFavouriteAll($param->search, $dataToSearch, $offset, $set, $publisher, $category);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);
	}
	
	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Query 
		$param = (object) $this->getRequest()->getPost();
		$publisher = $this->mySession->publisher_id;
		$dataToSearch = array(
								"p.name" => $param->title,
								"pb.name" => $param->publisher
							  );
		$offset = 0;
		$set = "";	 
		
		// Get Data
		if ($param->export == 0) {
			$path = "uploads/slime_favourite".$this->config->csv->ext;	
			$result = $this->db_slime_staging->getFavouriteAll($param->search, $dataToSearch, $offset, $set, $publisher, $category);
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_current_favourite".$this->config->csv->ext;	
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		
		echo $path;
	}
}