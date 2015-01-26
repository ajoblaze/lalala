<?php
require_once "db_pg_outlet/Db_Pg_Outlet.php";
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_user/Db_Pg_User.php";

class OutletController extends Zend_Controller_Action
{
	var $url;
	var $first;
	var $conn;
	var $db_outlet, $db_project, $db_user, $db_sgift; 
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
		$this->view->attr = (Object) array( "title" => "" );
											
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->sgift);
		
		// Create entity of database
		$this->db_outlet = new Db_Pg_Outlet($this->config->resources->db->sgift->postgre);
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		
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
	
	public function indexAction() {
		$param = (object) $this->getRequest()->getQuery();
		$data = array();
		$offset = 0;
		$limit = $this->config->sgift->outlet->promo->max->set; // how many data will be shown 
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->provider_name!="") {
			$data["provider_name"] = $param->provider_name;
		}
		if($param->promo_id!="") {
			$data["promo_id"] = $param->promo_id;
		}
		if($param->promo_name !='') {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->claim_type !='') {
			$data["claim_type"] = $param->claim_type;
		}
		if($param->merchant_id !='') {
			$data["merchant_id"] = $param->merchant_id;
		}
		if($param->merchant_name !='') {
			$data["merchant_name"] = $param->merchant_name;
		}
		if($param->outlet_id !='') {
			$data["outlet_id"] = $param->outlet_id;
		}
		if($param->outlet_name !='') {
			$data["outlet_name"] = $param->outlet_name;
		}
		if($param->start_time !='') {
			$data["start_time"] = $param->start_time;
		}
		if($param->end_time !='') {
			$data["end_time"] = $param->end_time;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		# find out query stat point
		$start = ($page * $limit) - $limit;
		//echo "start: ".$start." page in php: ".$page;
		
		//$offset = 0;
		//$limit = 100;
		
		// $result = $this->db_slime->getContentsFragment($q, $data, $offset, $limit, $param->start_release, $param->end_release);
		$result = $this->db_outlet->getOutletPerPromo($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs = Zend_Json::encode($_data);
		
		$data_merchant = array();
		$q_merchant='';
		//$offset_merchant=0;
		
		if($param->q_merchant!=''){
			$q_merchant = $param->q_merchant;
		}
		if($param->merchant_id_merchant!=""){
			$data_merchant["merchant_id_merchant"] = $param->merchant_id_merchant;
		}
		if($param->merchant_name_merchant!=""){
			$data_merchant["merchant_name_merchant"] = $param->merchant_name_merchant;
		}
		if($param->outlet_id_merchant !=''){
			$data_merchant["outlet_id_merchant"] = $param->outlet_id_merchant;
		}
		if($param->outlet_name_merchant !=''){
			$data_merchant["outlet_name_merchant"] = $param->outlet_name_merchant;
		}
		if($param->city_merchant !=''){
			$data_merchant["city_merchant"] = $param->city_merchant;
		}
		
		$offset_merchant = 0;
		$limit_merchant = 100;
		
		$result= $this->db_outlet->getOutletPerMerchant($q_merchant, $data_merchant, $offset_merchant, $limit_merchant);
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->arrs2 = Zend_Json::encode($data);
		
		$this->view->param = $param;
	}
	
	public function loadmoreoutletAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->sgift->outlet->promo->max->set;
		$claim_type = array();
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->provider_name!="") {
			$data["provider_name"] = $param->provider_name;
		}
		if($param->promo_id!="") {
			$data["promo_id"] = $param->promo_id;
		}
		if($param->promo_name !='') {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->claim_type1 == 1) {
			array_push($claim_type, "offline");
		}
		if($param->claim_type2 == 1) {
			array_push($claim_type, "online");
		}
		if($param->merchant_id !='') {
			$data["merchant_id"] = $param->merchant_id;
		}
		if($param->merchant_name !='') {
			$data["merchant_name"] = $param->merchant_name;
		}
		if($param->outlet_id !='') {
			$data["outlet_id"] = $param->outlet_id;
		}
		if($param->outlet_name !='') {
			$data["outlet_name"] = $param->outlet_name;
		}
		if($param->start_time !='') {
			$data["start_time"] = $param->start_time;
		}
		if($param->end_time !='') {
			$data["end_time"] = $param->end_time;
		}
		$data['claim_type'] = $claim_type;
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_outlet->getOutletPerPromo($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
	}
	
	public function gettotaloutletAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		$claim_type = array();
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->provider_name!="") {
			$data["provider_name"] = $param->provider_name;
		}
		if($param->promo_id!="") {
			$data["promo_id"] = $param->promo_id;
		}
		if($param->promo_name !='') {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->claim_type1 == 1) {
			array_push($claim_type, "offline");
		}
		if($param->claim_type2 == 1) {
			array_push($claim_type, "online");
		}
		if($param->merchant_id !='') {
			$data["merchant_id"] = $param->merchant_id;
		}
		if($param->merchant_name !='') {
			$data["merchant_name"] = $param->merchant_name;
		}
		if($param->outlet_id !='') {
			$data["outlet_id"] = $param->outlet_id;
		}
		if($param->outlet_name !='') {
			$data["outlet_name"] = $param->outlet_name;
		}
		if($param->start_time !='') {
			$data["start_time"] = $param->start_time;
		}
		if($param->end_time !='') {
			$data["end_time"] = $param->end_time;
		}
		$data['claim_type'] = $claim_type;
		$count = $this->db_outlet->getCountPerPromo($q, $data);
		
		echo $count;
	}
	
	public function loadmoremerchantAction(){
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		$limit = $this->config->sgift->outlet->merchant->max->set;
		
		if($param->q_merchant!=''){
			$q_merchant = $param->q_merchant;
		}
		if($param->merchant_id_merchant!=""){
			$data_merchant["merchant_id_merchant"] = $param->merchant_id_merchant;
		}
		if($param->merchant_name_merchant!=""){
			$data_merchant["merchant_name_merchant"] = $param->merchant_name_merchant;
		}
		if($param->outlet_id_merchant !=''){
			$data_merchant["outlet_id_merchant"] = $param->outlet_id_merchant;
		}
		if($param->outlet_name_merchant !=''){
			$data_merchant["outlet_name_merchant"] = $param->outlet_name_merchant;
		}
		if($param->city_merchant !=''){
			$data_merchant["city_merchant"] = $param->city_merchant;
		}
		
		if($param->page == '')
			$page=1;
		else
			$page = intval($param->page);
		// # find out query stat point
		$offset = ($page * $limit) - $limit;
		
		$result = $this->db_outlet->getOutletPerPromo($q, $data, $offset, $limit);

		$_data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($_data);
	}
	
	public function gettotalmerchantAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter 
		$param = (object) $this->getRequest()->getPost();
		
		if($param->q_merchant!=''){
			$q_merchant = $param->q_merchant;
		}
		if($param->merchant_id_merchant!=""){
			$data_merchant["merchant_id_merchant"] = $param->merchant_id_merchant;
		}
		if($param->merchant_name_merchant!=""){
			$data_merchant["merchant_name_merchant"] = $param->merchant_name_merchant;
		}
		if($param->outlet_id_merchant !=''){
			$data_merchant["outlet_id_merchant"] = $param->outlet_id_merchant;
		}
		if($param->outlet_name_merchant !=''){
			$data_merchant["outlet_name_merchant"] = $param->outlet_name_merchant;
		}
		if($param->city_merchant !=''){
			$data_merchant["city_merchant"] = $param->city_merchant;
		}
		$count = $this->db_outlet->getCountPerMerchant($q_merchant, $data_merchant);
		
		echo $count;
	}
	
	public function exportcsvAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "";
		$offset = 0;
		$claim_type = array();
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->provider_name!="") {
			$data["provider_name"] = $param->provider_name;
		}
		if($param->promo_id!="") {
			$data["promo_id"] = $param->promo_id;
		}
		if($param->promo_name !='') {
			$data["promo_name"] = $param->promo_name;
		}
		if($param->claim_type1 == 1) {
			array_push($claim_type, "offline");
		}
		if($param->claim_type2 == 1) {
			array_push($claim_type, "online");
		}
		if($param->merchant_id !='') {
			$data["merchant_id"] = $param->merchant_id;
		}
		if($param->merchant_name !='') {
			$data["merchant_name"] = $param->merchant_name;
		}
		if($param->outlet_id !='') {
			$data["outlet_id"] = $param->outlet_id;
		}
		if($param->outlet_name !='') {
			$data["outlet_name"] = $param->outlet_name;
		}
		if($param->start_time !='') {
			$data["start_time"] = $param->start_time;
		}
		if($param->end_time !='') {
			$data["end_time"] = $param->end_time;
		}
		$data['claim_type'] = $claim_type;
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/ggi_outlet_all".$this->config->csv->ext;
			
			$result = $this->db_outlet->getOutletPerPromo($q, $data, $offset, $limit);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/ggi_outlet_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function exportcsvmerchantAction()
	{	
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$data = array();
		$q='';
		$limit = "";
		$offset = 0;
		
		if($param->q_merchant!=''){
			$q_merchant = $param->q_merchant;
		}
		if($param->merchant_id_merchant!=""){
			$data_merchant["merchant_id_merchant"] = $param->merchant_id_merchant;
		}
		if($param->merchant_name_merchant!=""){
			$data_merchant["merchant_name_merchant"] = $param->merchant_name_merchant;
		}
		if($param->outlet_id_merchant !=''){
			$data_merchant["outlet_id_merchant"] = $param->outlet_id_merchant;
		}
		if($param->outlet_name_merchant !=''){
			$data_merchant["outlet_name_merchant"] = $param->outlet_name_merchant;
		}
		if($param->city_merchant !=''){
			$data_merchant["city_merchant"] = $param->city_merchant;
		}
		
		$path = "";		 
		if ($param->export == 0) {
			$path = "uploads/ggi_merchant_all".$this->config->csv->ext;
			
			$result = $this->db_outlet->getOutletPerMerchant($q_merchant, $data_merchant, $offset, $limit);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/ggi_merchant_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
}