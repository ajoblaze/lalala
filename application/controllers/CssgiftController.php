<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "LogController.php";

class CssgiftController extends Zend_Controller_Action
{
	var $url;
	var $config;
	var $library;
	var $conn, $conn_postgre;
	var $conn_sgift, $conn_sgift_postgre;
	var $db_project, $db_sgift;
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
		
		// Import Library
		$this->library = (object) array( "exporter" => new Exporter() );
		// Configuration for View
		$this->view->attr = (Object) array( "title" => "Promo Management" );
		// Check Valid Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->sgift);
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		
		// Set Variable
		$this->view->projects = $this->db_project->getVisibleProject();
		$this->initView();
	}
		
	public function indexAction(){
		$param = (object) $this->getRequest()->getQuery();

		$data = array();
		$limit = $this->config->sgift->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=''){
			$data["email"] = $param->email;
		}
		if($param->imei!=''){
			$data["imei"] = $param->imei;
		}
		if($param->device!=''){
			$data["model"] = $param->device;
		}
		if($param->telp!=''){
			$data["phone_number"] = $param->telp;
		}
		if($param->store!=''){
			$data["merchant"] = $param->store;
		}
		if($param->voucher_id!=''){
			$data["redeem_code"] = $param->voucher_id;
		}	
		if($param->promo_id!=''){
			$data["offer_id"] = $param->promo_id;
		}	
		if($param->start_claim != '') {
			$data['start_claim'] = $param->start_claim;
		}
		if($param->end_claim != '') {
			$data['end_claim'] = $param->end_claim;
		}
		if($param->start_redeem != '') {
			$data['start_redeem'] = $param->start_redeem;
		}
		if($param->end_redeem != '') {
			$data['end_redeem'] = $param->end_redeem;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		# find out query stat point
		$start = ($page * $limit) - $limit;
		//echo "start: ".$start." page in php: ".$page;

		$result= $this->db_sgift->getSearchFragment($q, $data, $start, $limit);
		
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
		$this->view->param = $param;
		
		if($user->roleID==$this->config->role->id->cs){
			LogController::insert($this->config->resources->db->postgre,  // Koneksi Database
								  $this->config->template->log->view,  // Flag
								  $this->config->const->activity->view,  // Flag
								  $this->mySession->id,  // UserID
								  "cssgift", // Menu
								  $data); 
		 }
	}
	
	public function gettotalAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=''){
			$data["f.email"] = $param->email;
		}
		if($param->imei!=''){
			$data["h.name"] = $param->imei;
		}
		if($param->device!=''){
			$data["h.model"] = $param->device;
		}
		if($param->telp!=''){
			$data["g.phone_number"] = $param->telp;
		}
		if($param->store!=''){
			$data["h.name"] = $param->store;
		}
		if($param->voucher_id!=''){
			$data["d.redeem_code"] = $param->voucher_id;
		}	
		if($param->promo_id!=''){
			$data["a.offer_id"] = $param->promo_id;
		}
		if($param->start_claim != '') {
			$range['start_claim'] = $param->start_claim;
		}
		if($param->end_claim != '') {
			$range['end_claim'] = $param->end_claim;
		}
		if($param->start_redeem != '') {
			$range['start_redeem'] = $param->start_redeem;
		}
		if($param->end_redeem != '') {
			$range['end_redeem'] = $param->end_redeem;
		}
			
		echo $this->db_sgift->getPromoCount($q, $data, $range);
	}
	
	public function searchuserAction(){
		$this->_helper->viewRenderer->setNoRender();
		//Get query
		$param = (object) $this->getRequest()->getPost();
		$result = $this->db_sgift->getUserSearch($param->user_id);
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($arr);
	}
	
	public function searchpromoAction(){
		$this->_helper->viewRenderer->setNoRender();
		//Get query
		$param = (object) $this->getRequest()->getPost();
		$result = $this->db_sgift->getPromoSearch($param->promo_id, $param->batch_id, $param->campaign_id, $param->provider_id, $param->channel_id);
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result); 
		echo Zend_Json::encode($arr);
	}
	
	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		// Get Query 
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=''){
			$data["email"] = $param->email;
		}
		if($param->imei!=''){
			$data["imei"] = $param->imei;
		}
		if($param->device!=''){
			$data["model"] = $param->device;
		}
		if($param->telp!=''){
			$data["phone_number"] = $param->telp;
		}
		if($param->store!=''){
			$data["merchant"] = $param->store;
		}
		if($param->voucher_id!=''){
			$data["redeem_code"] = $param->voucher_id;
		}	
		if($param->promo_id!=''){
			$data["offer_id"] = $param->promo_id;
		}
		if($param->start_claim != '') {
			$data['start_claim'] = $param->start_claim;
		}
		if($param->end_claim != '') {
			$data['end_claim'] = $param->end_claim;
		}
		if($param->start_redeem != '') {
			$data['start_redeem'] = $param->start_redeem;
		}
		if($param->end_redeem != '') {
			$data['end_redeem'] = $param->end_redeem;
		}
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/galaxy_gift_id_all".$this->config->csv->ext;
			$result = $this->db_sgift->getSearchFragment($q, $data, "", "");
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/galaxy_gift_id_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	public function loadmoreAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$limit = $this->config->sgift->max->set; /* how many data will be shown */
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=''){
			$data["email"] = $param->email;
		}
		if($param->imei!=''){
			$data["imei"] = $param->imei;
		}
		if($param->device!=''){
			$data["model"] = $param->device;
		}
		if($param->telp!=''){
			$data["phone_number"] = $param->telp;
		}
		if($param->store!=''){
			$data["merchant"] = $param->store;
		}
		if($param->voucher_id!=''){
			$data["redeem_code"] = $param->voucher_id;
		}	
		if($param->promo_id!=''){
			$data["offer_id"] = $param->promo_id;
		}
		if($param->start_claim != '') {
			$data['start_claim'] = $param->start_claim;
		}
		if($param->end_claim != '') {
			$data['end_claim'] = $param->end_claim;
		}
		if($param->start_redeem != '') {
			$data['start_redeem'] = $param->start_redeem;
		}
		if($param->end_redeem != '') {
			$data['end_redeem'] = $param->end_redeem;
		}
		if($param->page == '')
			$page=1;
		else
		$page = intval($param->page);
		# find out query stat point
		$start = ($page * $limit) - $limit;
		//echo "start: ".$start." page in php: ".$page."   ".$limit;
    	$result= $this->db_sgift->getSearchFragment($q, $data, $start, $limit);
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$data["search"] = $param->q;
		// Add, Menu, Delete, Search
		LogController::insert($this->config->resources->db->postgre,  // Koneksi Database
							  $this->config->template->log->search,  // Flag
							  $this->config->const->activity->search,
							  $this->mySession->id,  // UserID
							  "cssgift", // Menu
							  $data); // Datan
							  // Data After	
		echo Zend_Json::encode($arr);	
	}
	
	// Promo Action
	public function cspromoAction(){
		// Configuration for View
		if ($this->mySession->role == $this->config->role->id->merchant) {
			$this->view->attr->title = "Promo Merchant ".$this->mySession->provider_name;
		} else if ($this->mySession->role == $this->config->role->id->aggregator) {
			$this->view->attr->title = "Promo Merchant Aggregator";
		} else {
			$this->view->attr->title = "Promo";
		}
		
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$q='';
		$limit = $this->config->sgift->merchant->max->set;
		$offset = 0;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant!=''){
			$data["merchant"] = $param->merchant;
		}
		if($param->type!=''){
			$data["claim_type"] = $param->type;
		}
		if($param->publish_date!=''){//masih salah
			$data["publish_date"] = $param->publish_date;
		}
		if($param->coupon_title!=''){
			$data["coupon_title"] = $param->coupon_title;
		}
		if($param->start_start!=''){
			$data["start_start"] = $param->start_start;
		}
		if($param->end_start!=''){
			$data["end_start"] = $param->end_start;
		}
		if($param->start_end!=''){
			$data["start_end"] = $param->start_end;
		}
		if($param->end_end!=''){
			$data["end_end"] = $param->end_end;
		}
		if($param->start_publish!=''){
			$data["start_publish"] = $param->start_publish;
		}
		if($param->end_publish!=''){
			$data["end_publish"] = $param->end_publish;
		}
		
		if ($param->offset!='')$offset = (int)$param->offset;
				
		$result = $this->db_sgift->getAllPromoFragment($limit, $offset, $q, $data,'', null, true);

		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->merchant_promo = json_encode($arr, JSON_NUMERIC_CHECK);
		$this->view->param = $param;
	}

	public function searchpromodetailAction(){
		$this->_helper->viewRenderer->setNoRender();
		//Get query
		$param = (object) $this->getRequest()->getPost();
		$result = $this->db_sgift->getPromoDetail($param->promo_id, $param->batch_id, $param->campaign_id, $param->provider_id, $param->channel_id);
		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result); 
		echo Zend_Json::encode($arr);
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
		if($param->merchant!=''){
			$data["pm.name"] = $param->merchant;
		}
		if($param->type!=''){
			$data["claim_type"] = $param->type;
		}
		if($param->publish_date!=''){
			$data["TO_CHAR(coc.creation_time,'DD Mon YYYY HH:MI')"] = $param->publish_date;
		}
		if($param->coupon_title!=''){
			$data["coc.name"] = $param->coupon_title;
		}
		if($param->start_start!=''){
			$range["start_start"] = $param->start_start;
		}
		if($param->end_start!=''){
			$range["end_start"] = $param->end_start;
		}
		if($param->start_end!=''){
			$range["start_end"] = $param->start_end;
		}
		if($param->end_end!=''){
			$range["end_end"] = $param->end_end;
		}
		if($param->start_publish!=''){
			$range["start_publish"] = $param->start_publish;
		}
		if($param->end_publish!=''){
			$range["end_publish"] = $param->end_publish;
		}
			
		$totalData = $this->db_sgift->getAllPromoCount($q, $data, '', null,true, $range);
		
		echo $totalData;
	}
	
	public function exportcsvpromoAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "all";
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant!=''){
			$data["merchant"] = $param->merchant;
		}
		if($param->type!=''){
			$data["claim_type"] = $param->type;
		}
		if($param->publish_date!=''){
			$data["publish_date"] = $param->publish_date;
		}
		if($param->coupon_title!=''){
			$data["coupon_title"] = $param->coupon_title;
		}
		if($param->start_start!=''){
			$data["start_start"] = $param->start_start;
		}
		if($param->end_start!=''){
			$data["end_start"] = $param->end_start;
		}
		if($param->start_end!=''){
			$data["start_end"] = $param->start_end;
		}
		if($param->end_end!=''){
			$data["end_end"] = $param->end_end;
		}
		if($param->start_publish!=''){
			$data["start_publish"] = $param->start_publish;
		}
		if($param->end_publish!=''){
			$data["end_publish"] = $param->end_publish;
		}
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/promo_all".$this->config->csv->ext;
			
			$result = $this->db_sgift->getAllPromoFragment($limit, $offset, $q, $data,'', null, true);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/promo_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function loadmorepromoAction(){
	
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = $this->config->sgift->merchant->max->set;
		$offset = 0;
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant!=''){
			$data["merchant"] = $param->merchant;
		}
		if($param->type!=''){
			$data["claim_type"] = $param->type;
		}
		if($param->publish_date!=''){
			$data["publish_date"] = $param->publish_date;
		}
		if($param->coupon_title!=''){
			$data["coupon_title"] = $param->coupon_title;
		}
		if($param->start_start!=''){
			$data["start_start"] = $param->start_start;
		}
		if($param->end_start!=''){
			$data["end_start"] = $param->end_start;
		}
		if($param->start_end!=''){
			$data["start_end"] = $param->start_end;
		}
		if($param->end_end!=''){
			$data["end_end"] = $param->end_end;
		}
		if($param->start_publish!=''){
			$data["start_publish"] = $param->start_publish;
		}
		if($param->end_publish!=''){
			$data["end_publish"] = $param->end_publish;
		}
		
		if ($param->offset!='')$offset = (int)$param->offset;
		
		$result = $this->db_sgift->getAllPromoFragment($limit, $offset, $q, $data,'', null, true);

		$arr = $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo json_encode($arr, JSON_NUMERIC_CHECK);
	}
	
	//---------------------------------------------------------------------------
	//PROVIDER REQUEST
	
	public function providerrequestAction()
	{

		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$limit = $this->config->sgift->merchant->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->url!=""){
			$data["url"] = $param->url;
		}
		if($param->start_date != '') {
			$start_date = $param->start_date;
		}
		if($param->end_date != '') {
			$end_date = $param->end_date;
		}
		if($param->period != '') {
			$range = $param->period;
		}
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$start = ($page * $limit) - $limit;
		//echo "start: ".$start." page in php: ".$page;
		
		$result = $this->db_sgift->getProviderRequest($q, $start, $limit, $data, $start_date, $end_date, $range); 
		
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);		
		
		$this->view->param = $param;
		
		if($user->roleID==$this->config->role->id->cs){
			LogController::insert($this->conn_postgre,  // Koneksi Database
								  $this->config->template->log->view,  // Flag
								  $this->config->const->activity->view,  // Flag
								  $this->mySession->id,  // UserID
								  "provider request", // Menu
								  $data); 
		 }
	}
	
	public function getproviderrequestdetailAction(){
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$result = $this->db_sgift->getProviderRequestDetail($param->id); 
			
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo  Zend_Json::encode($_data);
	}
	public function exporttxtdetailAction(){
		$this->_helper->viewRenderer->setNoRender();
			$this->getResponse()
			 ->setHeader('Content-Disposition', 'attachment; filename=providerrequest_detail.txt');
		$param = (object) $this->getRequest()->getPost();
		$data = $param->content;
		$data  = strip_tags($param->content, '<br><li>');
		$data = str_replace('<li>', '- ', $data);
		$data = str_replace('</li>', '', $data);
		$data = str_replace('<BR>', '', $data);
		$data = str_replace('<br/>', PHP_EOL, $data);
		echo $data;
	}
	public function loadmoreproviderrequestAction(){
	$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$limit = $this->config->sgift->merchant->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->url!=""){
			$data["url"] = $param->url;
		}
		if($param->start_date != '') {
			$start_date = $param->start_date;
		}
		if($param->end_date != '') {
			$end_date = $param->end_date;
		}
		if($param->period != '') {
			$range = $param->period;
		}
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$start = ($page * $limit) - $limit;
		//echo "start: ".$start." page in php: ".$page;
		
		$result = $this->db_sgift->getProviderRequest($q, $start, $limit, $data, $start_date, $end_date, $range); 
		
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
		
	}
	
	public function gettotalproviderrequestAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$limit = $this->config->sgift->merchant->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->url!=""){
			$data["url"] = $param->url;
		}
		if($param->start_date != '') {
			$start_date = $param->start_date;
		}
		if($param->end_date != '') {
			$end_date = $param->end_date;
		}
		if($param->period != '') {
			$range = $param->period;
		}
		$total = $this->db_sgift->getTotalProviderRequest($q, $data, $start_date, $end_date, $range); 		
		echo  $total;
	}
	
	public function exportcsvproviderrequestAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "all";
		
		if ($param->q != '' || $param->url != '' )
		{
			if($param->q!=''){
				$q = $param->q;
			}
			if($param->url!=''){
				$data["url"] = $param->url;
			}
		}
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/providerrequest_all".$this->config->csv->ext;
			$result = $this->db_sgift->getProviderRequest(); 
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/providerrequest_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	//---------------------------------------------------------------------------
	
}