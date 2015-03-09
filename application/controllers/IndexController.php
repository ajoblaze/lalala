<?php
class IndexController extends Zend_Controller_Action
{
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

	}

	public function doLoginAction()
	{
		$this->_helper->viewRenderer->setNoRender();





	}

	public function mainAction()
	{
		
	}
}
