<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Slime extends Db_PDO
{
	var $db;
	static $SUSPEND = "enabled", $ACTIVATE = "disabled";
	static $ACTIVE_MONTHLY = 30, $ACTIVE_WEEKLY = 7, $ACTIVE_DAILY = 1;
	static $TYPE_NEWSPAPER = 1, $TYPE_MAGAZINE = 2, $TYPE_BOOK = 3;
	static $GRAPH_DAILY = "daily", $GRAPH_WEEKLY = "weekly", $GRAPH_MONTHLY = "monthly";
	static $TIME_ALL = "all", $TIME_MONTHLY = "monthly", $TIME_WEEKLY = "weekly";
	static $THIS_MONTH = "this", $LAST_MONTH = "last", $ALL_MONTH = "all";
	var $mtr = "mtr_";
	public function __construct($dbconfig)
	{
		$this->primary_column = "";
		$this->table = "";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function
	
	// -----------------------------------------------------------------------------------------------------------------------
	// REVENUE TABLE
	// -----------------------------------------------------------------------------------------------------------------------
	
	public function getRevenueTable()
	{
		$sql = $this->db->select()
						->from("publisher", array("id", "name"))
						->where("status = 'enabled' ".$where_pub)
						->order("name ASC");
		$row = $this->db->fetchAll($sql);
		
		// GET REVENUE TOTAL
		$this_month = $this->getRevenueColumn(self::$THIS_MONTH);
		$last_month = $this->getRevenueColumn(self::$LAST_MONTH);
		$all_month = $this->getRevenueColumn(self::$ALL_MONTH);
		
		array_push($row, (object) array("id" => 0, "name" => "Package"));
		
		$data = array();
		foreach($row as $r) : 
			$r->this_month = ($this_month[$r->name]->total == null) ? 0 : $this_month[$r->name]->total;
			$r->last_month = ($last_month[$r->name]->total == null) ? 0 : $last_month[$r->name]->total;
			$r->all_month = ($all_month[$r->name]->total == null) ? 0 : $all_month[$r->name]->total;
			array_push($data, $r);
		endforeach;
		return $data;
	}
	
	public function getRevenuePublisherTable($publisher = "")
	{	
		$where_pub = ($publisher == "") ? "pu.name ~* ''" : "UPPER(pu.name) = ".$this->escape(strtoupper($publisher));
		$sql = $this->db->select()
						->distinct()
						->from(array("p" => $this->mtr."product"), array("product" => "name"))
						->join(array("p2" => $this->mtr."product"), "p.parent_product_id = p2.id", array())
						->join(array("pu" => $this->mtr."publisher"), "pu.id = p2.publisher_id", array("publisher" => "name"))
						->where("p.type = ?", "SINGLE")
						->where($where_pub)
						->order("p.name ASC");
		$row = $this->db->fetchAll($sql);
		
		// GET REVENUE TOTAL
		$this_month = $this->getRevenuePublisherColumn(self::$THIS_MONTH);
		$last_month = $this->getRevenuePublisherColumn(self::$LAST_MONTH);
		$all_month = $this->getRevenuePublisherColumn(self::$ALL_MONTH);
		
		if ($publisher == "") {
			array_push($row, (object) array("product" => "Package", "publisher" => "-"));
		}
		
		$data = array();
		foreach($row as $r) : 
			$r->this_month = ($this_month[$r->product]->total == null) ? 0 : $this_month[$r->product]->total;
			$r->last_month = ($last_month[$r->product]->total == null) ? 0 : $last_month[$r->product]->total;
			$r->all_month = ($all_month[$r->product]->total == null) ? 0 : $all_month[$r->product]->total;
			array_push($data, $r);
		endforeach;
		return $data;
	}
	
	public function getRevenueColumn($type = "this")
	{
		$sql = "SELECT COALESCE(publisher,'Package') AS publisher, total FROM revenue_".$type."_month_dshutama()";
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r):
			$data[$r->publisher] = $r;
		endforeach;
		return $data;
	}
	
	public function getRevenuePublisherColumn($type, $publisher)
	{
		$sql = "SELECT COALESCE(product,'Package') AS product, publisher, total FROM revenue_".$type."_month_dshpublisher()";
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r):
			$data[$r->product] = $r;
		endforeach;
		return $data;
	}
	
	// -----------------------------------------------------------------------------------------------------------------------
	// END
	// -----------------------------------------------------------------------------------------------------------------------
	
	// -----------------------------------------------------------------------------------------------------------------------
	// USER BASE CHART
	// -----------------------------------------------------------------------------------------------------------------------
	
	public function getActiveUserForAll($type){	
		switch($type)
		{
			case self::$TIME_ALL : $sql = $this->db->select()
											->from(array("sg" => "server_log_login_gplus"), array("datelabel" => "TO_CHAR(log_time, 'dd Mon')",
																								  "date" => "TO_CHAR(log_time, 'YYYY-MM-DD')",
																								  "Jumlah" => "COUNT(logid)"))
											->join(array("dseg" => "device_segment3"), "REPLACE(sg.device_model, 'Samsung ', '')  = dseg.device_model", array("segment"))
											->group(array("datelabel", "date", "dseg.segment"))
											->order("date ASC"); break;
			case self::$TIME_WEEKLY : $sql = $this->db->select()
											->from(array("sg" => "server_log_login_gplus"), array("datelabel" => "TO_CHAR(date_trunc('week', log_time), 'dd Mon')",
																								  "date" => "TO_CHAR(date_trunc('week', log_time),'YYYY-MM-DD')",
																								  "Jumlah" => "COUNT(logid)"))
											->join(array("dseg" => "device_segment3"), "REPLACE(sg.device_model, 'Samsung ', '')  = dseg.device_model", array("segment"))
											->group(array("datelabel", "date", "dseg.segment"))
											->order("date ASC"); break;
			case self::$TIME_MONTHLY : $sql = $this->db->select()
											->from(array("sg" => "server_log_login_gplus"), array("datelabel" => "TO_CHAR(date_trunc('month', log_time), 'dd Mon')",
																								  "date" => "TO_CHAR(date_trunc('month', log_time),'YYYY-MM-DD')",
																								  "Jumlah" => "COUNT(logid)"))
											->join(array("dseg" => "device_segment3"), "REPLACE(sg.device_model, 'Samsung ', '')  = dseg.device_model", array("segment"))
											->group(array("datelabel", "date", "dseg.segment"))
											->order("date ASC") ;break;
		}
		$row = $this->db->fetchAll($sql);
		$data = array();
		$assoc = array();
		
		// Convert to Assoc
		foreach($row as $r):
			if (!isset($assoc[$r->date])) {
				$assoc[$r->date] = (object) array();
				$assoc[$r->date]->date  = $r->date;
				$assoc[$r->date]->datelabel  = $r->datelabel;
			}
			$property = strtolower($r->segment);
			$assoc[$r->date]->$property = $r->Jumlah;
			$assoc[$r->date]->entry = 0;
			$assoc[$r->date]->total = $assoc[$r->date]->mid + $assoc[$r->date]->premium + $assoc[$r->date]->tab; 
		endforeach;
		
		foreach($assoc as $key=> $value) :
			array_push($data, $value);
		endforeach;
		return $data;
	}
		
	// -----------------------------------------------------------------------------------------------------------------------
	// END
	// -----------------------------------------------------------------------------------------------------------------------
	
 	public function getProductPreview($productID)
	{
		$sql = $this->db->select()
						->from(array("p" => "product"), array("id", "name"))
						->joinLeft(array("pi" => "product_preview_image"), "p.id = pi.product_id ", array())
						->joinLeft(array("sf" => "static_file"), "pi.static_file_id = sf.id", array("file" => "name"))
						->where("p.id = ?", $this->validate($productID))
						->limit(5,0);
		return $this->db->fetchAll($sql);
	} 

	public function getPublicationCount($publisher = "")
	{
		$where_pub = ($publisher == "") ? "p.author ~* ''" : "upper(p.author) LIKE  ".$this->escape(strtoupper($publisher)."%");
		$sql = $this->db->select()
						->from(array("p" => $this->mtr."product"), array("Jumlah" => "COUNT( DISTINCT p.name)"))
						->join(array("p2" => $this->mtr."product"), "p.parent_product_id = p2.id", array())
						->join(array("pu" => $this->mtr."publisher"), "pu.id = p2.publisher_id", array())
						->where("p.type = ?", "SINGLE")
						->where($where_pub);
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function getPublisher($publisher="")
	{
		$where_pub = ($publisher != "") ? " AND name = ".$this->escape($publisher) : "";  
		$sql = $this->db->select()
						->from("publisher", array("id", "name"))
						->where("status = 'enabled' ".$where_pub);
		return $this->db->fetchAll($sql);
	}
	
	public function stabilizePie($data, $combinedCol, $jumlah = 5)
	{
		$result = array();
		$i = 0;
		$total = 0;
		foreach($data as $d) :
			if ($i < $jumlah) {
				array_push($result, $d);
			} else {
				$total += $d->$combinedCol;
			}
			$i++;
		endforeach;
		array_push($result, (object) array("name" => "Other", $combinedCol => $total));
		return $result;
	}

	public function stabilizeGraph($param, $time, &$data)
	{
		$startMon = $param["startMon"];
		$endMon = $param["endMon"];
		$startYear = $param["startYear"];
		$endYear = $param["endYear"];
		$startWeek = $param['startWeek'];
		$endWeek = $param['endWeek'];
		$startDate = $param['startDate'];
		$endDate = $param['endDate'];

		// Validate End 
		if ($endMon == 12) { $endYear ++; }
		$endMon = ($endMon % 12) + 1;

		if ($endWeek == 48) { $endYear ++; }
		$endWeek = ($endWeek % 48) + 1;

		$endDate = date_create($endDate);
		$endDate = date_add($endDate, date_interval_create_from_date_string("1 days"));
		switch($time)
		{
			case self::$GRAPH_MONTHLY : 
							// Convert to associative array
							$assoc = array();
							foreach ($data as $d) : 
								$assoc[$d->revenue_month."-".$d->revenue_year] = $d;
							endforeach;
							$month = $startMon;
							$year = $startYear;
							do {
								if (!isset($assoc[$month."-".$year])) {
									array_push($data, (object) array("id" => "", "revenue_month" => $month, "revenue_year" => $year, "name" => "", "publisher" => "", "total_revenue" => "0"));
								}

								$month++;
								if ($month > 12) {
									$month = 1;
									$year ++;
								}
							}while ($month != $endMon || $year != $endYear);
							$data = $this->selectionSort($data, "revenue_year", "revenue_month");
						;break;
			case self::$GRAPH_WEEKLY : 
						// Convert to associative array
						$assoc = array();
						foreach ($data as $d) : 
							$assoc[$d->revenue_week."-".$d->revenue_year] = $d;
						endforeach;
						$week = $startWeek;
						$year = $startYear;
						do {
							if (!isset($assoc[$week."-".$year])) {
								array_push($data, (object) array("id" => "", "revenue_week" => $week, "revenue_year" => $year, "name" => "", "publisher" => "", "total_revenue" => "0"));
							}

							$week++;
							if ($week > 48) {
								$week = 1;
								$year ++;
							}
						}while ($week != $endWeek || $year != $endYear);
						$data = $this->selectionSort($data, "revenue_year", "revenue_week");
						;break;
			case self::$GRAPH_DAILY : 
						$date = date_create($startDate);		
						$assoc = array();
						foreach ($data as $d) : 
							$assoc[date('Y-m-d', strtotime($d->revenue_date))] = $d;
						endforeach;
						do {
							$year = date_format($date, 'Y');
							$month = date_format($date, 'm');
							$day = date_format($date, 'd');
							if (!isset($assoc[date_format($date, 'Y-m-d')])) {
								array_push($data, (object) array("id" => "", "revenue_date" => date_format($date, 'Y-m-d'), "revenue_month" => $month, "revenue_year" => $year, "revenue_day" => $day, "name" => "", "publisher" => "", "total_revenue" => "0"));
							}

							$date = date_add($date, date_interval_create_from_date_string("1 days"));
						}while (date_format($date, 'Y-m-d') != date_format($endDate,'Y-m-d'));
						$data = $this->selectionSort($data, "revenue_date");
						;break;
		}
	}
	
	public function getGraphPublicationRevenue($time, $graphPurpose = true, $publisher = "")
	{
		// Daily 
		$where_pub = ($publisher == "") ? "id::text ~* ''" : "upper(publisher) LIKE  ".$this->escape(strtoupper($publisher)."%");
		switch ($time) {
			case self::$GRAPH_DAILY : $sql = $this->db->select()
													  ->from("daily_revenue_publisher", array("id", "revenue_date", "revenue_day" => "EXTRACT(DAY from revenue_date)", "revenue_month" => "EXTRACT(month FROM revenue_date)", "revenue_year" => "EXTRACT(year FROM revenue_date)", "name" => "COALESCE(name,'Package')", "publisher" => "COALESCE(publisher, 'Package')", "total_revenue")) 
													  ->where($where_pub)
													  ->order("revenue_date ASC"); 
										$row = $this->db->fetchAll($sql);
										$min_date = date('Y-m-d', strtotime($row[0]->revenue_date));
										$max_date = date('Y-m-d', strtotime($row[sizeof($row)-1]->revenue_date));
										$min_month = $row[0]->revenue_month;
										$max_month = $row[sizeof($row)-1]->revenue_month;
										$min_day = $row[0]->revenue_day;
										$max_day = $row[sizeof($row)-1]->revenue_day;
										break;
			case self::$GRAPH_WEEKLY : $sql = $this->db->select()
													   ->from("weekly_revenue_publisher", array("id", "revenue_week", "revenue_year", "name" => "COALESCE(name,'Package')", "publisher" => "COALESCE(publisher, 'Package')", "total_revenue"))
													   ->where($where_pub)
													   ->order(array("revenue_year ASC", "revenue_week ASC"));
										$row = $this->db->fetchAll($sql);
										$min_week = $row[0]->revenue_week;
										$max_week = $row[sizeof($row)-1]->revenue_week;
										$min_month = ceil($row[0]->revenue_week / 4);
										$max_month = ceil($row[sizeof($row)-1]->revenue_week / 4);
										$min_day = (( ($row[0]->revenue_week - 1) % 4) * 7) + 1;
										$max_day = (( ($row[sizeof($row)-1]->revenue_week - 1) % 4) * 7) + 2;
										break;
			case self::$GRAPH_MONTHLY : $sql = $this->db->select()
														->from("monthly_revenue_publisher", array("id", "revenue_month", "revenue_year", "name" => "COALESCE(name,'Package')", "publisher" => "COALESCE(publisher, 'Package')", "total_revenue"))
														->where($where_pub)
														->order(array("revenue_year ASC", "revenue_month ASC")); 
										$row = $this->db->fetchAll($sql);
										$min_month = $row[0]->revenue_month;
										$max_month = $row[sizeof($row)-1]->revenue_month;
										$min_day = 1;
										$max_day = 1;
										break;
		}
		
		if ($graphPurpose == true) {
			$data = array();
			$obj = array();
			$result = array();
			$doc = array();
			
			foreach($row as $r) :
				if (!isset($data[$r->name])) {
					$data[$r->name] = array();
				}			
				array_push($data[$r->name], $r);
			endforeach;
			
			foreach ($data as $key => $value) {
				// Stabilize
				$param = array("startDate" => $min_date, "endDate" => $max_date, "startWeek" => $min_week, "endWeek" => $max_week ,"startMon" => $min_month, "endMon" => $max_month, "startYear" => $row[0]->revenue_year, "endYear" => $row[sizeof($row)-1]->revenue_year);
				$this->stabilizeGraph($param, $time, $value);
				array_push($result, array( "name" => $key, "data" => $value));
			}
			
			$doc = array("min_year" => $row[0]->revenue_year, 
						 "max_year" => $row[sizeof($row)-1]->revenue_year, 
						 "min_month" => $min_month,
						 "max_month" => $max_month,
						 "min_day" => $min_day,
						 "max_day" => $max_day,
						 "result" => $result);
			return $doc;
		} else {
			return $row;
		}
	}
	
	public function getPublicationPieRevenue($publisher = "")
	{
		$where_pub = ($publisher == "") ? "pu.name ~* ''" : "pu.name = ".$this->escape($publisher);
		$sql = $this->db->select()
						->from(array("so" => "subscription_order"), array("total" => "SUM(order_total)"))
						->joinLeft(array("sod" => "subscription_order_detail"), "so.order_id=sod.order_id", array() )
						->joinLeft(array("p" => "product"), "sod.product_id=p.id", array("name"))
						->joinLeft(array("p2" => "product"), "p.parent_product_id = p2.id", array())
						->joinLeft(array("pu" => "publisher"), "p2.publisher_id = pu.id", array() )
						->where("so.status='completed' and p.parent_product_id in (select id from product where publisher_id is not null) and extract(month from order_date)=extract(month from CURRENT_DATE-1) and extract(year from order_date) = extract(year from CURRENT_DATE - 1)")
						->where($where_pub)
						->group("p.name");
		return $this->db->fetchAll($sql);
	}
	
	public function getDeviceContribution($top5 = false, $publisher = "")
	{	
		// $groupCol = ($publisher == "") ? "" : ", pub.name AS publisher";
		// $group = ($publisher == "") ? "" : ", pub.name";
		$where_pub = ($publisher == "") ? "" : "and pub.name is not null AND pub.name ~* ".$this->escape($publisher);
		$sql = "select REPLACE(device_model, 'Samsung ', '') AS name,sum(order_total) AS total from subscription_order so
				left outer join device_reg d
				on(so.app_user_id=d.app_user_id)
				left outer join subscription_order_detail sod on(so.order_id=sod.order_id)
				left outer join product p on(sod.product_id=p.id)
				left outer join (select * from product where parent_product_id=0) pr on (p.parent_product_id=pr.id)
				left outer join publisher pub on(pr.publisher_id=pub.id)
				where so.status='completed' and extract(month from order_date)=extract(month from CURRENT_DATE-1) and extract(year from order_date)=extract(year from CURRENT_DATE-1)
				".$where_pub."
				group by device_model";
				
		if ($top5 == true) {
			$sql .= " ORDER BY total DESC LIMIT 5 OFFSET 0";
		}
		return $this->db->fetchAll($sql);
	}
	
	public function getDownloadPerMonth($publisher = "")
	{
		$where_pub = ($publisher == "") ? "" : " AND publisher ~* ".$this->escape($publisher);
		$sql = $this->db->select()
						->from("download_publisher_per_month", array("name" => "COALESCE(name, 'Package')","total"))
						->where("month::int = extract(month from CURRENT_DATE - 1)  and year::int = extract(year from CURRENT_DATE - 1) ".$where_pub)
						->order("total DESC");
		$row = $this->db->fetchAll($sql);
		$data = $this->stabilizePie($row, "total", 7);
		return $data;
	}
	
	public function getGraphRevenue($time, $graphPurpose = true)
	{
		// Daily 
		switch ($time) {
			case self::$GRAPH_DAILY : $sql = $this->db->select()
													  ->from("daily_revenue", array("id", "revenue_date", "revenue_day" => "EXTRACT(DAY from revenue_date)", "revenue_month" => "EXTRACT(month FROM revenue_date)", "revenue_year" => "EXTRACT(year FROM revenue_date)",  "publisher" => "COALESCE(publisher, 'Package')", "total_revenue")) 
													  ->order("revenue_date ASC"); 
										$row = $this->db->fetchAll($sql);
										$min_date = date('Y-m-d', strtotime($row[0]->revenue_date));
										$max_date = date('Y-m-d', strtotime($row[sizeof($row)-1]->revenue_date));
										$min_month = $row[0]->revenue_month;
										$max_month = $row[sizeof($row)-1]->revenue_month;
										$min_day = $row[0]->revenue_day;
										$max_day = $row[sizeof($row)-1]->revenue_day;
										break;
			case self::$GRAPH_WEEKLY : $sql = $this->db->select()
													   ->from("weekly_revenue", array("id", "revenue_week", "revenue_year", "publisher" => "COALESCE(publisher, 'Package')", "total_revenue"))
														->order(array("revenue_year ASC", "revenue_week ASC"));
										$row = $this->db->fetchAll($sql);
										$min_week = $row[0]->revenue_week;
										$max_week = $row[sizeof($row)-1]->revenue_week;
										$min_month = ceil($row[0]->revenue_week / 4);
										$max_month = ceil($row[sizeof($row)-1]->revenue_week / 4);
										$min_day = (( ($row[0]->revenue_week - 1) % 4) * 7) + 1;
										$max_day = (( ($row[sizeof($row)-1]->revenue_week - 1) % 4) * 7) + 2;
										break;
			case self::$GRAPH_MONTHLY : $sql = $this->db->select()
														->from("monthly_revenue", array("id", "revenue_month", "revenue_year", "publisher" => "COALESCE(publisher, 'Package')", "total_revenue"))
														 ->order(array("revenue_year ASC", "revenue_month ASC")); 
										$row = $this->db->fetchAll($sql);
										$min_month = $row[0]->revenue_month;
										$max_month = $row[sizeof($row)-1]->revenue_month;
										$min_day = 1;
										$max_day = 1;
										break;
		}
		
		if ($graphPurpose == true) {
			$data = array();
			$obj = array();
			$result = array();
			$doc = array();
			
			foreach($row as $r) :
				if (!isset($data[$r->publisher])) {
					$data[$r->publisher] = array();
				}			
				array_push($data[$r->publisher], $r);
			endforeach;
			
			foreach ($data as $key => $value) {
				// Stabilize
				$param = array("startDate" => $min_date, "endDate" => $max_date, "startWeek" => $min_week, "endWeek" => $max_week ,"startMon" => $min_month, "endMon" => $max_month, "startYear" => $row[0]->revenue_year, "endYear" => $row[sizeof($row)-1]->revenue_year);
				$this->stabilizeGraph($param, $time, $value);
				array_push($result, array( "publisher" => $key, "data" => $value));
			}
			
			$doc = array("min_year" => $row[0]->revenue_year, 
						 "max_year" => $row[sizeof($row)-1]->revenue_year, 
						 "min_month" => $min_month,
						 "max_month" => $max_month,
						 "min_day" => $min_day,
						 "max_day" => $max_day,
						 "result" => $result);
			return $doc;
		} else {
			return $row;
		}
	}
	
	public function getTop5Stats($type)
	{
		$sql = $this->db->select()
						->from(array("pr" => $this->mtr."product"), array("name"))
						->join(array("dh" => $this->mtr."download_history"), "pr.id = dh.product_id", array("download" => "COUNT(dh.product_id)"))
						->join(array("pr2" => $this->mtr."product"), "pr.parent_product_id = pr2.id", array())
						->join(array("pu" => $this->mtr."publisher"), "pr2.publisher_id = pu.id", array("publisher" => "name"))
						->join(array("pc" => $this->mtr."product_category"), "pc.product_id = pr2.id", array())
						->join(array("c" => $this->mtr."category"), "pc.category_id = c.id", array())
						->where("c.parent_id = ?", $this->validate($type) )
						->where("pr.type = ?", $this->validate("SINGLE"))
						->group(array("pr.name", "pu.name"))
						->order("download DESC")
						->limit(5,0);
		return $this->db->fetchAll($sql);
	}
	
	public function getMonthPieRevenue()
	{
		$sql = $this->db->select()
						->from(array("so" => "subscription_order"), array())
						->joinLeft(array("sod" => "subscription_order_detail"), "so.order_id=sod.order_id", array())
						->joinLeft(array("p" => "product"), "sod.product_id=p.id", array("author" => "COALESCE(author, 'Package')", "total" => "SUM(product_price)"))
						->where("so.status = ? AND extract(month from order_date)=extract(month from CURRENT_DATE-1) AND extract(year from order_date) = extract(year from CURRENT_DATE - 1)", $this->validate("completed"))
						->group("author");
		return $this->db->fetchAll($sql);		
	}
	
	public function getMontlyPieDownload()
	{
		$sql = "select count(*) as total,author from 
				(select author,app_user_id,product_id,download_time from download_history d left 
				outer join product p on (d.product_id=p.id)
				where extract(month from download_time)=extract(month from CURRENT_DATE - 1) AND extract(year from download_time) = extract(year from CURRENT_DATE - 1)) a
				group by author";
		return $this->db->fetchAll($sql);
	}
	
	public function getActiveCustomer($interval)
	{
		$end_date = date('Y-m-d');
		$start_date = date_create($end_date);
		date_add($start_date, date_interval_create_from_date_string("-".$interval." days"));
		$start_date = date_format($start_date, 'Y-m-d');

		$sql = "select dseg.segment, count(distinct(dh.app_user_id)) as jumlah from 
				(
					SELECT DISTINCT app_user_id, device_reg_id FROM ".$this->mtr."download_history
					where cast(download_time as date) between ".$this->escape($start_date)." and ".$this->escape($end_date)."
				) as dh
				left join ".$this->mtr."device_reg as dr
				on dh.device_reg_id = dr.id
				left join device_segment3 as dseg
				on REPLACE(dr.device_model, 'Samsung ','') = dseg.device_model
				GROUP BY dseg.segment";
		$row = $this->db->fetchAll($sql);
		$data = array();
		$total = 0;
		foreach ($row as $r):
			$segment = ($r->segment == "" )? "Other" : $r->segment;
			$data[$segment] = $r->jumlah;
			$total += $r->jumlah;
		endforeach;
		$data[""] = $total;
		return $data;
	}
	
	public function getRegisteredCustomer()
	{
		$sql = $this->db->select()
						->from(array("mu" => $this->mtr."app_user"), array("Jumlah" => "COUNT(mu.id)"))
						->joinLeft(array("mdr" => $this->mtr."device_reg"), "mu.id = mdr.app_user_id", array())
						->joinLeft(array("dseg" => "device_segment3"), "REPLACE(mdr.device_model, 'Samsung ','') = dseg.device_model", array("segment"))
						->group("dseg.segment");
		$row = $this->db->fetchAll($sql);
		$data = array();
		$total = 0;
		foreach ($row as $r):
			$segment = ($r->segment == "" )? "Other" : $r->segment;
			$data[$segment] = $r->Jumlah;
			$total += $r->Jumlah;
		endforeach;
		$data[""] = $total;
		return $data;
	}
	
	public function apiCustomer($device_id, $type = "enabled") {
		try{
			$this->db->beginTransaction();
			$dataToEdit = array( "status" => $type );
			$this->db->update("device_reg", $dataToEdit, "device_identifier = ".$this->escape($device_id));
			$this->db->commit();
		} catch(Zend_Exception $e) {
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
	}
	
	public function getHistoryDevice($userid)
	{
		$sql = $this->db->select()
						->from($this->mtr."device_reg", array("device_model", "device_identifier", "status", "os_version"))
						->where("app_user_id = ?", $this->validate($userid));
		return $this->db->fetchAll($sql);
	}
	
	public function getHistoryDownload($userid)
	{
		$sql = $this->db->select()
						->from(array("dh" => $this->mtr."download_history"), array("id", "product_id", "download_time" => "TO_CHAR(download_time, 'yyyy-MM-DD HH:MI:SS')"))
						->joinLeft(array("p" => $this->mtr."product"), "dh.product_id = p.id", array("name","release_date" => "TO_CHAR(p.release_date, 'yyyy-MM-DD HH:MI')", "price"))
						->joinLeft(array("p2" => $this->mtr."product"), "p2.id = p.parent_product_id", array())
						->joinLeft(array("pu" => $this->mtr."publisher"), "pu.id = p2.publisher_id", array("author" => "name"))
						->where("dh.app_user_id = ?", $this->validate($userid));
		return $this->db->fetchAll($sql);
	}
	
	public function getCustomerSlime($search, $dataToSearch, $offset = 0, $set = 100)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		$advanced = ($temp <= 0) ? "( name ~* ".$this->escape($search)." OR email ~* ".$this->escape($search)."
				  OR gender ~* ".$this->escape($search)." OR interests ~* ".$this->escape($search)." OR status ~* ".$this->escape($search).")" 
				  : 
				  "name ~* ".$this->escape($dataToSearch['name'])." AND email ~* ".$this->escape($dataToSearch['email'])."
				  AND gender ~* ".$this->escape($dataToSearch['gender'])." AND interests ~* ".$this->escape($dataToSearch['interests'])."
				  AND status ~* ".$this->escape($dataToSearch['status']);
				  
		if ($dataToSearch['start_dob'] != "" && $dataToSearch['end_dob'] != "") {
			$advanced .= " AND date_of_birth BETWEEN ".$this->escape($dataToSearch['start_dob'])." AND ".$this->escape($dataToSearch['end_dob']);
		}
		
		if ($dataToSearch['start_registered'] != "" && $dataToSearch['end_registered'] != "") {
			$advanced .= " AND creation_time BETWEEN ".$this->escape($dataToSearch['start_registered'])." AND ".$this->escape($dataToSearch['end_registered']);
		}
		
		$limit = ($set != "") ? "LIMIT ".$set." OFFSET ".$offset : ""; 
		$sql = $this->db->select()
						->from(array("au" => $this->mtr."app_user"), array("id", "name", "email", "gender", "date_of_birth", "interests", "creation_time" => "TO_CHAR(creation_time, 'yyyy-MM-DD HH:MI')", "status"))
						->where($advanced);
		$sql .= $limit;
		$row = $this->db->fetchAll($sql);
		$userList = array();
		foreach($row as $r) :
			array_push($userList, $this->escape($r->id));
		endforeach;
		
		$device = $this->getCustomerDevices();
		$download = $this->getCustomerDownload($userList);
		$trans = $this->getCustomerTransaction($userList);
		$data = array();
		foreach($row as $r) :
			$r->device = $device[$r->id]->Jumlah;
			$r->download = $download[$r->id]->Jumlah;
			$r->subscription = $trans[$r->id]->Jumlah;
			$r->transaction = $trans[$r->id]->Total;
			array_push($data, $r);
		endforeach;
		return $data;
	}
	
	public function getCustomerSlimeCount($search, $dataToSearch) 
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		$advanced = ($temp <= 0) ? "( name ~* ".$this->escape($search)." OR email ~* ".$this->escape($search)."
				  OR gender ~* ".$this->escape($search)." OR interests ~* ".$this->escape($search)." OR status ~* ".$this->escape($search).")" 
				  : 
				  "name ~* ".$this->escape($dataToSearch['name'])." AND email ~* ".$this->escape($dataToSearch['email'])."
				  AND gender ~* ".$this->escape($dataToSearch['gender'])." AND interests ~* ".$this->escape($dataToSearch['interests'])."
				  AND status ~* ".$this->escape($dataToSearch['status']);
				  
		if ($dataToSearch['start_dob'] != "" && $dataToSearch['end_dob'] != "") {
			$advanced .= " AND date_of_birth BETWEEN ".$this->escape($dataToSearch['start_dob'])." AND ".$this->escape($dataToSearch['end_dob']);
		}
		
		if ($dataToSearch['start_registered'] != "" && $dataToSearch['end_registered'] != "") {
			$advanced .= " AND creation_time BETWEEN ".$this->escape($dataToSearch['start_registered'])." AND ".$this->escape($dataToSearch['end_registered']);
		}
		$sql = $this->db->select()
						->from(array("au" => $this->mtr."app_user"), array("Jumlah" => "COUNT(*)"))
						->where($advanced);
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function getCustomerDownload($userList)
	{
		$require_in = (sizeof($userList) > 0) ? "app_user_id IN (".implode(",", $userList).")" : "app_user_id::text ~* ''";
		$sql = $this->db->select()
						->from($this->mtr."download_history", array("app_user_id", "Jumlah" => "COUNT(*)"))
						->where($require_in)
						->group("app_user_id");
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->app_user_id] = $r;
		endforeach;
		return $data;
	}
	
	public function getCustomerDevices()
	{
		$sql = $this->db->select()
						->from($this->mtr."device_reg", array("app_user_id", "Jumlah" => "COUNT(*)"))
						->group("app_user_id");
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->app_user_id] = $r;
		endforeach;
		return $data;
	}
	
	public function getCustomerTransaction($userList)
	{
		$require_in = (sizeof($userList) > 0) ? "app_user_id IN (".implode(",", $userList).")" : "app_user_id::text ~* ''";
		$sql = $this->db->select()
						->from($this->mtr."subscription_order", array("app_user_id", "Jumlah" => "COUNT(mtr_subscription_order)", "Total" => "SUM(order_total)" ))
						->where($require_in)
						->group("app_user_id");
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->app_user_id] = $r;
		endforeach;
		return $data;
	}
	
	public function getContentDownload($product_id)
	{
		$sql = $this->db->select()
						->from(array("dh" => $this->mtr."download_history"), array("id", "download_time" => "TO_CHAR(download_time, 'yyyy-MM-DD HH:MI:SS')", "download_url"))
						->joinLeft(array("au" => $this->mtr."app_user"), "dh.app_user_id = au.id", array("name"))
						->joinLeft(array("dr" => $this->mtr."device_reg"), "dr.id = dh.device_reg_id", array("device_model", "device_identifier"))
						->where("dh.product_id = ?", $this->validate($product_id));
		return $this->db->fetchAll($sql);
	}
	
	public function getContentDetail($id)
	{
		//"freq" => "CONCAT(p1.publication_release_frequency,' ',p1.publication_release_frequency_unit)"
		$sql = $this->db->select()
						->from(array("p1" => $this->mtr."product"), array("id", "name", "release_date", "price", "page_count", "synopsis", "doc_type", "status"))
						->joinLeft(array("p2" => $this->mtr."product"), "p1.parent_product_id = p2.id" , array("group" => "name"))
						->joinLeft(array("pc" => $this->mtr."product_category"), "pc.product_id = p2.id", array("category_id", "product_id"))
						->joinLeft(array("c" => $this->mtr."category"), "c.id = pc.category_id", array("category" => "name"))
						->joinLeft(array("pu" => $this->mtr."publisher"), "p2.publisher_id = pu.id", array("author" => "name"))
						->joinLeft(array("s" => $this->mtr."static_file"), "p1.cover_static_file_id = s.id", array("cover" => "name"))
						->where("p1.id = ?", $id);
		
		return $this->db->fetchRow($sql);		
	}
	
	public function getContentsFragment($search, $dataToSearch, $offset = 0, $set = 100, $start_date = "", $end_date = "", $publisher = "")
	{
		$where_pub = ($publisher != "") ? " AND pu.name = ".$this->escape($publisher) : ""; 
		$where = (sizeof($dataToSearch) <= 0) ? "(p1.name ~* ".$this->escape($search)." OR p1.author ~* ".$this->escape($search)." OR p1.release_date::text ~* ".$this->escape($search)."
				  OR p1.price::text ~* ".$this->escape($search)." OR p2.name ~* ".$this->escape($search)." OR c.name ~* ".$this->escape($search).")" 
				  : 
				  "p1.name ~* ".$this->escape($dataToSearch['product_name'])." AND p1.author ~* ".$this->escape($dataToSearch['publisher'])."
				  AND p1.price::text ~* ".$this->escape($dataToSearch['price'])." AND p2.name ~* ".$this->escape($dataToSearch['group'])."
				  AND COALESCE(c.name,'') ~* ".$this->escape($dataToSearch['category']);
				  
		if ($start_date != "" && $end_date != "") {
			$where .= " AND p1.release_date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		$where .= $where_pub;
		
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		$sql = $this->db->select()
						->from(array("p1" => $this->mtr."product"), array("id", "name", "release_date", "price"))
						->joinLeft(array("p2" => $this->mtr."product"), "p1.parent_product_id = p2.id" , array("group" => "name"))
						->joinLeft(array("pc" => $this->mtr."product_category"), "pc.product_id = p2.id", array("category_id", "product_id"))
						->joinLeft(array("c" => $this->mtr."category"), "c.id = pc.category_id", array("category" => "name"))
						->join(array("pu" => $this->mtr."publisher"), "p2.publisher_id = pu.id", array("author" => "name"))
						->where("upper(p1.type) = ?", "SINGLE")
						->where($where)
						->order("name ASC");

		$sql .= $limit;
		$fragment = $this->getDownloadCount();
		$trans  = $this->getContentTransaction();
		
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$r->download = $fragment[$r->id];
			$r->trans = $trans[$r->id];
			array_push($data, $r);
		endforeach;
		
		return $data;
	}
	
	public function getDownloadCount()
	{
		$sql = $this->db->select()
						->from($this->mtr."download_history", array("product_id", "Jumlah" => "COUNT(id)"))
						->group("product_id");
		$row =  $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->product_id] = $r->Jumlah;
		endforeach;
		return $data;
	}
	
	public function getContentTransaction()
	{
		$sql = $this->db->select()
						->from(array("so" => $this->mtr."subscription_order"), array("Jumlah" => "COUNT(DISTINCT so.order_id)"))
						->join(array("sod" => $this->mtr."subscription_order_detail"), "so.order_id = sod.order_id", array("product_id"))
						->where("sod.product_id IS NOT NULL")
						->group("sod.product_id");
		
		$row =  $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->product_id] = $r->Jumlah;
		endforeach;
		return $data;				
	}
	
	public function getContentCount($search, $dataToSearch, $start_date = "", $end_date = "", $publisher = "")
	{
		$where_pub = ($publisher != "") ? " AND pu.name = ".$this->escape($publisher) : ""; 
		$where = (sizeof($dataToSearch) <= 0) ? "(p1.name ~* ".$this->escape($search)." OR pu.name ~* ".$this->escape($search)." OR p1.release_date::text ~* ".$this->escape($search)."
				  OR p1.price::text ~* ".$this->escape($search)." OR p2.name ~* ".$this->escape($search)." OR c.name ~* ".$this->escape($search).")" 
				  : 
				  "p1.name ~* ".$this->escape($dataToSearch['product_name'])." AND pu.name ~* ".$this->escape($dataToSearch['publisher'])."
				  AND p1.price::text ~* ".$this->escape($dataToSearch['price'])." AND p2.name ~* ".$this->escape($dataToSearch['group'])."
				  AND COALESCE(c.name,'') ~* ".$this->escape($dataToSearch['category']);
				  
		if ($start_date != "" && $end_date != "") {
			$where .= " AND p1.release_date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		$where .= $where_pub;
		$sql = $this->db->select()
						->from(array("p1" => $this->mtr."product"), array("Jumlah" => "COUNT(*)"))
						->joinLeft(array("p2" => $this->mtr."product"), "p1.parent_product_id = p2.id" , array())
						->joinLeft(array("pc" => $this->mtr."product_category"), "pc.product_id = p2.id", array())
						->joinLeft(array("c" => $this->mtr."category"), "c.id = pc.category_id", array())
						->join(array("pu" => $this->mtr."publisher"), "p2.publisher_id = pu.id", array())
						->where("upper(p1.type) = ?", "SINGLE")
						->where($where);
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function getCustomerToday($search, $dataToSearch)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "name ~* ".$this->escape($dataToSearch['name'])." AND
								 email ~* ".$this->escape($dataToSearch['email'])."AND
								 device_identifier ~* ".$this->escape($dataToSearch['imei'])." AND
								 c.device_model ~* ".$this->escape($dataToSearch['device_model'])." AND 
								 ('Slime') ~* ".$this->escape($dataToSearch['app'])." AND
								 status ~* ".$this->escape($dataToSearch['status'])
								 : 
								"name ~* ".$this->escape($search)." OR 
								 email ~* ".$this->escape($search)." OR 
								 device_identifier ~* ".$this->escape($search)." OR 
								 c.device_model ~* ".$this->escape($search)." OR 
								 ('Slime') ~* ".$this->escape($search)." OR 
								 status ~* ".$this->escape($search);
								 
		$sql = $this->db->select()
						->from(array("c" => "customer_slime_today"), array(
															"customer_id" => "('-')",
															"name" => "name",
															"email" => "email",
															"imei" => "device_identifier",
															"type" => "identifier_type",
															"device_model" => "device_model",
															"app" => "('Slime')",
															"status" => "( CASE WHEN LOWER(status) = 'enabled' THEN 'active' ELSE 'suspended' END )",
															"first_download_date" => "TO_CHAR(first_download::date, 'YYYY-MM-DD HH24:MI')",
															"last_download_date" => "TO_CHAR(last_download::date, 'YYYY-MM-DD HH24:MI')"))
						->joinLeft(array("dp" => "device_product"), "c.device_model ~* dp.code", array("product_name"))
						->joinLeft(array("md" => "msdevice_price"), "md.device_model ~* c.device_model", array("price" => "price"))
						->where("email <> ''")
						->where($advanced);
		$table = $this->db->fetchAll($sql);
		$data = array();
		foreach ($table as $row) : 
			$data[$row->imei] = $row;
		endforeach;
		return $data;
	}
	
	/* create by kovan for favourite download_time */
    public function getFavouriteAll($search, $dataToSearch, $offset = 0, $set = 100, $publisher = "", $category = "")
    {
		$where_cate = ($category != "") ? " AND p.parent_product_id in (
															SELECT pp.id FROM 
															(
																SELECT id FROM product WHERE type = 'MULTIPLE'
															) AS pp JOIN product_category AS pc ON pp.id = pc.product_id
															JOIN category AS c ON c.id = pc.category_id
															WHERE c.parent_id = ".$this->escape($category)."
														)" : "";
		$where_pub = ($publisher != "") ? " AND p.id in
											(
												select p.id from product as p
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
        $where=array();
		$temp=0;
		$advanced="";
		foreach ($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where,$key ." ~* ". $this->escape($value));
				$temp++;
			}
		}
		$advanced = ($temp>0 ) ? " WHERE (".implode(" AND ", $where).")" : " WHERE (p.name ~* '".$search."' or pb.name ~* '".$search."')";
        
		$advanced .= $where_pub;
		$advanced .= $where_cate;
		
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		
		
		$sql = $this->db->select()
						->from(array("dh" => $this->mtr."download_history"), array("title_name" => "p.name", "jumlah" => "COUNT(dh.product_id)", 
									 "publisher_name" => "pb.name"))
						->joinLeft(array("p" => $this->mtr."product"), "dh.product_id=p.id", array())
						->joinLeft(array("pd" => $this->mtr."product"), "p.parent_product_id=pd.id", array())
						->joinLeft(array("pb" => $this->mtr."publisher"), "pd.publisher_id=pb.id", array());
		$sql .= $advanced;
		$sql .= "group by p.name,pb.name
				 order by p.name asc";
		$sql .= $limit;
        return $this->db->fetchAll($sql);
    }
	
	public function getFavouriteCount($search, $dataToSearch, $publisher = "", $category = "")
    {
		$where_cate = ($category != "") ? " AND p.parent_product_id in (
															SELECT pp.id FROM 
															(
																SELECT id FROM product WHERE type = 'MULTIPLE'
															) AS pp JOIN product_category AS pc ON pp.id = pc.product_id
															JOIN category AS c ON c.id = pc.category_id
															WHERE c.parent_id = ".$this->escape($category)."
														)" : "";
		$where_pub = ($publisher != "") ? " AND p.id in
											(
												select p.id from product as p
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
        $where=array();
		$temp=0;
		$advanced="";
		foreach ($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where,$key ." ~* ". $this->escape($value));
				$temp++;
			}
		}
		$advanced = ($temp>0 ) ? " WHERE (".implode(" AND ", $where).")" : " WHERE (p.name ~* '".$search."' or pb.name ~* '".$search."')";
        $advanced .= $where_pub;
		$advanced .= $where_cate;
		
		$sql = $this->db->select()
				->from(array("dh" => $this->mtr."download_history"), array())
				->joinLeft(array("p" => $this->mtr."product"), "dh.product_id=p.id", array("Jumlah" => "COUNT(DISTINCT CONCAT(p.name, pb.name))"))
				->joinLeft(array("pd" => $this->mtr."product"), "p.parent_product_id=pd.id", array())
				->joinLeft(array("pb" => $this->mtr."publisher"), "pd.publisher_id=pb.id", array());
		
		$sql .= $advanced;
        $row = $this->db->fetchRow($sql);
		return $row->Jumlah;
    }
    
	public function getDetailFavourite($search)
    {
		$sql = $this->db->select()
						->from(array("dh" => $this->mtr."download_history"), array("product_id", "jumlah" => "COUNT(dh.product_id)"))
						->joinLeft(array("p" => $this->mtr."product"), "(dh.product_id=p.id)", array("title_name" => "name", "release_date"))
						->where("p.type = ?", "SINGLE")
						->where("p.name = ?", $this->validate($search))
						->group(array("dh.product_id", "p.name", "release_date"));
        return $this->db->fetchAll($sql);
    }
    
    /* end of favourite download */
	public function getCustomerTodayCount($search, $dataToSearch)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "name ~* ".$this->escape($dataToSearch['name'])." AND
								 email ~* ".$this->escape($dataToSearch['email'])."AND
								 device_identifier ~* ".$this->escape($dataToSearch['imei'])." AND
								 device_model ~* ".$this->escape($dataToSearch['device_model'])." AND 
								 ('Slime') ~* ".$this->escape($dataToSearch['app'])." AND
								 status ~* ".$this->escape($dataToSearch['status'])
								 : 
								"name ~* ".$this->escape($search)." OR 
								 email ~* ".$this->escape($search)." OR 
								 device_identifier ~* ".$this->escape($search)." OR 
								 device_model ~* ".$this->escape($search)." OR 
								 ('Slime') ~* ".$this->escape($search)." OR 
								 status ~* ".$this->escape($search);
								 
		$sql = $this->db->select()
						->from("customer_slime_today", array("Jumlah" => "COUNT(email)"))
						->where($advanced);
						
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	// Custom Function --
	public function searchAllSlime($search, $dataToSearch, $offset = 0, $set = 25, $start_date = "", $end_date = "")
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (so.order_id::text ~* '".$search."' or email ~* '".$search."' or au.email ~* '".$search."' or p.name ~* '".$search."' or TO_CHAR(so.order_date,'DD Mon YYYY') ~* '".$search."' or 
				device_model ~* '".$search."' or so.device_identifier ~* '".$search."' or TO_CHAR(callback_handler_time,'DD Mon YYYY') ~* '".$search."' or
				p.name ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND order_date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".$offset;
		
		
		$sql = "SELECT DISTINCT order_date, so.user_id, so.order_id, email, payment_type, item_name AS period, order_total, so.status, so.device_identifier, so.device_identifier_type, callback_handler_time AS payment_date, transaction_id AS payment_id, device_model, p.name AS pname
				, dm.product_name  FROM 
				( 
					SELECT order_id, user_id, device_identifier, device_identifier_type, order_date, status, order_total
					, callback_handler_time, transaction_id, payment_type FROM 
					subscription_order
				) AS so
				LEFT OUTER JOIN app_user au ON ( so.user_id = au.id ) 
				LEFT OUTER JOIN subscription_order_detail sod ON ( so.order_id = sod.order_id ) 
				LEFT OUTER JOIN app_session ass ON ( so.device_identifier = ass.device_identifier ) 
				LEFT OUTER JOIN subscription s ON ( sod.item_id = s.publisher_id ) 
				LEFT OUTER JOIN publisher p ON ( s.publisher_id = p.id )
				LEFT OUTER JOIN device_product AS dm ON ass.device_model ~* dm.code				
				".$advanced." 
				".$limit;
		
		//echo $sql;
		return $this->db->fetchAll($sql);
	}
	
	// ---------------------------------------------------------------------------------
	// NEW SLIME 
	// ---------------------------------------------------------------------------------
	
	public function getSubscriptionOrder($search, $dataToSearch, $offset = 0, $set = 100, $publisher = "")
	{
		$where_pub = ($publisher != "") ? " AND order_id in
											(
												select order_id from subscription_order_detail as sod
												join product as p on sod.product_id = p.id
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
        $where=array();
        $temp=0;
        foreach($dataToSearch as $key =>$value)
        {
            if($value!="")
            {
                array_push($where,($key." ~* ".$this->escape($value)));
                $temp++;
            }
        }
        $advanced= ($temp>0) ? " WHERE (order_id::text ~* ".$this->escape($dataToSearch['order_id'])." 
								AND au.name ~* ".$this->escape($dataToSearch['name'])."
								AND au.email ~* ".$this->escape($dataToSearch['email'])."
								AND COALESCE(transaction_id,'') ~* ".$this->escape($dataToSearch['transaction_id'])."
								AND COALESCE(payment_type,'') ~* ".$this->escape($dataToSearch['payment_type'])." 
								AND so.status ~* ".$this->escape($dataToSearch['status']).")"
								: 
								" WHERE (cast(order_id as text) ~* '".$search."' or email ~* '".$search."' or cast(order_total as text) ~* '".$search."' or transaction_id ~* '".$search."' or payment_type ~* '".$search."' or so.status ~* '".$search."' or name ~* '".$search."')";
		if ($dataToSearch['pid'] != "") {
			$advanced .= " AND order_id IN (SELECT order_id FROM ".$this->mtr."subscription_order_detail WHERE product_id = ".$this->escape($dataToSearch['pid']).")"; 
		}
		
		if ($dataToSearch['pname'] != "") {
			$advanced .= " AND order_id IN (SELECT order_id FROM ".$this->mtr."subscription_order_detail WHERE product_name ~* ".$this->escape($dataToSearch['pname']).")";
		}
		
		if ($dataToSearch['start_order'] != "" && $dataToSearch['end_order'] != "") {
			$advanced .= " AND order_date BETWEEN ".$this->escape($dataToSearch['start_order'])." AND ".$this->escape($dataToSearch['end_order']);
		}
		
		$advanced .= $where_pub;
		
		$limit = ($set != "") ? " OFFSET ".$offset." LIMIT ".$set : "";
		$sql = $this->db->select()
						->from(array("so" => $this->mtr."subscription_order"), array("order_id", "order_date" => "TO_CHAR(order_date, 'YYYY-MM-DD HH:MI:SS')", "order_total", "transaction_id", "payment_type", "status","callback_handler_content"))
						->join(array("au" => $this->mtr."app_user"), "so.app_user_id=au.id", array("name", "email"));
		$sql .= $advanced;
		$sql .= $limit;
		return $this->db->fetchAll($sql);
    }
	
	public function getSubscriptionCount($search, $dataToSearch, $publisher = "")
	{
		$where_pub = ($publisher != "") ? " AND order_id in
											(
												select order_id from subscription_order_detail as sod
												join product as p on sod.product_id = p.id
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
		$where=array();
        $temp=0;
        foreach($dataToSearch as $key =>$value)
        {
            if($value!="")
            {
                array_push($where,($key." ~* ".$this->escape($value)));
                $temp++;
            }
        }
         $advanced= ($temp>0) ? " WHERE (order_id::text ~* ".$this->escape($dataToSearch['order_id'])." 
								AND au.name ~* ".$this->escape($dataToSearch['name'])."
								AND au.email ~* ".$this->escape($dataToSearch['email'])."
								AND COALESCE(transaction_id,'') ~* ".$this->escape($dataToSearch['transaction_id'])."
								AND COALESCE(payment_type,'') ~* ".$this->escape($dataToSearch['payment_type'])." 
								AND so.status ~* ".$this->escape($dataToSearch['status']).")"
								: 
								" WHERE (cast(order_id as text) ~* '".$search."' or email ~* '".$search."' or cast(order_total as text) ~* '".$search."' or transaction_id ~* '".$search."' or payment_type ~* '".$search."' or so.status ~* '".$search."' or name ~* '".$search."')";
		if ($dataToSearch['pid'] != "") {
			$advanced .= " AND order_id IN (SELECT order_id FROM ".$this->mtr."subscription_order_detail WHERE product_id = ".$this->escape($dataToSearch['pid']).")"; 
		}
		
		if ($dataToSearch['pname'] != "") {
			$advanced .= " AND order_id IN (SELECT order_id FROM ".$this->mtr."subscription_order_detail WHERE product_name ~* ".$this->escape($dataToSearch['pname']).")";
		}
		
		if ($dataToSearch['start_order'] != "" && $dataToSearch['end_order'] != "") {
			$advanced .= " AND order_date BETWEEN ".$this->escape($dataToSearch['start_order'])." AND ".$this->escape($dataToSearch['end_order']);
		}
		
		$advanced.=$where_pub;
		
		$sql = $this->db->select()
						->from(array("so" => $this->mtr."subscription_order"), array("Jumlah" => "COUNT(*)"))
						->join(array("au" => $this->mtr."app_user"), "so.app_user_id=au.id", array());
		$sql .= $advanced;
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
    
    public function getDetailOrder($search)
    {
    	$sql = $this->db->select()
    					->from(array("so" => $this->mtr."subscription_order"), array("order_id","callback_handler_time"))
    					->joinLeft(array("sod" => $this->mtr."subscription_order_detail"), "so.order_id=sod.order_id", array("product_id", "product_price","product_name", "order_item_type"))
    					->joinLeft(array("p" => $this->mtr."product"), "sod.product_id=p.id", array("cover_static_file_id", "synopsis", "release_date"))
    					->joinLeft(array("pp" => $this->mtr."product"), "p.parent_product_id=pp.id", array())
    					->joinLeft(array("pu" => $this->mtr."publisher"), "pp.publisher_id = pu.id", array("author" => "name"))
    					->joinLeft(array("s" => $this->mtr."static_file"), "s.id = p.cover_static_file_id", array("cover" => "name"))
    					->where("so.order_id = ?", $search);
         return $this->db->fetchAll($sql);
    }
    
	// ---------------------------------------------------------------------------------
	// ---------------------------------------------------------------------------------
	
	public function getPaymentCount($search, $dataToSearch)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (so.order_id::text ~* '".$search."' or email ~* '".$search."' or au.email ~* '".$search."' or p.name ~* '".$search."' 
				or TO_CHAR(so.order_date, 'DD Mon YYYY') ~* '".$search."' or 
				device_model ~* '".$search."' or so.device_identifier ~* '".$search."' or TO_CHAR(callback_handler_time, 'DD Mon YYYY') ~* '".$search."' or
				p.name ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND order_date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$sql = " SELECT COUNT(DISTINCT(so.order_id)) AS total  FROM ".$this->mtr."subscription_order so
				LEFT OUTER JOIN app_user au ON ( so.user_id = au.id ) 
				LEFT OUTER JOIN subscription_order_detail sod ON ( so.order_id = sod.order_id ) 
				LEFT OUTER JOIN app_session ass ON ( so.device_identifier = ass.device_identifier ) 
				LEFT OUTER JOIN subscription s ON ( sod.item_id = s.publisher_id ) 
				LEFT OUTER JOIN publisher p ON ( s.publisher_id = p.id )
				LEFT OUTER JOIN device_product AS dm ON ass.device_model ~* dm.code				
				".$advanced;
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	
	public function searchDownloadByEmail($email, $offset = 0, $set = 25)
	{
		$limit = ($set == "") ? "" : " LIMIT ".$offset.",".$set;
		
		$sql = "SELECT MIN(cd.first_download_date) AS download_time, cd.email, cd.device_identifier, apu.device_model, apu.device_identifier_type, p.name AS pname, 
				p.author, p.status, dm.product_name 
				FROM (
					SELECT first_download_date, email, device_identifier, publication_id FROM view_download2
					WHERE email = ".$this->escape($email)."
				) AS cd 
				left outer JOIN app_session AS apu ON cd.device_identifier = apu.device_identifier 
				left outer JOIN publication AS p ON cd.publication_id = p.id
				left outer JOIN device_product AS dm ON apu.device_model LIKE CONCAT(CONCAT('%',dm.code),'%')
				GROUP BY cd.email, cd.device_identifier, apu.device_model, apu.device_identifier_type, p.name, 
				p.author, p.status, dm.product_name ";
				
		/*$sql = "SELECT distinct full_date AS download_time, cd.email, cd.device_identifier, apu.device_model, p.name AS pname, p.author, p.status 
				FROM `cs_download` AS cd 
				left outer JOIN app_session AS apu ON cd.device_identifier = apu.device_identifier 
				left outer JOIN publication as p ON cd.publication_id = p.id 
				WHERE cd.email = ".$this->escape($email);*/
				
		return $this->db->fetchAll($sql);
	}
	
	public function getCustomer($q, $arr, $offset, $length){
		$normal_search ="SELECT au.name, au.email, aps.device_identifier AS imei, aps.device_model, 'S Lime' AS app, 
						( CASE
						  WHEN au.status = 'verified' THEN 'active'
						  ELSE 'suspended'
						END) AS status FROM app_user AS au
						 JOIN app_session AS aps ON au.id = aps.id";//test doang
		
		if ($length != "")
		{
			$normal_search.= " LIMIT $offset,$length"; 
		}
		
		return $this->db->fetchAll($normal_search);
	}
	
	public function suspendOrActivateCustomer($device_identifier, $status = "disabled")
	{
		try {
			$this->db->beginTransaction();
			$data = array("status" => $this->validate($status));
		
			$this->db->update("device_identifier", $data, "name = ".$this->escape($device_identifier));
			$this->db->commit();
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
	}
	
	public function changeEmail($device_identifier, $newemail) 
	{
		try {
			$this->db->beginTransaction();
			$sql = "UPDATE app_user SET email = ".$this->escape($newemail)." WHERE id = (
						SELECT first_activation_app_user_id FROM device_identifier
						WHERE name = ".$this->escape($device_identifier)."
					)";
			$this->logToFirebug($sql);
			$this->db->query($sql);
			$this->db->commit();
		} catch (Zend_Exception $e)
		{
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
	}
	
	public function searchCustomer($search, $dataToSearch, $offset = 0, $set = 25)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." LIKE ".$this->escape("%".$value."%")));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : 
					"WHERE (au.name ~* '".$search."' or 
							au.email ~* '".$search."' or 
							asu.device_identifier ~* '".$search."' or 
							asu.device_model ~* '".$search."' or 
							'S Lime' ~* '".$search."' or 
							au.status ~* '".$search."')";
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".floor($offset);
		
		$normal_search ="SELECT au.name, au.email, asu.device_identifier AS imei, asu.device_identifier_type AS type, asu.device_model, 'S Lime' AS app, 
							( CASE
							  WHEN asu.status = 'enabled' THEN 'active'
							  ELSE 'suspended'
							END) AS status, product_name, price, TO_CHAR(cd.first_download_date,'DD Mon YYYY HH24:MI') AS first_download_date, TO_CHAR(cd.last_download_date,'DD Mon YYYY HH24:MI') AS last_download_date
						FROM 
						(
							SELECT id, name, email, status FROM app_user where name ~* 'andy'
						) AS au
						JOIN 
						(
							SELECT aus.app_user_id, di.name AS device_identifier, di.identifier_type AS device_identifier_type, aps2.device_model, di.status 
							FROM (
								SELECT id, name, identifier_type, status 
								FROM device_identifier
							) AS di JOIN 
							(
								SELECT id, device_model, device_identifier_id FROM app_session
							) AS aps2
							ON di.id = aps2.device_identifier_id
							JOIN 
							(
							    SELECT app_session_id, app_user_id FROM app_session_user
							) AS aus
							ON aps2.id = aus.app_session_id
						) AS asu
						ON asu.app_user_id = au.id
						LEFT OUTER JOIN device_product AS dm ON asu.device_model ~* dm.code 
						LEFT OUTER JOIN msdevice_price AS md ON dm.code = md.device_model
						LEFT OUTER JOIN 
						(
							SELECT email, MIN(first_download_date) AS first_download_date, MAX(last_download_date) AS last_download_date FROM view_download2
							GROUP BY email
						) AS cd ON cd.email = au.email
						".$advanced." 
						".$limit;

		return $this->db->fetchAll($normal_search);
	}
	
	public function getCustomerCount($search, $dataToSearch)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." LIKE ".$this->escape("%".$value."%")));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : 
					"WHERE (au.name like '%".$search."%' or 
							au.email like '%".$search."%' or 
							asu.device_identifier like '%".$search."%' or 
							asu.device_model like '%".$search."%' or 
							'S Lime' like '%".$search."%' or 
							au.status like '%".$search."%')";
		
		$sql ="SELECT COUNT(au.name) AS total
						FROM 
						(
							SELECT id, name, email, status FROM app_user where name ~* 'andy'
						) AS au
						JOIN 
						(
							SELECT aus.app_user_id, di.name AS device_identifier, di.identifier_type AS device_identifier_type, aps2.device_model, di.status 
							FROM (
								SELECT id, name, identifier_type, status 
								FROM device_identifier
							) AS di JOIN 
							(
								SELECT id, device_model, device_identifier_id FROM app_session
							) AS aps2
							ON di.id = aps2.device_identifier_id
							JOIN 
							(
							    SELECT app_session_id, app_user_id FROM app_session_user
							) AS aus
							ON aps2.id = aus.app_session_id
						) AS asu
						ON asu.app_user_id = au.id
						".$advanced;
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	
	public function getMoreReview($search, $dataToSearch, $offset = 0, $set = 25, $start_date = "", $end_date = ""){
		$where = array();
		$advanced = "";
		$temp = 0;
		
		foreach ($dataToSearch as $key => $value) : 
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		endforeach;
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : 
					"WHERE (name ~* '".$search."' or 
							email ~* '".$search."' or 
							subject ~* '".$search."' or 
							feedback ~* '".$search."' or 
							TO_CHAR(feedback_time, 'YYYY-MM-DD') ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND feedback_time BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
							
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".floor($offset);
		
		$sql = "SELECT id, name, email, subject, feedback, TO_CHAR(feedback_time, 'DD Mon YYYY HH:MI') AS feedback_time FROM slime_feedback ".
				$advanced."
				ORDER BY feedback_time DESC ".
				$limit;
		
		$this->logToFirebug($sql);
		return $this->db->fetchAll($sql);
	}
	
	public function getReviewCount($search, $dataToSearch, $start_date = "", $end_date = "")
	{
		$where = array();
		$advanced = "";
		$temp = 0;
		
		foreach ($dataToSearch as $key => $value) : 
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		endforeach;
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : 
					"WHERE (name ~* '".$search."' or 
							email ~* '".$search."' or 
							subject ~* '".$search."' or 
							feedback ~* '".$search."' or 
							TO_CHAR(feedback_time, 'DD Mon YYYY HH:MI') ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND feedback_time BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$sql = "SELECT COUNT(id) AS total FROM slime_feedback ".
				$advanced;
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	
	public function getAllReview(){
		$sql = $this->db->select()
						->from("slime_feedback", array("id" => "id",
																 "name" => "name",
																 "email" => "email",
																 "subject" => "subject",
																 "feedback" => "feedback", 
																 "feedback_time" => "TO_CHAR(feedback_time, 'DD Mon YYYY HH:MI')"))
						->order("feedback_time DESC");
		return $this->db->fetchAll($sql);
	}
}