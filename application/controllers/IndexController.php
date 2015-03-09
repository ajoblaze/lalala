<?php
require_once "db_user/Db_User.php";
class IndexController extends Zend_Controller_Action
{
	private $url;
	private $mySession,$config;
	private $datauser;

	public function init()
	{
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;

		//$this->mySession = new Zend_Session_Namespace("Latihan");
		//$this->view->mySession = $this->mySession;

		//$this->config = Zend_Registry::get("config");
		//$this->view->config = $this->config;


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

		foreach($row as $datauser)
		{
			if($param->txtuser == $row[""] && $param->txtpass == $)
			{
				$this->mySession->unlock();
				$this->mySession->username = $param->txtuser;
				$this->mySession->role = $param->txtuser;
				$this->mySession->lock();
				$this->_redirect("main");
			}
		}
		else
		{
			$this->_redirect("index?err=1");
		}

	}
}
