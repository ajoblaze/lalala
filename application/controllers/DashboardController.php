<?php
require_once "db_pg_user/Db_Pg_User.php";
require_once "db_pg_project/Db_Pg_Project.php";
require_once "db_pg_sgift/Db_Pg_Sgift.php";
require_once "db_pg_slime/Db_Pg_Slime.php";
require_once "db_pg_segment/Db_Pg_Segment.php";
require_once "LogController.php";

class DashboardController extends Zend_Controller_Action
{
	var $url;
	var $config;
	var $first;
	var $library;
	var $conn, $conn_postgre;
	static $SLIME_ID = 1, $SGIFT_ID = 2;
	static $TIME_DAILY = "daily", $TIME_ALL = "all", $TIME_WEEKLY = "weekly", $TIME_MONTHLY = "monthly", $APP_SGIFT = "ggi", $APP_SLIME="slime";
	var $db_project, $db_user, $db_sgift, $db_segment;
	public function init()
	{
		//Init View
		$this->url = $this->getRequest()->getBaseURL();
		$this->view->baseUrl = $this->url;
		
		//Initialize Session
		$this->mySession = new Zend_Session_Namespace("user_data");
		$this->view->mySession = $this->mySession;
	
		/*Get Configuration Value*/
		$this->config = Zend_Registry::get('config');
		$this->view->config = $this->config;
				
		// Include Custom Library
		$this->library = (object) array( "validate" => new Validate() );
		
		// Property
		$this->view->attr = (object) array("title" => "");
		
		// Check Session
		$this->getFrontController()->getParam("bootstrap")->checkAuthentication();
		
		// Create entity of Database
		$this->db_user = new Db_Pg_User($this->config->resources->db->postgre);
		$this->db_project = new Db_Pg_Project($this->config->resources->db->postgre);
		$this->db_sgift = new Db_Pg_Sgift($this->config->resources->db->sgift->postgre);
		$this->db_slime = new Db_Pg_Slime($this->config->resources->db->newslime->postgre);
		$this->db_segment = new Db_Pg_Segment($this->config->resources->db->sgift->postgre);
		
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
	
	public function indexAction()
	{
		$this->view->attr->title = "Google Play Dashboard";
		$this->view->dashboardData = $this->db_project->getDashboard();
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "DASHBOARD",
							  ""
							 );
	}	
	
	public function ubaseAction()
	{
		$this->view->attr->title = "User-base Dashboard";
		$downloadsData=array();
		$activeinstallsData=array();
		$regusersData=array();
		$activeMonthly=array();
		$activeWeekly=array();
		$activeDaily=array();
		$activeMonthlyTemp = array();
		$activeWeeklyTemp = array();
		$activeDailyTemp = array();
		$totalDailyActive = array();
		$temp = array(0,1,3,4);
		$segments = array('','Premium' , 'Mid',  'Entry', 'Tab','Other');
		$segment_labels = array('Total', 'Premium' ,'Medium' , 'New Entry', 'Tablet', 'Others');
		$apps=array('Galaxy Gift ID', 'S LIME');

		$result = $this->db_sgift->getTotalDailyActive();
		foreach($result[0] as $k=>$v):
			array_push($totalDailyActive, $v);
		endforeach;
		
		$slime_down = $this->db_project->getDownloads(self::$SLIME_ID);
		$slime_act = $this->db_project->getActiveInstalls(self::$SLIME_ID);
		$slime_reg = $this->db_slime->getRegisteredCustomer();
		$sgift_down = $this->db_project->getDownloads(self::$SGIFT_ID);
		$sgift_act = $this->db_project->getActiveInstalls(self::$SGIFT_ID);
		$sgift_reg = $this->db_sgift->getRegUser();
	
		$slime_month = $this->db_slime->getActiveCustomer(Db_Pg_Slime::$ACTIVE_MONTHLY);
		$slime_week = $this->db_slime->getActiveCustomer(Db_Pg_Slime::$ACTIVE_WEEKLY);
		$slime_daily = $this->db_slime->getActiveCustomer(Db_Pg_Slime::$ACTIVE_DAILY);
		$sgift_month = $this->db_sgift->getAllActiveUser(Db_Pg_Sgift::$ACTIVE_MONTHLY);
		$sgift_week = $this->db_sgift->getAllActiveUser(Db_Pg_Sgift::$ACTIVE_WEEKLY);
		$sgift_daily = $this->db_sgift->getAllActiveUser(Db_Pg_Sgift::$ACTIVE_DAILY);
		
		foreach($sgift_month as $k=>$v):
			array_push($activeMonthly, $v);
		endforeach;		
		foreach($sgift_week as $k=>$v):
			array_push($activeWeekly, $v);
		endforeach;
		foreach($sgift_daily as $k=>$v):
			array_push($activeDaily, $v);
		endforeach;

 		for($x=2;$x>=1;$x--){
			foreach($segments as $segment):
					if($x==2){
						array_push($activeinstallsData, $sgift_act[$segment]);
						array_push($downloadsData, $sgift_down[$segment]);
						array_push($regusersData, $sgift_reg[$segment]);
					} else {
						array_push($activeinstallsData, $slime_act[$segment]);
						array_push($downloadsData, $slime_down[$segment]);
						array_push($regusersData, $slime_reg[$segment]);
						array_push($activeMonthly, $slime_month[$segment]);
						array_push($activeWeekly, $slime_week[$segment]);
						array_push($activeDaily, $slime_daily[$segment]);
					}
			endforeach;
		} 
		
		foreach($temp as $x):
			array_push($activeMonthlyTemp, $activeMonthly[$x]);
			array_push($activeWeeklyTemp, $activeWeekly[$x]);
			array_push($activeDailyTemp, $activeDaily[$x]);
		endforeach;
		
		$this->view->downloadsData = $downloadsData;
		$this->view->activeinstallsData = $activeinstallsData;
		$this->view->regusersData = $regusersData;
		$this->view->activeDaily=$activeDaily;
		$this->view->activeWeekly=$activeWeekly;
		$this->view->activeMonthly=$activeMonthly;
		$this->view->apps = $apps;
		$this->view->segments = $segments;
		$this->view->segment_labels = $segment_labels;
		$this->view->activeMonthlyTemp=Zend_Json::encode($activeMonthlyTemp);
		$this->view->activeWeeklyTemp=Zend_Json::encode($activeWeeklyTemp);
		$this->view->activeDailyTemp=Zend_Json::encode($activeDailyTemp);
		$this->view->totalDailyActive = $totalDailyActive;
		$this->view->allDeviceSegment = Zend_Json::encode($this->db_segment->getAllDeviceSegment());
		
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "DASHBOARD user base",
							  ""
							 );
	}
	
	
	public function slimedashboardAction()
	{
		$this->view->attr->title = "Dashboard";
		$this->view->dashboardData = $this->db_project->getSlimeDashboard();
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "DASHBOARD",
							  ""
							 );
	}
	
	public function publisherdashboardAction()
	{
		$this->view->attr->title = "Publisher Dashboard";
		// Logging
		LogController::insert($this->config->resources->db->postgre,
							  $this->config->template->log->view,
							  $this->config->const->activity->view,
							  $this->mySession->id,
							  "PUBLISHER DASHBOARD",
							  ""
							 );
	}

	public function activeuserAction(){
		$this->_helper->viewRenderer->setNoRender();
		$activeMonthly=array();
		$activeWeekly=array();
		$activeDaily=array();
		$result = $this->db_sgift->getAllActiveUser(Db_Pg_Sgift::$ACTIVE_MONTHLY);
		foreach($result as $k=>$v):
			array_push($activeMonthly, $v);
		endforeach;		
		$result = $this->db_sgift->getAllActiveUser(Db_Pg_Sgift::$ACTIVE_WEEKLY);
		foreach($result as $k=>$v):
			array_push($activeWeekly, $v);
		endforeach;
		$result = $this->db_sgift->getAllActiveUser(Db_Pg_Sgift::$ACTIVE_DAILY);
		foreach($result as $k=>$v):
			array_push($activeDaily, $v);
		endforeach;
		echo Zend_Json::encode($activeMonthly).";".Zend_Json::encode($activeWeekly).";".Zend_Json::encode($activeDaily);
	}

	public function getPromoSegment($dayInterval) {
		$totalPromo=array();
		$totalVoucher=array();
		$totalRedeem=array();
		$totalClaim=array();
		$segments = array('Premium', 'Entry', 'Mid', 'Tab');
		$channels = array(2,1,3);
		$segment_labels = array('Total', 'Premium', 'Entry', 'Mid', 'Tab');
		
		$promoData = $this->db_sgift->getTotalPromo("", $dayInterval);
		$voucherData = $this->db_sgift->getTotalVoucher("", $dayInterval);
		$redeemData = $this->db_sgift->getTotalRedeem("", $dayInterval);
		$claimData = $this->db_sgift->getTotalClaim("", $dayInterval, true);
		

		$totalAllClaim = 0;
		$totalAllPromo = 0;
		$totalAllVoucher = 0;
		$totalAllRedeem = 0;
		
		foreach($channels as $channel) : 
			$totalAllPromo += $promoData[$channel];
			$totalAllVoucher += $voucherData[$channel];
		endforeach;
		
		foreach ($segments as $segment) :
			$totalAllClaim += $claimData[$segment];		
			$totalAllRedeem += $redeemData[$segment];
		endforeach;

		array_push($totalPromo, $totalAllPromo);
		array_push($totalVoucher, $totalAllVoucher);
		array_push($totalClaim, $totalAllClaim);
		array_push($totalRedeem, $totalAllRedeem);
		foreach($segments as $segment):
			array_push($totalPromo, $promoData[$segment]);
			array_push($totalVoucher, $voucherData[$segment]);
			array_push($totalRedeem, $redeemData[$segment]);
			array_push($totalClaim, $claimData[$segment]);
		endforeach;
		echo Zend_Json::encode($totalPromo).";".Zend_Json::encode($totalVoucher).";".Zend_Json::encode($totalRedeem).";".Zend_Json::encode($totalClaim);
	}

	public function promosegmentAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getQuery();
		
		switch($param->time) {
			case self::$TIME_DAILY : $this->getPromoSegment(1); break;
			case self::$TIME_WEEKLY : $this->getPromoSegment(7); break;
			case self::$TIME_MONTHLY : $this->getPromoSegment(30); break;
		}
	}	
		
	public function getdataAction(){
		// Disable View
		$this->_helper->viewRenderer->setNoRender();
		
		// Get Parameter
		$param = (object) $this->getRequest()->getQuery();
		$temp= array();
		
		if ($param->app == self::$APP_SGIFT) {
			switch($param->time) {
				case self::$TIME_ALL : $result = $this->db_sgift->getActiveUserForAll(); break;
				case self::$TIME_WEEKLY : $result = $this->db_sgift->getActiveUserForWeekly(); break;
				case self::$TIME_MONTHLY : $result = $this->db_sgift->getActiveUserForMonthly(); break;
			}
		} else {
			$result = $this->db_slime->getActiveUserForAll($param->time);
		}
		echo Zend_Json::encode($result);
	}
	
	public function chartAction() {
		$result = $this->db_sgift->getCurrentPromo();
		$this->view->promos = $result;
	}
	
	public function getclaimAction() {
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();		
		$date = $param->date;
		$temp= array();
		$result = $this->db_sgift->getMinutesClaim($date, $param->start_time, $param->end_time, $param->promo_id);
		$result2 = $this->db_sgift->getMinutesRedeem($date, $param->start_time, $param->end_time, $param->promo_id);
		$max_temp=$param->date." ".$param->end_time;
		$cur_time =  date_create($date." ".$param->start_time);
		$cur_time = date_add($cur_time, date_interval_create_from_date_string("+ 1 minutes"));
		$max_time = date_create((isset($result[sizeof($result)-1]->curdate)?$result[sizeof($result)-1]->curdate:$max_temp));
		$start_date = date_format($cur_time, 'Y-m-d H:i');
		$end_date = date_format($max_time, 'H:i');
		$data =array();
		foreach($result as $value):
			$data[$value->curtime] = $value->total;
		endforeach;
		$data2 =array();
		foreach($result2 as $value):
			$data2[$value->curtime] = $value->total;
		endforeach;		
		
		$temp_claim = array();
		$temp_redeem = array();
		$data_claim = array();
		$data_redeem = array();
		while($cur_time <= $max_time){
			/* $temp_claim = array("time" =>date_format($cur_time, 'H:i'),
								"value" => ($data[date_format($cur_time, 'H:i')]!=null)?$data[date_format($cur_time, 'H:i')]:0);
			$temp_redeem = array("time" => date_format($cur_time, 'H:i'),
								 "value" => ($data2[date_format($cur_time, 'H:i')]!=null)?$data2[date_format($cur_time, 'H:i')]:0);
			array_push($data_claim, $temp_claim);
			array_push($data_redeem, $temp_redeem); */
			$temp_claim[date_format($cur_time, 'H:i')] = ($data[date_format($cur_time, 'H:i')]!=null)?$data[date_format($cur_time, 'H:i')]:0;
			$temp_redeem[date_format($cur_time, 'H:i')] = ($data2[date_format($cur_time, 'H:i')]!=null)?$data2[date_format($cur_time, 'H:i')]:0;
			$cur_time = date_add($cur_time, date_interval_create_from_date_string("+ 1 minutes"));
		}
		echo Zend_Json::encode($temp_claim).";".date('m/d/Y', strtotime($date)).";".date('m/d/Y',strtotime($end_date)).";".Zend_Json::encode($temp_redeem);	
	}
	
	public function getgroupclaimAction(){
		$this->_helper->viewRenderer->setNoRender();
		$param = (object) $this->getRequest()->getPost();	
		if($param->type=="week"){
			$result = $this->db_sgift->getWeekPromo('claim');
			$result2 = $this->db_sgift->getWeekPromo('redeem');
		}
		else{
			$result = $this->db_sgift->getMonthPromo('claim');
			$result2 = $this->db_sgift->getMonthPromo('redeem');
		}
		echo Zend_Json::encode($result).";".Zend_Json::encode($result2);
	}
}