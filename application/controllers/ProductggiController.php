<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_product/Db_Pg_Product.php";
require_once "LogController.php";

class ProductggiController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_project, $db_review, $db_sgift, $db_user, $db_product;
	var $ID = 2;  
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
						
		// Library 
		$this->library = (object) array( "exporter" => new Exporter() );
		
		// Configuration for View
		$title = "";
		$this->view->attr = (Object) array( "title" => $title );
									
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->sgift);
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		$this->db_product = new Db_Pg_Product($this->config->resources->db->sgift->postgre);
		
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
	
	public function loadmoreAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch = array();
		$q='';
		$offset=0;
		$max_set=100;
		
		if($param->offset!=''){
			$offset = $param->offset;
		}
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant_name!=""){
			$datasearch["merchant_name"] = $param->merchant_name;
		}
		if($param->segment !=''){
			$datasearch["segment"] = $param->segment;
		}

		$result= $this->db_product->getMerchantPromoAmount($q , $datasearch, $offset, $max_set);
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo json_encode( $data, JSON_NUMERIC_CHECK );
	}
	
	// public function loadmoredetailAction(){
	// 	$this->_helper->viewRenderer->setNoRender();
	// 	$param = (object) $this->getRequest()->getPost();
		
	// 	$datasearch_detail = array();
	// 	$q_detail='';
	// 	$offset_detail=0;
	// 	$max_set=100;
		
	// 	if($param->q_detail!=''){
	// 		$q_detail = $param->q_detail;
	// 	}
	// 	if($param->offset!=''){
	// 		$offset_detail = $param->offset;
	// 	}
	// 	if($param->merchant_name_detail!=""){
	// 		$datasearch_detail["merchant_name_detail"] = $param->merchant_name_detail;
	// 	}
	// 	if($param->promotion_name_detail!=""){
	// 		$datasearch_detail["promotion_name_detail"] = $param->promotion_name_detail;
	// 	}
	// 	if($param->segment_detail !=''){
	// 		$datasearch_detail["segment_detail"] = $param->segment_detail;
	// 	}
	// 	if($param->start_date !=''){
	// 		$datasearch_detail["start_date"] = $param->start_date;
	// 	}
	// 	if($param->end_date !=''){
	// 		$datasearch_detail["end_date"] = $param->end_date;
	// 	}

	// 	$result= $this->db_product->getMerchantPromoDetail($q_detail , $datasearch_detail, $offset_detail, $max_set);
	// 	$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
	// 	echo json_encode( $data, JSON_NUMERIC_CHECK );
	// }
	
	public function exportcsvAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant_name!=""){
			$datasearch["merchant_name"] = $param->merchant_name;
		}
		if($param->segment !=''){
			$datasearch["segment"] = $param->segment;
		}
		$max_set = 100;
		$offset = 0;
			
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/product_ggi_all".$this->config->csv->ext;
			$result= $this->db_product->getMerchantPromoAmount($q ,$datasearch,$offset,'all');
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/product_ggi_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function exportcsvdetailAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch_detail = array();
		$q_detail='';
		
		if($param->q_detail!=''){
			$q_detail = $param->q_detail;
		}
		if($param->merchant_name_detail!=""){
			$datasearch_detail["merchant_name_detail"] = $param->merchant_name_detail;
		}
		if($param->promotion_name_detail!=""){
			$datasearch_detail["promotion_name_detail"] = $param->promotion_name_detail;
		}
		if($param->segment_detail !=''){
			$datasearch_detail["segment_detail"] = $param->segment_detail;
		}
		if($param->start_date !=''){
			$datasearch_detail["start_date"] = $param->start_date;
		}
		if($param->end_date !=''){
			$datasearch_detail["end_date"] = $param->end_date;
		}
		$max_set = 100;
		$offset = 0;
			
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/product_ggi_detail_all".$this->config->csv->ext;
			$result= $this->db_product->getMerchantPromoDetail($q_detail , $datasearch_detail, $offset, 'all');
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/product_ggi_detail_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function getcountamountAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant_name!=""){
			$datasearch["merchant_name"] = $param->merchant_name;
		}
		if($param->segment !=''){
			$datasearch["segment"] = $param->segment;
		}
		
		echo $this->db_product->getCountMerchantPromoAmount($q ,$datasearch);
	}
	
	public function getcountdetailAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$datasearch_detail = array();
		$q_detail='';
		
		if($param->q_detail!=''){
			$q_detail = $param->q_detail;
		}
		if($param->merchant_name_detail!=""){
			$datasearch_detail["merchant_name_detail"] = $param->merchant_name_detail;
		}
		if($param->promotion_name_detail!=""){
			$datasearch_detail["promotion_name_detail"] = $param->promotion_name_detail;
		}
		if($param->segment_detail !=''){
			$datasearch_detail["segment_detail"] = $param->segment_detail;
		}
		if($param->start_date !=''){
			$datasearch_detail["start_date"] = $param->start_date;
		}
		if($param->end_date !=''){
			$datasearch_detail["end_date"] = $param->end_date;
		}
		
		echo $this->db_product->getCountMerchantPromoDetail($q_detail ,$param->merchant_id, $datasearch_detail);
	}

	public function indexAction(){    
		$param = (object) $this->getRequest()->getQuery();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->merchant_name!=""){
			$datasearch["merchant_name"] = $param->merchant_name;
		}
		if($param->segment !=''){
			$datasearch["segment"] = $param->segment;
		}
		$max_set = $this->config->productggi->segment->max->set;
		$offset = 0;
			
		$result= $this->db_product->getMerchantPromoAmount($q ,$datasearch, $offset, $max_set);
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->merchant_promo_amount = Zend_Json::encode($data);
				
		$this->view->param = $param;
	}
	
	public function promodetailAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();

		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$max_set = $this->config->productggi->segment->max->set;

		$datasearch_detail = array();
		$q_detail='';
		$offset_detail=0;
		
		if($param->q_detail!=''){
			$q_detail = $param->q_detail;
		}

		if($param->merchant_name_detail!=""){
			$datasearch_detail["merchant_name_detail"] = $param->merchant_name_detail;
		}
		if($param->promotion_name_detail!=""){
			$datasearch_detail["promotion_name_detail"] = $param->promotion_name_detail;
		}
		if($param->segment_detail !=''){
			$datasearch_detail["segment_detail"] = $param->segment_detail;
		}
		if($param->start_date !=''){
			$datasearch_detail["start_date"] = $param->start_date;
		}
		if($param->end_date !=''){
			$datasearch_detail["end_date"] = $param->end_date;
		}
		

		$result= $this->db_product->getMerchantPromoDetail($q_detail , $datasearch_detail, $param->merchant_id, $offset_detail, $max_set);
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);
	}

	// ---------------------------------------------------------------------------------------------
	// Promo Segment
	// ---------------------------------------------------------------------------------------------
	
	public function promosegmentAction(){
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$limit = $this->config->sgift->merchant->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->title!=""){
			$data["title"] = $param->title;
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
		
		$result = $this->db_sgift->getPromoSegment($q, $start, $limit, $data, $start_date, $end_date, $range); 
		
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);		
		
		$this->view->param = $param;
		
		if($user->roleID==$this->config->role->id->cs){
			LogController::insert($this->conn_postgre,  // Koneksi Database
								  $this->config->template->log->view,  // Flag
								  $this->config->const->activity->view,  // Flag
								  $this->mySession->id,  // UserID
								  "cssgift", // Menu
								  $data); 
		 }
	}
	
	public function getpromosegmentdetailAction(){
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$result = $this->db_sgift->getPromoSegmentDetail($param->user_id); 
			
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo  Zend_Json::encode($_data);
	}
	
	public function loadmorepromosegmentAction(){
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$limit = $this->config->sgift->merchant->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->title!=""){
			$data["title"] = $param->title;
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
		
		$result = $this->db_sgift->getPromoSegment($q, $start, $limit, $data, $start_date, $end_date, $range); 
		
		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);		
	}
	
	public function gettotalpromosegmentAction(){
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$limit = $this->config->sgift->merchant->max->set; // how many data will be shown 
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->title!=""){
			$data["title"] = $param->title;
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
		$total = $this->db_sgift->getTotalPromoSegment($q, $data, $start_date, $end_date, $range); 		
		echo  $total;
	}
	
	public function exportcsvpromosegmentAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "all";
		
		if ($param->q != '' || $param->title != '' )
		{
			if($param->q!=''){
				$q = $param->q;
			}
			if($param->title!=''){
				$data["title"] = $param->title;
			}
		}
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/promosegment_all".$this->config->csv->ext;
			$result = $this->db_sgift->getPromoSegmentDetail(); 
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/promo_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
}