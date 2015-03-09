<?php
require_once "Zend/Session/Namespace.php";
require_once "Zend/Config/Ini.php";

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	public $mySession;
	public $config;
	
	public function _initSession(){
		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->mySession->lock();
	}
	
	public function _initConfig(){
		$this->config = new Zend_Config($this->getOptions(), true);
		Zend_Registry::set('config',$this->config);
		return $this->config;
	}
	
}

