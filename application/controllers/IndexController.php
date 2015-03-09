<?php
class IndexController extends Zend_Controller_Action
{
	private $url;
	private $mySession,$config;

	public function init()
	{
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;

		$this->mySession = new Zend_Session_Namespace("Latihan");
		$this->view->mySession = $this->mySession;

		$this->config = Zend_Registry::get("config");
		$this->view->config = $this->config;


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
				case 1: $error = "Invalid Username / Password";break;
				case 2: break;
				default: break;
			}
			$this->view->error = $error;
		}
	}

	public function doLoginAction()
	{
		$this->_helper->viewRenderer->setNoRender();

		$param = (object) $this->getRequest()->getPost();

		if($param->txtuser == "user" && $param->txtpass == "user")
		{
			$this->mySession->unlock();
			$this->mySession->username = $param->txtuser;
			$this->mySession->lock();

			$this->_redirect("main");
		}
		else
		{
			$this->_redirect("index?err=1");
		}

	}

	public function mainAction()
	{
		
	}
}
