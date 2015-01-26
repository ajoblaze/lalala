<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Project extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "projectid";
		$this->table = "msproject";
		$this->kode = "PR";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getProject()
	{
		$sql = $this->db->select()
						->from($this->table, array("projectID" => "projectid",
												   "projectName" => "projectname",
												   "projectLink" => "projectlink",
												   "visibility" => "visibility"));
		return $this->db->fetchAll($sql);
	}
		
	public function getProjectById($projectID)
	{
		$sql = $this->db->select()
						->from($this->table, array("projectID" => "projectid",
												   "projectName" => "projectname",
												   "projectLink" => "projectlink",
												   "visibility" => "visibility"))
						->where($this->primary_column." = ".$this->escape($projectID));
		$row = $this->db->fetchRow($sql);
		return $row;
	}
	
	public function insert($dataToInsert)
	{			
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete($projectID)
	{
		// Using Basic Delete instead of Custom
		$this->basic_delete($projectID);
	}
	
	// Custom Function place here -- 
	public function getVisibleProject()
	{
		$sql = $this->db->select()
						->from($this->table, array("projectID" => "projectid",
												   "projectName" => "projectname",
												   "projectLink" => "projectlink",
												   "visibility" => "visibility"))
						->where("visibility = ?", 1)
						->order("projectid ASC");
		$row = $this->db->fetchAll($sql);
		return $row; 
	}
	
	// + Added by Pinto Luhur
	// + Created on 22 / 08 / 2014
	
	//SAMSUNG APPS
	
	public function getAllDailyApps($id)
	{
		$sql = $this->db->select()
						->from("install_samsungapp", array("id" => "id",
														   "datelabel" => "TO_CHAR(install_date, 'dd Mon YYYY')",
														   "install_date" => "install_date",
														   "daily_install" => "daily_install"))
						->where("projectid = ?", $this->validate($id))
						->order("install_date ASC");
		//echo $sql;
		return $this->db->fetchAll($sql);
	}
	
	public function get7DayDailyApps($id)
	{
		$sql = $this->db->select()
						->from("install_samsungapp", array("id" => "id", 
														   "datelabel" => "TO_CHAR(install_date, 'dd Mon YYYY')",
														   "install_date" => "install_date",
														   "daily_install" => "daily_install"))
						->where("install_date BETWEEN NOW() - CAST('11 days' AS INTERVAL) AND NOW()")
						->where("projectid = ?", $this->validate($id))
						->order("install_date ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function get30DayDailyApps($id)
	{
		$sql = $this->db->select()
						->from("install_samsungapp", array("id" => "id",
														   "datelabel" => "TO_CHAR(install_date, 'dd Mon YYYY')",
														   "install_date" => "install_date",
														   "daily_install" => "daily_install"))
						->where("install_date BETWEEN NOW() - CAST('34 days' AS INTERVAL) AND NOW()")
						->where("projectid = ?", $this->validate($id))
						->order("install_date ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function getCustomDailyApps($id, $startDate, $endDate)
	{
		$sql = $this->db->select()
						->from("install_samsungapp", array("id" => "id",
														   "datelabel" => "TO_CHAR(install_date, 'dd Mon YYYY')",
														   "install_date" => "install_date",
														   "daily_install" => "daily_install"))
						->where("install_date BETWEEN ".$this->escape($startDate)." AND ".$this->escape($endDate))
						->where("projectid = ?", $this->validate($id))
						->order("install_date ASC");
		return $this->db->fetchAll($sql);
	}
	
	// -- STATISTIC
	public function getAllDaily($id)
	{
		$sql = $this->db->select()
						->from("transaction_install", array("datelabel" => "TO_CHAR(transactiondate, 'dd Mon YYYY')",
														   "transactiondate" => "transactiondate",
														   "dailydownload" => "dailydownload",
														   "daily_uninstall" => "daily_uninstall"))
						->where("projectid = ?", $this->validate($id))
						->order("transactiondate ASC");
		
		return $this->db->fetchAll($sql);
	}
	
	public function get7DayDaily($id)
	{
		$sql = $this->db->select()
						->from("transaction_install", array("datelabel" => "TO_CHAR(transactiondate, 'dd Mon YYYY')",
														   "transactiondate" => "transactiondate",
														   "dailydownload" => "dailydownload",
														   "daily_uninstall" => "daily_uninstall"))
						->where("transactiondate BETWEEN NOW() - CAST('11 days' AS INTERVAL) AND NOW()")
						->where("projectid = ?", $this->validate($id))
						->order("transactiondate ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function get30DayDaily($id)
	{
		$sql = $this->db->select()
						->from("transaction_install", array("datelabel" => "TO_CHAR(transactiondate, 'dd Mon YYYY')",
														   "transactiondate" => "transactiondate",
														   "dailydownload" => "dailydownload",
														   "daily_uninstall" => "daily_uninstall"))
						->where("transactiondate BETWEEN NOW() - CAST('34 days' AS INTERVAL) AND NOW()")
						->where("projectid = ?", $this->validate($id))
						->order("transactiondate ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function getCustomDaily($id, $startDate, $endDate)
	{
		$sql = $this->db->select()
						->from("transaction_install", array("datelabel" => "TO_CHAR(transactiondate, 'dd Mon YYYY')",
														   "transactiondate" => "transactiondate",
														   "dailydownload" => "dailydownload",
														   "daily_uninstall" => "daily_uninstall"))
						->where("transactiondate BETWEEN ".$this->escape($startDate)." AND ".$this->escape($endDate))
						->where("projectid = ?", $this->validate($id))
						->order("transactiondate ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function getTotalDaily($id)
	{
		$sql = $this->db->select()
						->from("tr_install_copy", array("total" => "COUNT(ProjectID)"))
						->where("projectid = ?", $this->validate($id));
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	
	// + Added by Nadia Alviana
	// + Created on 25 / 08 / 2014
	
	public function getDashboard()
	{
		$sql = "SELECT mp.projectname AS \"projectName\", mp.projectlink AS \"projectLink\",
				( SELECT SUM(dailydownload) AS TotalDownload FROM transaction_install WHERE projectid = p.projectid ) AS total_install,
				( SELECT COALESCE(SUM(daily_uninstall),0) AS TotalUninstall FROM transaction_install WHERE projectid = p.projectid ) AS total_uninstall,
				( SELECT CONCAT(CONCAT(COALESCE(average_rating,0.0), ' / '), COALESCE(total_people_rating,0)) AS rating FROM transaction_install WHERE projectid = p.projectid LIMIT 1 OFFSET 0 ) AS rating,
				( SELECT COALESCE((SUM(daily_crashes) + SUM(daily_anrs)),0) AS total_error FROM overall_error WHERE projectid = p.projectid ) AS total_error,
				( select COUNT(DISTINCT(carrier_name)) AS total_carrier FROM carrier_install WHERE projectid = p.projectid ) AS total_carrier,
				( SELECT COUNT(DISTINCT(device_name)) AS total_devices FROM device_install WHERE projectid = p.projectid ) AS total_devices 
				FROM project AS p INNER JOIN msproject AS mp ON p.linkid = mp.projectid";
				
		return $this->db->fetchAll($sql);
	}
	
	public function getSlimeDashboard()
	{
		$sql = "SELECT mp.projectname AS \"projectName\", mp.projectlink AS \"projectLink\",
				( SELECT SUM(dailydownload) AS TotalDownload FROM transaction_install WHERE projectid = p.projectid ) AS total_install,
				( SELECT COALESCE(SUM(daily_uninstall),0) AS TotalUninstall FROM transaction_install WHERE projectid = p.projectid ) AS total_uninstall,
				( SELECT CONCAT(CONCAT(COALESCE(average_rating,0.0), ' / '), COALESCE(total_people_rating,0)) AS rating FROM transaction_install WHERE projectid = p.projectid LIMIT 1 OFFSET 0 ) AS rating,
				( SELECT COALESCE((SUM(daily_crashes) + SUM(daily_anrs)),0) AS total_error FROM overall_error WHERE projectid = p.projectid ) AS total_error,
				( select COUNT(DISTINCT(carrier_name)) AS total_carrier FROM carrier_install WHERE projectid = p.projectid ) AS total_carrier,
				( SELECT COUNT(DISTINCT(device_name)) AS total_devices FROM device_install WHERE projectid = p.projectid ) AS total_devices 
				FROM project AS p INNER JOIN msproject AS mp ON p.linkid = mp.projectid WHERE p.projectid = 1";
				
		return $this->db->fetchAll($sql);
	}
	
	public function getAllDevices($id)
	{
		$sql = $this->db->select()
						->from("device_install", array("current_device_installs" => "MAX(current_device_installs)",
													   "device_name" => "device_name"))
						->where("projectid = ?", $this->validate($id))
						->group("device_name")
						->order("current_device_installs DESC")
						->limit(5,0);
		return $this->db->fetchAll($sql);
	}
	
	public function getAllDataDevices($id)
	{
		$sql = "select 
					distinct(device_name), 
					MAX(current_device_installs) as current_device_installs, 
					segment
				from device_install dins
				left outer join device_segment3 dseg on lower(dseg.product_name) = lower(trim(substring(dins.device_name from 0 for position('(' in dins.device_name))))
				where projectid = $id
				group by device_name, segment";
		$result =  $this->db->fetchAll($sql);	
		$dict = array();
		
		foreach ($result as $a){
			$device_name = preg_replace('/\(.*\)/','',$a->device_name);
			$name = $device_name;
			$device_name = str_replace(' ', '', $device_name);
			$device_name = strtolower($device_name);	
			$segment  = ($a->segment==NULL) ?'-':$a->segment;
			
			if (array_key_exists($device_name, $dict)) {
				$dict[$device_name][0]+=$a->current_device_installs;
				
				if($segment!='-'){
					$dict[$device_name][2]=$segment;
				}
			}
			else{
				$dict[$device_name]=array(
										$a->current_device_installs, 
										$name, 
										$segment
									);
			}
		}
		
		$ar0 = array();
		$ar1 = array();
		$ar2 = array();
		
		foreach ($dict as $key => $row) {
			array_push($ar0, $row[0]);// current_device_installs
			array_push($ar1, $row[1]);// device name
			array_push($ar2, $row[2]);// device segment
		}
		$len = count($ar0);
		
		array_multisort($ar0, SORT_DESC, $ar1, SORT_ASC, $ar2);
		
		$ans = array();
		
		for ($i=0;$i<$len;$i+=1) {
			array_push($ans, array('device_name'=>$ar1[$i], 
									'current_device_installs'=>$ar0[$i], 
									'segment'=>$ar2[$i])
					);
		}	
		return $ans;
	}
	
	public function getAllCarrier($id) {
		$sql = $this->db->select()
						->from("carrier_install", array("current_device_installs", "carrier_name", "carrier_date"))
						->where("projectid = ?", $this->validate($id))
						->order(array("carrier_date DESC", "current_device_installs DESC"))
						->limit(5,0);
		return $this->db->fetchAll($sql);
	}
	
	public function getAllDataCarrier($id) {
		$sql = $this->db->select()
						->from("carrier_install", array("carrier_name" => "DISTINCT(carrier_name)",
														"current_device_installs" => "MAX(current_device_installs)"))
						->where("projectid = ?", $this->validate($id))
						->group("carrier_name")
						->order("current_device_installs DESC");
		return $this->db->fetchAll($sql);
	}

	public function getAllTotalDeviceInstall($id)
	{
		$sql = $this->db->select()
						->from("device_install", array("total" => "SUM(daily_device_installs)"))
						->where("projectid = ?", $this->validate($id));
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	public function getAllTotalDevice($id)
	{
		$sql = $this->db->select()
						->from("device_install", array("total" => "COUNT(DISTINCT(device_name))"))
						->where("projectId = ?", $this->validate($id));
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	//for carrier
	public function getAllTotalCarrierInstall($id)
	{
		$sql = $this->db->select()
						->from("carrier_install", array("total" => "SUM(daily_device_installs)"))
						->where("projectid = ?", $this->validate($id));
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	public function getAllTotalCarrier($id)
	{
		$sql = $this->db->select()
						->from("carrier_install", array("total" => "COUNT(DISTINCT(carrier_name))"))
						->where("projectid = ?", $this->validate($id));
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	
	public function getAllErrorDaily($id)
	{
		$sql =	"SELECT 
					to_char(a.date, 'DD Mon YYYY')  AS datelabel,
					a.date,
					a.daily_crashes,
					a.daily_anrs,
					count(detail) as total_detail,
					(select count(os) from os_error where date=a.date and projectid=".$this->escape($id).") as total_os,
					(select count(device) from device_error where date=a.date and projectid=".$this->escape($id).") as total_device,
					(select count(app_version) from app_version_error where date=a.date and projectid=".$this->escape($id).") as total_appversion
				FROM
					overall_error a
					join detail_error b on (a.date=b.date and a.projectid=b.projectid)
				WHERE a.projectid = ".$this->escape($id)."
				GROUP BY a.date, a.daily_crashes, a.daily_anrs
				ORDER BY date ASC";
		return $this->db->fetchAll($sql);
	}
	
	public function get7DayErrorDaily($id)
	{
		//$sql = "SELECT to_char(date, 'DD Mon YYYY') AS datelabel, date, daily_crashes, daily_anrs FROM overall_error WHERE date BETWEEN NOW() - INTERVAL '11 DAY' AND NOW() AND projectid = ".$this->escape($id)."ORDER BY date ASC";
		$sql =	"SELECT 
					to_char(a.date, 'DD Mon YYYY')  AS datelabel,
					a.date,
					a.daily_crashes,
					a.daily_anrs,
					count(detail) as total_detail,
					(select count(os) from os_error where date=a.date and projectid=".$this->escape($id).") as total_os,
					(select count(device) from device_error where date=a.date and projectid=".$this->escape($id).") as total_device,
					(select count(app_version) from app_version_error where date=a.date and projectid=".$this->escape($id).") as total_appversion
				FROM
					overall_error a
					join detail_error b on (a.date=b.date and a.projectid=b.projectid)
				WHERE 
					a.date BETWEEN NOW() - INTERVAL '11 DAY' AND NOW() AND
					a.projectid = ".$this->escape($id)."
				GROUP BY a.date, a.daily_crashes, a.daily_anrs
				ORDER BY date ASC";
		return $this->db->fetchAll($sql);
	}
	
	public function get30DayErrorDaily($id)
	{
		//$sql = "SELECT to_char(date, 'DD Mon') AS datelabel, date, date as temp, daily_crashes, daily_anrs FROM overall_error WHERE date BETWEEN NOW() - INTERVAL '34 DAY' AND NOW() AND projectid = ".$this->escape($id)."ORDER BY date ASC";
		$sql =	"SELECT 
					to_char(a.date, 'DD Mon YYYY')  AS datelabel,
					a.date,
					a.daily_crashes,
					a.daily_anrs,
					count(detail) as total_detail,
					(select count(os) from os_error where date=a.date and projectid=".$this->escape($id).") as total_os,
					(select count(device) from device_error where date=a.date and projectid=".$this->escape($id).") as total_device,
					(select count(app_version) from app_version_error where date=a.date and projectid=".$this->escape($id).") as total_appversion
				FROM
					overall_error a
					join detail_error b on (a.date=b.date and a.projectid=b.projectid)
				WHERE 
					a.date BETWEEN NOW() - INTERVAL '34 DAY' AND NOW() AND
					a.projectid = ".$this->escape($id)."
				GROUP BY a.date, a.daily_crashes, a.daily_anrs
				ORDER BY date ASC";
		return $this->db->fetchAll($sql);
	}
	
	public function getCustomErrorDaily($id, $startDate, $endDate)
	{
		//$sql = "SELECT to_char(date, 'DD Mon') AS datelabel, date, daily_crashes, daily_anrs FROM overall_error WHERE date BETWEEN '".$startDate."' AND '".$endDate."' AND projectid = ".$this->escape($id)."ORDER BY date ASC";
		$sql =	"SELECT 
					to_char(a.date, 'DD Mon YYYY')  AS datelabel,
					a.date,
					a.daily_crashes,
					a.daily_anrs,
					count(detail) as total_detail,
					(select count(os) from os_error where date=a.date and projectid=".$this->escape($id).") as total_os,
					(select count(device) from device_error where date=a.date and projectid=".$this->escape($id).") as total_device,
					(select count(app_version) from app_version_error where date=a.date and projectid=".$this->escape($id).") as total_appversion
				FROM
					overall_error a
					join detail_error b on (a.date=b.date and a.projectid=b.projectid)
				WHERE 
					a.date BETWEEN '".$startDate."' AND '".$endDate."' AND
					a.projectid = ".$this->escape($id)."
				GROUP BY a.date, a.daily_crashes, a.daily_anrs
				ORDER BY date ASC";
		return $this->db->fetchAll($sql);
	}
	
	//TAMBAHAN YANG OS, DEVICE, APP VERSION, DETAIL TABLE TERPISAH
	
	public function getAllErrorOs($id, $tgl)
	{
		$sql = $this->db->select()
						->from("os_error", array("datelabel" => "to_char(date, 'DD Mon YYYY')",
												 "date" => "date",
												 "OS" => "os",
												 "daily_crashes" => "daily_crashes",
												 "daily_anrs" => "daily_anrs"))
						->where("to_char(date, 'DD Mon YYYY') = ?", $this->validate($tgl))
						->where("projectid = ?",$this->validate($id))
						->order("date ASC");						
		return $this->db->fetchAll($sql);
	}
	
	public function getAllErrorDevice($id, $tgl)
	{
		$sql = $this->db->select()
						->from("device_error", array("datelabel" => "to_char(date, 'DD Mon YYYY')",
													 "date" => "date",
													 "Device" => "device",
													 "daily_crashes" => "daily_crashes",
													 "daily_anrs" => "daily_anrs"))
						->where("date = ?",$this->validate($tgl))
						->where("projectid = ?", $this->validate($id))
						->order("date ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function getAllErrorAppversion($id, $tgl)
	{
		$sql = $this->db->select()
						->from("app_version_error", array("datelabel" => "to_char(date, 'DD Mon YYYY')",
														   "date" => "date",
														   "App Version" => "app_version",
														   "daily_crashes" => "daily_crashes",
														   "daily_anrs" => "daily_anrs"))
						->where("date = ?", $this->validate($tgl))
						->where("projectid = ?", $this->validate($id))
						->order("date ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function getAllErrorDetail($id, $tgl)
	{
		$sql = $this->db->select()
						->from("detail_error", array(
														"datelabel" => "to_char(date, 'DD Mon YYYY')",
														"date" => "date",
														"Detail" => "detail",
														"daily_crashes" => "daily_crashes",
														"daily_anrs" => "daily_anrs"
													))
						->where("date = ?", $this->validate($tgl))
						->where("projectid = ?", $this->validate($id))
						->order("date ASC");
		return $this->db->fetchAll($sql);
	}
	
	//END
	
	public function getTotalError($id)
	{
		$sql ="SELECT SUM(daily_crashes + daily_anrs) AS TotalError FROM overall_error WHERE projectid = ".$this->escape($id);
		$row = $this->db->fetchRow($sql);
		return $row->TotalError;
	}
	
	public function getTotalCrash($id)
	{
		$sql ="SELECT SUM(daily_crashes) AS TotalCrash FROM overall_error WHERE projectid = ".$this->escape($id);
		$row = $this->db->fetchRow($sql);
		return $row->TotalCrash;
	}
	
	public function getTotalAnrs($id)
	{
		$sql ="SELECT SUM(daily_anrs) AS TotalAnrs FROM overall_error WHERE projectid = ".$this->escape($id);
		$row = $this->db->fetchRow($sql);
		return $row->TotalAnrs;
	}
	
	public function getTotalErrorDaily($id)
	{
		$sql ="SELECT COUNT(error_id) AS total FROM overall_error WHERE projectid = ".$this->escape($id);
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}	
	public function getAllDownloads(){
		$sql = $this->db->select()
						->from("ggi_downloads_active_installs", array("ggi_downloads_total",
																	  "ggi_downloads_premium",
																	  "ggi_downloads_mid",
																	  "ggi_downloads_entry",
																	  "ggi_downloads_tab",
																	  "ggi_downloads_other"));
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	public function getAllActiveInstalls(){

		$sql = $this->db->select()
						->from("ggi_downloads_active_installs", array("ggi_active_installs_total",
																	  "ggi_active_installs_premium",
																	  "ggi_active_installs_mid",
																	  "ggi_active_installs_entry",
																	  "ggi_active_installs_tab",
																	  "ggi_active_installs_other"));	
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}

	public function getDownloads($id=''){
		$sql = $this->db->select()
						->from(array("dins" => "device_install"), array("total" => "SUM(daily_device_installs)"))
						->joinLeft(array("dseg" => "device_segment3"), "lower(dseg.product_name) = lower(trim(substring(dins.device_name from 0 for position('(' in dins.device_name))))", array("segment"))  
						->where("projectid = ?", $this->validate($id))
						->group("dseg.segment");
		$row = $this->db->fetchAll($sql);
		$data = array();
		$total = 0;
		foreach ($row as $r):
			$segment = ($r->segment == "" )? "Other" : $r->segment;
			$data[$segment] = $r->total;
			$total += $r->total;
		endforeach;
		$data[""] = $total;
		return $data;
	}
	
	public function getActiveInstalls($id=''){
		$where = ($id!='') ? " where projectid=".$this->escape($id) : "";			
		$sql = "select segment, sum(subtotal) as total
					from
					(
						select device_name,max(current_device_installs) as subtotal from device_install ".$where." group by device_name
					)as t1
					left outer join device_segment3 dseg on lower(dseg.product_name) = lower(trim(substring(t1.device_name from 0 for position('(' in t1.device_name))))
					group by dseg.segment";
		
		$row = $this->db->fetchAll($sql);
		$data = array();
		$total = 0;
		foreach ($row as $r):
			$segment = ($r->segment == "" )? "Other" : $r->segment;
			$data[$segment] = $r->total;
			$total += $r->total;
		endforeach;
		$data[""] = $total;
		return $data;
	}
}