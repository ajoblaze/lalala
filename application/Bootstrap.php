<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
//Load Common Zend Library
require_once "Zend/Form.php";
require_once "Zend/Session/Namespace.php";
require_once "Zend/Config/Ini.php";
require_once "Zend/Paginator.php";
require_once "Zend/Http/Client.php";
require_once "Zend/Http/Cookie.php";
require_once "Zend/File/Transfer/Adapter/Http.php";
require_once "PHPMailer/class.phpmailer.php";

// Include Custom Library
require_once "Validate/validate.php";
require_once "Exporter/exporter.php";
require_once "Html/html.php";

// Include Db Authentication
require_once "db_pg_authentication/Db_Pg_Authentication.php";

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	var $mySession;
	var $myCookie;
	var $services;
	var $cache_expire;
	var $writer, $logger, $config, $linker, $redirector;
	var $db_authenticate;
	static $MAINTENANCE = "maintenance", $ACTIVE = "active";
	public function _initSession()
	{
		// Set the Session
		$this->mySession = new Zend_Session_Namespace("user_data");
		
		// Lock the Session
		$this->mySession->lock();
	}
	
	public function _initServices()
	{
		// Init Services
		$this->services = new Zend_Http_Client();
		return $this->services;
	}
	
	public function _initConfig()
	{
		// Init the Configuration
		$this->config = new Zend_Config($this->getOptions(), true);
		Zend_Registry::set('config', $this->config);
		return $this->config;
	}
	
	// Other useful function
	public function unsetSession()
	{
		// Clear all Session
		$this->mySession->unsetAll();
	}
	
	public function convertResultToArray($result)
	{
		if (is_array($result)) {
			return $result;
		}
		$arr = array();
		while ($row = $result->fetch_object())
		{
			array_push($arr,$row);
		}
		return $arr;
	}
	
	public function sendEmail($config)
	{
		$mail = new PHPMailer();
		/* $mail->IsSMTP();
		$mail->SMTPDebug = 0;
		$mail->SMTPAuth = 'login';
		$mail->SMTPSecure = 'ssl'; 
		$mail->Host = 'smtp.samsung.com';
		$mail->Username = 'kovan.c@partner.samsung.com';
		$mail->Password = 'chan1293'; */
		
		$mail->Host = "srinapps.com";
		$mail->Port = 25;
		$mail->Username   = "info@srinapps.com";
		$mail->Password   = "srinappsinfo123";
		$mail->From = $config['from'];
		$mail->FromName = $config['from_name'];
		$mail->Subject = $config['subject'];
		$mail->Body = $config['body'];
		$mail->IsHTML(true);
		$mail->AddAddress($config['to']);

		if(!$mail->Send()) {
			return "Mailer Error (" . str_replace("@", "&#64;", $config['to']) . ') ' . $mail->ErrorInfo . '<br />';
		} else {
			return 1;
		}
	}
	
	public function hitServices($url, $data)
	{
		try {
			$this->services->setUri($url);
			$this->services->setHeaders(array(
											"Authorization" => "Basic ZGFmdXE6bXNjaQ==",
											"Content-Type" => "application/json"
										));
			$json = Zend_Json::encode($data);
			$this->services->setRawData($json, "application/json");										
			$result = $this->services->request(Zend_Http_Client::POST);		
			$object = Zend_Json::decode($result->getBody(), false);
		} catch (Zend_Exception $e) {
			$this->logToFirebug($e->getMessage());
		}
		return $object;
	}
	
	// Logging Function
	public function logToFirebug($text, $type = Zend_Log::INFO)
	{
		$this->writer = new Zend_Log_Writer_Firebug();
		$this->logger = new Zend_Log($this->writer);
		$this->logger->log($text, $type);
	}
	
	public function logToFile($text, $path = "log.txt")
	{
		$this->writer = new Zend_Log_Writer_Stream($path);
		$this->logger = new Zend_Log($this->writer);
		 
		$this->logger->info($text);
	}
	
	// Authentication
	public function checkAuthentication($allowedProject = "")
	{
		$this->linker = Zend_Controller_Front::getInstance();
		$this->redirector = new Zend_Controller_Action_Helper_Redirector();
		$currentUrl = $this->linker->getRequest()->getRequestUri();
		$baseUrl = $this->linker->getRequest()->getBaseUrl();
		$controller = str_replace($baseUrl."/","",$currentUrl);
		
		$error_state = array_search("error", $controller);
		if ($this->config->webconfig->web->state == self::$MAINTENANCE && !is_numeric($error)) {
			$this->redirector->gotoUrl("error/maintenance");
		} else if (!isset($this->mySession->username)) {
			$this->redirector->gotoUrl("");
		} else {
			$this->db_authenticate = new Db_Pg_Authentication($this->config->resources->db->postgre);
			$allow = $this->db_authenticate->checkLink($this->mySession->role, "/".$controller);
			
			$this->logToFirebug($allow);
			if ($allow == false) {
				$this->redirector->gotoUrl('error/error403');
			} else if($allowedProject != "") {
				$search = array_search($allowedProject, $this->mySession->project);
				if (!is_numeric($search)) { 
					$this->redirector->gotoUrl('error/error403');
				} 
			}
		}
	}
	
	// Maintenance State
	public function checkValid()
	{	
		$this->linker = Zend_Controller_Front::getInstance();
		$this->redirector = new Zend_Controller_Action_Helper_Redirector();
		$currentUrl = $this->linker->getRequest()->getRequestUri();
		$baseUrl = $this->linker->getRequest()->getBaseUrl();
		$controller = str_replace($baseUrl."/","",$currentUrl);
		
		$error_state = array_search("error", $controller);
		if ($this->config->webconfig->web->state == self::$MAINTENANCE && !is_numeric($error)) {
			$this->redirector->gotoUrl("error/maintenance");
		} else if (isset($this->mySession->username)) {
			$this->redirector->gotoUrl("main");
		}
	}
}

