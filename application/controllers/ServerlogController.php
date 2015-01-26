<?php
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "LogController.php";
require_once "db_pg_serverlog/Db_Pg_Serverlog.php";

class ServerlogController extends Zend_Controller_Action
{
	var $url;
	var $config;
	var $library;
	var $conn, $conn_postgre;
	var $conn_sgift, $conn_sgift_postgre;
	var $db_project;
	var	$db_sgift;
	var $db_serverlog;
	
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
		
		// Create Connection		
		$this->conn_postgre = (object) array("host" => $this->config->resources->db->postgre->host,
											 "username" => $this->config->resources->db->postgre->username,
											 "pass" => $this->config->resources->db->postgre->pass,
											 "db" => $this->config->resources->db->postgre->db);
									
		$this->conn_sgift_postgre = (object) array("host" => $this->config->resources->db->sgift->postgre->host,
											"username" => $this->config->resources->db->sgift->postgre->username,
											"pass" => $this->config->resources->db->sgift->postgre->pass,
											"db" => $this->config->resources->db->sgift->postgre->db);
											
		// Import Library
		$this->library = (object) array(
											"exporter" => new Exporter()
										);
		// Configuration for View
		$this->view->attr = (Object) array(
											"title" => "Server Log"
										  );
		
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication($this->config->project->id->sgift);
		
		// Create entity of database
		$this->db_project = new Db_Pg_Project($this->conn_postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->conn_sgift_postgre);
		$this->db_serverlog = new Db_Pg_Serverlog($this->conn_sgift_postgre);
		
		// Set Variable
		$this->view->projects = $this->db_project->getVisibleProject();
		$this->initView();
	}
	
	public function exportcsvredeemlogAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei_or_mac;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$limit = 1000;
		
		$path = "";		 
		if ($param->export == 0) {

			$path = "uploads/redeem_log_all".$this->config->csv->ext;
			
			$result =$this->db_serverlog->getRedeemLog($q, $datasearch,$limit,0, $param->start_redeem, $param->end_redeem);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/redeem_log_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function exportcsvclaimlogAction()
	{	
		$this->_helper->viewRenderer->setNoRender();
		
		$param = (object) $this->getRequest()->getPost();
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->redeem_code !=''){
			$datasearch["slc.redeem_code"] = $param->redeem_code;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$limit = 1000;
		
		$path = "";		 
		if ($param->export == 0) {

			$path = "uploads/claim_log_all".$this->config->csv->ext;
			
			$result =$this->db_serverlog->getClaimLog($q, $datasearch,$limit,0, $param->start_claim, $param->end_claim);
			
			$this->library->exporter->exportToCSVFromDB($result, $path);
		}
		else { 
			$path = "uploads/claim_log_current".$this->config->csv->ext;
			$json = $param->json;
			$this->library->exporter->exportToCSVFromJSON($json, $path);
		}	
		echo $path;
	}
	
	public function checkValid()
	{
		// Back to login if session is invalid
		if (!isset($this->mySession->username) ) {
			$this->_redirect("");
		}
	}
	
	public function indexAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		$this->_redirect("serverlog/redeemlog");
	}
	
	public function redeemlogAction()
	{
		// Get Parameter
		$param = (object) $this->getRequest()->getQuery();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei_or_mac;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$max_set = $this->config->redeem_log->max->set;
		$offset = 0;
			
		$result= $this->db_serverlog->getRedeemLog($q, $datasearch, $max_set, $offset, $param->start_redeem, $param->end_redeem);
		
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->redeem_log = Zend_Json::encode($data);
		$this->view->param = $param;
	}
	
	public function gettotalredeemAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei_or_mac;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$count = $this->db_serverlog->getRedeemCountLog($q, $datasearch, $param->start_redeem, $param->end_redeem);
		
		echo $count;
	}

	public function claimlogAction()
	{
		$param = (object) $this->getRequest()->getQuery();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->redeem_code !=''){
			$datasearch["slc.redeem_code"] = $param->redeem_code;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$max_set = $this->config->claim_log->max->set;
		$offset = 0;
		
		$result= $this->db_serverlog->getClaimLog($q, $datasearch, $max_set, $offset, $param->start_claim, $param->end_claim);
		
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		$this->view->claim_log = Zend_Json::encode($data);
		$this->view->param = $param;
	}
	
	public function gettotalclaimAction()
	{
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch = array();
		$q='';
	
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->redeem_code !=''){
			$datasearch["slc.redeem_code"] = $param->redeem_code;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$count = $this->db_serverlog->getClaimCountLog($q, $datasearch, $param->start_claim, $param->end_claim);
		
		echo $count;
	}
	
	public function redeemloadmoreAction()
	{
		$this->_helper->viewRenderer->setNoRender();
	
		$param = (object) $this->getRequest()->getPost();
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$max_set = $this->config->redeem_log->max->set;
		
		$result= $this->db_serverlog->getRedeemLog($q, $datasearch, $max_set, $param->offset);
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
	
		echo Zend_Json::encode($data);
	}
	
	public function claimloadmoreAction()
	{
		$this->_helper->viewRenderer->setNoRender();
	
		$param = (object) $this->getRequest()->getPost();
		
		$datasearch = array();
		$q='';
		
		if($param->q!=''){
			$q = $param->q;
		}
		if($param->email!=""){
			$datasearch["email"] = $param->email;
		}
		if($param->imei_or_mac!=""){
			$datasearch["di.name"] = $param->imei;
		}
		if($param->model !=''){
			$datasearch["di.model"] = $param->model;
		}
		if($param->merchant!=''){
			$datasearch["pm.name"] = $param->merchant;
		}	
		if($param->promo_name !=''){
			$datasearch["coc.name"] = $param->promo_name;
		}
		if($param->status != '') {
			$datasearch["slc.redeem_code"] = $param->redeem_code;
		}
		if($param->status != '') {
			$datasearch["result_message"] = $param->status;
		}
		
		if($param->status_code != '') {
			$datasearch["server_response_code::text"] = $param->status_code;
		}
		
		$max_set = $this->config->claim_log->max->set;
		
		$result= $this->db_serverlog->getClaimLog($q, $datasearch, $max_set, $param->offset);
		$data =  $this->getFrontController()->getParam("bootstrap")->convertResultToArray($result);
		echo Zend_Json::encode($data);
	}
}