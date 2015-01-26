<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_promo/Db_Pg_Promo.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "LogController.php";

class CsslimeController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $config;
	var $conn, $conn_slime, $conn_postgre, $conn_slime_postgre;
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
		$this->db_promo = new Db_Pg_Promo($this->config->resources->db->postgre->staging);
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
		$publisher = $this->mySession->publisher_id;
        
		if($param->order_id!="")$dataToSearch["order_id"]=$param->order_id;
		if($param->name!="")$dataToSearch["name"] = $param->name;
		if($param->email!="")$dataToSearch["email"] = $param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="")$dataToSearch["order_total"] = $param->order_total;
		if($param->transaction_id!="")$dataToSearch["transaction_id"] = $param->transaction_id;
		if($param->payment_type!="")$dataToSearch["payment_type"] = $param->payment_type;
		if($param->status!="")$dataToSearch["status"] = $param->status;
		if($param->pid != "") $dataToSearch['pid'] = $param->pid;
		if($param->pname != "") $dataToSearch['pname'] = $param->pname;
             
		$result=$this->db_slime_staging->getSubscriptionOrder($search, $dataToSearch, $offset, $set, $publisher);
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
		$publisher = $this->mySession->publisher_id;
		if($param->order_id!="") $dataToSearch["order_id"]=$param->order_id;
		if($param->name!="") $dataToSearch["name"]=$param->name;
		if($param->email!="") $dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="") $dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="") $dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="") $dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="") $dataToSearch["status"]=$param->status;
		if($param->pid != "") $dataToSearch['pid'] = $param->pid;
		if($param->pname != "") $dataToSearch['pname'] = $param->pname;
		
		echo $this->db_slime_staging->getSubscriptionCount($search, $dataToSearch, $publisher);
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
		$publisher = $this->mySession->publisher_id;
		if($param->order_id!="")$dataToSearch["order_id"]=$param->order_id;
		if($param->name!="")$dataToSearch["name"]=$param->name;
		if($param->email!="")$dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="")$dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="")$dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="")$dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="")$dataToSearch["status"]=$param->status;
		if($param->pid != "") $dataToSearch['pid'] = $param->pid;
				
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		# find out query stat point
		$offset = ($page * $set) - $set;
		
		$result = $this->db_slime_staging->getSubscriptionOrder($search, $dataToSearch, $offset, $set, $publisher);
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
		$publisher = $this->mySession->publisher_id;
		if($param->order_id!="")$dataToSearch["order_id"]=$param->order_id;
		if($param->name!="")$dataToSearch["name"]=$param->name;
		if($param->email!="")$dataToSearch["email"]=$param->email;
		if($param->start_order != "") $dataToSearch['start_order'] = $param->start_order;
		if($param->end_order != "") $dataToSearch['end_order'] = $param->end_order;
		if($param->order_total!="")$dataToSearch["order_total"]=$param->order_total;
		if($param->transaction_id!="")$dataToSearch["transaction_id"]=$param->transaction_id;
		if($param->payment_type!="")$dataToSearch["payment_type"]=$param->payment_type;
		if($param->status!="")$dataToSearch["status"]=$param->status;
		if($param->pid != "") $dataToSearch['pid'] = $param->pid;
		
		$offset = 0;
		$set = "";	 
		// Get Data
		if ($param->export == 0) {
			$path = "uploads/slime_all".$this->config->csv->ext;
			$result = $this->db_slime_staging->getSubscriptionOrder($search, $dataToSearch, $offset, $set, $publisher);
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_current".$this->config->csv->ext;	
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		
		echo $path;
	}
	
	public function exportdownloadAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Query
		$param = (object) $this->getRequest()->getQuery();
		
		$path = "uploads/slime_download".$this->config->csv->ext;
		$offset = 0;
		$set = "";
		if ($param->export == 0) {
			$result = $this->db_slime->searchDownloadByEmail($param->email, $offset, $set);
			$this->library->exporter->exportToCSVFromDB($result, $path);
		} else {
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}
		echo $path;
	}
	
	// --------------------------------------------------------------------------------------------------------------
	// Current Promo
	// --------------------------------------------------------------------------------------------------------------
	
	public function cspromoAction(){
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$offset = 0;
		$limit = $this->config->slime->content->max->set; // how many data will be shown 
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->promo_name!="") {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->start_end_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_valid_date !='') {
			$data["start_valid_date"] = $param->start_valid_date;
		}
		if($param->end_valid_date !='') {
			$data["end_valid_date"] = $param->end_valid_date;
		}	
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$start = ($page * $limit) - $limit;
		//echo "start: ".$start." page in php: ".$page;
		
		// $result = $this->db_slime->getContentsFragment($q, $data, $offset, $limit, $param->start_release, $param->end_release);
		$result = $this->db_promo->getPromo($q, $data, $offset, $limit, true);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
				
		$this->view->param = $param;
	}
	
	public function getpromoproductAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$product = $this->db_promo->getPromoProduct($param->promo_id);
		
		echo Zend_Json::encode($product);
	}
	
	public function getpromodeviceAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$device = $this->db_promo->getPromoDevice($param->promo_id);
		
		echo Zend_Json::encode($device);
	}
	
	public function gettotalpromoAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->promo_name!="") {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->start_end_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_valid_date !='') {
			$data["start_valid_date"] = $param->start_valid_date;
		}
		if($param->end_valid_date !='') {
			$data["end_valid_date"] = $param->end_valid_date;
		}
		$count = $this->db_promo->getPromoCount($q, $data, true);
		
		echo $count;
	}

	public function loadmorepromoAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->slime->content->max->set;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->promo_name!="") {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->start_end_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_valid_date !='') {
			$data["start_valid_date"] = $param->start_valid_date;
		}
		if($param->end_valid_date !='') {
			$data["end_valid_date"] = $param->end_valid_date;
		}	
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_promo->getPromo($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
	}

	public function exportcsvpromoAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "";
		$offset = 0;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->promo_name!="") {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->start_start_date!="") {
			$data["start_start_date"] = $param->start_start_date;
		}
		if($param->start_end_date !='') {
			$data["end_start_date"] = $param->end_start_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_end_date !='') {
			$data["start_end_date"] = $param->start_end_date;
		}
		if($param->start_end_date !='') {
			$data["end_end_date"] = $param->end_end_date;
		}
		if($param->start_valid_date !='') {
			$data["start_valid_date"] = $param->start_valid_date;
		}
		if($param->end_valid_date !='') {
			$data["end_valid_date"] = $param->end_valid_date;
		}	
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/slime_promo_all".$this->config->csv->ext;
			
			$result = $this->db_promo->getPromo($q, $data, $offset, $limit);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/slime_promo_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
}