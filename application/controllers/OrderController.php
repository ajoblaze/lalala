<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class OrderController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $config;
	var $library;
	var $db_project, $db_slime, $db_user, $db_promo;
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
		$this->view->attr = (Object) array( "title" => "Transaction Management" );
										  
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->slime);
		
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
		
	public function indexAction()
	{
		$param =(object)$this->getRequest()->getQuery();
        $dataToSearch = array();
        $search=$param->search;
		$offset = 0;
		$set = $this->config->slime->max->set;
        
		if($param->order_id!="")$dataToSearch["order_id"]=$param->order_id;
		if($param->name!="")$dataToSearch["name"]=$param->name;
		if($param->email!="")$dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="")$dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="")$dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="")$dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="")$dataToSearch["status"]=$param->status;
             
		$result=$this->db_slime_staging->getSubscriptionOrder($search, $dataToSearch, $offset, $set);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->subscriptionOrder = Zend_Json::encode($data);
		$this->view->param = $param;
		
		// Logging
		$dataToSearch["search"] = $search;
		LogController::insert( 	  $this->config->resources->db->postgre,
								  $this->config->template->log->search,
								  $this->config->const->activity->search,
								  $this->mySession->id,
								  "SEARCH SLIME",
								  $dataToSearch
							 );
	}
	
	public function gettotalAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$search=$param->search;
		$dataToSearch = array();
		if($param->order_id!="") $dataToSearch["order_id"]=$param->order_id;
		if($param->name!="") $dataToSearch["name"]=$param->name;
		if($param->email!="") $dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="") $dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="") $dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="") $dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="") $dataToSearch["status"]=$param->status;
			
		echo $this->db_slime_staging->getSubscriptionCount($search, $dataToSearch);
	}
	
	public function detailtransactionAction()
    {
        // Disable View
        $this->_helper->viewRenderer->setNoRender();
        $order_id=$this->getRequest()->getPost("order_id");
        $result= $this->db_slime_staging->getDetailOrder($order_id);
        $data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
        echo Zend_Json::encode($data);
    }
	
	public function loadmoreAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$search=$param->search;
		$set = $this->config->slime->max->set;
		$dataToSearch = array();
		if($param->order_id!="")$dataToSearch["order_id"]=$param->order_id;
		if($param->name!="")$dataToSearch["name"]=$param->name;
		if($param->email!="")$dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="")$dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="")$dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="")$dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="")$dataToSearch["status"]=$param->status;
				
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		# find out query stat point
		$offset = ($page * $set) - $set;
		
		$result = $this->db_slime_staging->getSubscriptionOrder($search, $dataToSearch, $offset, $set);
		$data = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);
	}
	
	public function parseByDevice ($datas){
		
		$temp_data = array();
		$temp_device_model = "";
		$temp_array = array();
		$total = 0;
		$counter=-1;
		foreach ($datas as $data){
			if($temp_device_model == $data->device_model){
				array_push($temp_array,$data);
			}else{
				if(count($temp_array)>0){
					$temp_array["item_count"] = count($temp_array);
					$total += count($temp_array);
					array_push($temp_data,$temp_array);
				}
				$temp_array = array();
				array_push($temp_array,$data);
				$temp_array['device_name'] = $data->device_model;
				$temp_array['device_identifier'] = $data->device_identifier;
				$temp_array['device_identifier_type'] = $data->device_identifier_type;
				$temp_array['product_name'] = $data->product_name;
			}
			
			$temp_device_model = $data->device_model;
		}
		
		if(count($temp_array)>0){
			$temp_array["item_count"] = count($temp_array);
			$total += count($temp_array);		
			array_push($temp_data,$temp_array);
		}
		
		return $temp_data;
	}
	
	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Query 
		$param = (object) $this->getRequest()->getPost();
		$search = $param->search;
		if($param->order_id!="")$dataToSearch["order_id"]=$param->order_id;
		if($param->name!="")$dataToSearch["name"]=$param->name;
		if($param->email!="")$dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="")$dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="")$dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="")$dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="")$dataToSearch["status"]=$param->status;
             
		
		$offset = 0;
		$set = "";	 
		// Get Data
		if ($param->export == 0) {
			$path = "uploads/slime_all".$this->config->csv->ext;
			$result = $this->db_slime_staging->getSubscriptionOrder($search, $dataToSearch, $offset, $set);
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_current".$this->config->csv->ext;	
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		
		echo $path;
	}
}