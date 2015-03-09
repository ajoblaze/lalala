<?php
require_once "db_user/Db_User.php";
class IndexController extends Zend_Controller_Action
{
	private $url;
	private $mySession,$config;
	private $datauser;

	private  $db_user;
	public function init()
	{
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;

		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->view->mySession = $this->mySession;

		$this->config = Zend_Registry::get("config");
		$this->view->config = $this->config;
	
		$this->db_user = new Db_User($this->config->resources->db);

		$this->initView();
	}

	public function indexAction()
	{
		$param = (object) $this->getRequest()->getQuery();

		$error = "";
		if(isset($param->err))
		{
			switch($param->err)
			{
				case 1: $error = "Invalid Username / Password"; break;
				case 2: $error = "Registration Successful"; break;
				case 3: break;
				default: break;
			}
			$this->view->error = $error;
		}
	}

	public function dologinAction()
	{
		$this->_helper->viewRenderer->setNoRender();

		$param = (object) $this->getRequest()->getPost();

		$datauser = $this->db_user->getUser();

		foreach($datauser as $row)
		{
			if($param->txtuser == $row->username && md5($param->txtpass) == $row->password)
			{
				$this->mySession->unlock();
				$this->mySession->name = $row->name;
				$this->mySession->role = $row->role;
				$this->mySession->lock();
				$this->_redirect("main");
			}
		}
		$this->_redirect("index?err=1");

	}
}
