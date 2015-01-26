<?php
/*
	Created By Calvin Windoro
  	Date : 9 September 2014
*/
require_once "Db/Db_PDO.php";

class Db_Pg_Sgift extends Db_PDO
{
	var $db;
	var $mtr = "mtr2_";
	static $SEG_NEW_ENTRY = 1, $SEG_PREMIUM = 2, $SEG_TAB = 3;
	static $ACTIVE_MONTHLY = "monthly", $ACTIVE_WEEKLY = "weekly", $ACTIVE_DAILY = "daily";
	static $CLAIM_OFFLINE = "offline", $CLAIM_ONLINE = "online";
	public function __construct($dbconfig)
	{
		$this->primary_column = "";
		$this->table = "";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// --------------------------------------------------------------------------------------
	// EXPORT ONLINE AND OFFLINE CLAIM
	// --------------------------------------------------------------------------------------
	
	public function getOfflineClaim($offset, $set = "")
	{
		$sql = $this->db->select()
						->from(array("cc" => $this->mtr."campaign_claim"), array("promo_id" => "CONCAT (channel_id,'-', cr.provider_id,'-', cc.offer_id,'-', cc.batch_id,'-', campaign_id)", "claim_time", "redeem_code"))
						->joinLeft(array("pm" => $this->mtr."provider_merchant"), "cc.merchant_id=pm.merchant_id", array("merchant_name" => "name"))
						->joinLeft(array("pot" => $this->mtr."provider_outlet"), "cc.outlet_id=pot.outlet_id", array("outlet_name" => "name"))
						->join(array("cr" =>  $this->mtr."campaign_redeem"), "cc.redeem_code=cr.redeem_code AND cc.provider_id=cr.provider_id AND cc.offer_id=cr.offer_id AND cc.batch_id=cr.batch_id", array("provider_id", "redeem_time"))
						->joinLeft(array("gu" => $this->mtr."ggi_user"), "cr.user_id=gu.id", array("customer_name" => "name"));
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		$sql .= $limit;
		return $this->db->fetchAll($sql);
	}
	
	public function getOnlineClaim($offset, $set = "")
	{
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		$sql = "SELECT 
					CONCAT (channel_id,'-', cr.provider_id,'-', iocr.offer_id,'-', iocr.batch_id,'-', campaign_id) as promo_id, 
					pm.name AS merchant_name, 
					outlet_name, 
					gu.name AS customer_name, 
					iocr.redeem_code, 
					redeem_time, 
					status_date, 
					status_description
				FROM
				(
					SELECT provider_id, offer_id, batch_id, outlet_name, redeem_code, status_date, status_description
					FROM ".$this->mtr."intouch_offer_claim_report
					WHERE outlet_name IS NOT NULL
					".$limit."
				)AS iocr
				LEFT OUTER JOIN ".$this->mtr."campaign_redeem cr ON iocr.redeem_code=cr.redeem_code AND iocr.provider_id=cr.provider_id AND iocr.offer_id=cr.offer_id AND iocr.batch_id=cr.batch_id
				LEFT OUTER JOIN ".$this->mtr."provider_offer po ON iocr.provider_id=po.provider_id AND iocr.offer_id=po.offer_id AND iocr.batch_id=po.batch_id
				LEFT OUTER JOIN ".$this->mtr."provider_merchant pm ON po.merchant_id=pm.merchant_id
				LEFT OUTER JOIN ".$this->mtr."ggi_user gu ON cr.user_id=gu.id";
		return $this->db->fetchAll($sql);
	}
	
	// --------------------------------------------------------------------------------------
	// Customer GGI
	// --------------------------------------------------------------------------------------
	
	public function getCustomerSgiftDevice($search, $dataToSearch, $offset = 0, $set = 100)
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
		$advanced = ($temp <= 0) ? " WHERE (name ~* ".$this->escape($search)." or marital_status ~* ".$this->escape($search)." 
									or gender ~* ".$this->escape($search)." or marital_status ~* ".$this->escape($search)." 
									or holiday ~* ".$this->escape($search)." or address_province ~* ".$this->escape($search)." 
									or id in (
										select user_id from ".$this->mtr."ggi_user_phone where phone_number ~* ".$this->escape($search)."
									) or id in (
										select user_id from ".$this->mtr."ggi_user_email where email ~* ".$this->escape($search)."
									) or id in (
										select user_id from ".$this->mtr."ggi_app_session_user as mgasu 
										left join ".$this->mtr."device_identifier AS mdi on mgasu.device_identifier_id = mdi.id
										where concat(manufactur,' ',model) ~* ".$this->escape($search)." or mdi.name ~* ".$this->escape($search)."
									))" 
								: 
								" WHERE (name ~* ".$this->escape($dataToSearch['name'])." AND marital_status ~* ".$this->escape($dataToSearch['marital_status'])." 
									AND gender ~* ".$this->escape($dataToSearch['gender'])."
									AND holiday ~* ".$this->escape($dataToSearch['holiday'])." AND address_province ~* ".$this->escape($dataToSearch['address'])." 
									AND id in (
										select user_id from ".$this->mtr."ggi_user_phone where phone_number ~* ".$this->escape($dataToSearch['phone'])."
									) AND id in (
										select user_id from ".$this->mtr."ggi_user_email where email ~* ".$this->escape($dataToSearch['email'])."
									) )";
		
		if ($dataToSearch['device_model'] != "" || $dataToSearch['imei'] != "") {
			$advanced .= " AND id in (
										select user_id from ".$this->mtr."ggi_app_session_user as mgasu 
										left join ".$this->mtr."device_identifier AS mdi on mgasu.device_identifier_id = mdi.id
										where concat(manufactur,' ',model) ~* ".$this->escape($dataToSearch['device_model'])." AND mdi.name ~* ".$this->escape($dataToSearch['imei'])."
									)";
		}
			
		if ($dataToSearch['start_dob'] != "" && $dataToSearch['end_dob'] != "") {
			$advanced .= " AND date_of_birth BETWEEN ".$this->escape($dataToSearch['start_dob'])." AND ".$this->escape($dataToSearch['end_dob']);
		}
		
		if ($dataToSearch['start_registered'] != "" && $dataToSearch['end_registered'] != "") {
			$advanced .= " AND creation_time BETWEEN ".$this->escape($dataToSearch['start_registered'])." AND ".$this->escape($dataToSearch['end_registered']);
		}
		
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : ""; 
		$sql = "SELECT 
				 gu.id AS user_id, 
				 gu.name,
				 phone_number,
				 email,
				 gu.creation_time, 
				 date_of_birth, 
				 gender, 
				 marital_status, 
				 marital_kid_count, 
				 holiday,
				 address_province,
				 dc.name AS channel,
				 CONCAT (manufactur,' ', di.model) AS device_model,
				 di.name AS imei,
				 type,
				 (SELECT max(redeem_time) FROM campaign_redeem WHERE user_id=gu.id) AS last_activity
				FROM
				(
					select id, name, creation_time, date_of_birth, gender, marital_status, marital_kid_count, holiday, address, address_province
					FROM ".$this->mtr."ggi_user
					".$advanced."
					".$limit."
				)AS gu
				JOIN ".$this->mtr."ggi_app_session_user gasu ON gu.id=gasu.user_id
				LEFT OUTER JOIN ".$this->mtr."device_identifier AS di ON gasu.device_identifier_id = di.id
				LEFT OUTER JOIN ".$this->mtr."ggi_user_email AS gue ON gu.id = gue.user_id
				LEFT OUTER JOIN ".$this->mtr."ggi_user_phone gup ON gu.id=gup.user_id
				LEFT OUTER JOIN ".$this->mtr."ggi_app_session gas ON di.id=gas.device_identifier_id
				LEFT OUTER JOIN distribution_channel dc ON gas.channel_id=dc.id";
	
		$row = $this->db->fetchAll($sql);
		$device = $this->getDeviceFragment();
		$data = array();
		foreach($row as $r) :
			$code = trim(str_replace("SAMSUNG", "", strtoupper($r->device_model)));
			$r->device_name = $device[$code]->product_name;
			array_push($data, $r);
		endforeach;
		return $data;
	}
	
	public function getCustomerSgift($search, $dataToSearch, $offset = 0, $set = 100)
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
		$advanced = ($temp <= 0) ? " WHERE (name ~* ".$this->escape($search)." or marital_status ~* ".$this->escape($search)." 
									or gender ~* ".$this->escape($search)." or marital_status ~* ".$this->escape($search)." 
									or holiday ~* ".$this->escape($search)." or address_province ~* ".$this->escape($search)." 
									or id in (
										select user_id from ".$this->mtr."ggi_user_phone where phone_number ~* ".$this->escape($search)."
									) or id in (
										select user_id from ".$this->mtr."ggi_user_email where email ~* ".$this->escape($search)."
									) or id in (
										select user_id from ".$this->mtr."ggi_app_session_user as mgasu 
										left join ".$this->mtr."device_identifier AS mdi on mgasu.device_identifier_id = mdi.id
										where concat(manufactur,' ',model) ~* ".$this->escape($search)." or mdi.name ~* ".$this->escape($search)."
									))" 
								: 
								" WHERE (name ~* ".$this->escape($dataToSearch['name'])." AND marital_status ~* ".$this->escape($dataToSearch['marital_status'])." 
									AND gender ~* ".$this->escape($dataToSearch['gender'])."
									AND holiday ~* ".$this->escape($dataToSearch['holiday'])." AND address_province ~* ".$this->escape($dataToSearch['address'])." 
									AND id in (
										select user_id from ".$this->mtr."ggi_user_phone where phone_number ~* ".$this->escape($dataToSearch['phone'])."
									) AND id in (
										select user_id from ".$this->mtr."ggi_user_email where email ~* ".$this->escape($dataToSearch['email'])."
									) )";
		
		if ($dataToSearch['device_model'] != "" || $dataToSearch['imei'] != "") {
			$advanced .= " AND id in (
										select user_id from ".$this->mtr."ggi_app_session_user as mgasu 
										left join ".$this->mtr."device_identifier AS mdi on mgasu.device_identifier_id = mdi.id
										where concat(manufactur,' ',model) ~* ".$this->escape($dataToSearch['device_model'])." AND mdi.name ~* ".$this->escape($dataToSearch['imei'])."
									)";
		}
			
		if ($dataToSearch['start_dob'] != "" && $dataToSearch['end_dob'] != "") {
			$advanced .= " AND date_of_birth BETWEEN ".$this->escape($dataToSearch['start_dob'])." AND ".$this->escape($dataToSearch['end_dob']);
		}
		
		if ($dataToSearch['start_registered'] != "" && $dataToSearch['end_registered'] != "") {
			$advanced .= " AND creation_time BETWEEN ".$this->escape($dataToSearch['start_registered'])." AND ".$this->escape($dataToSearch['end_registered']);
		}
		
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : ""; 
		$sql = "SELECT 
				 gu.id AS user_id, 
				 gu.name,
				 phone_number,
				 email,
				 TO_CHAR(gu.creation_time,'YYYY-MM-DD HH24:MI') AS creation_time, 
				 date_of_birth, 
				 gender, 
				 marital_status, 
				 marital_kid_count, 
				 holiday,
				 address_province,
				 dc.name AS channel,
				 CONCAT (manufactur,' ', di.model) AS device_model,
				 di.name AS imei,
				 type,
				 (SELECT max(redeem_time) FROM campaign_redeem WHERE user_id=gu.id) AS last_activity
				FROM
				(
					select id, name, creation_time, date_of_birth, gender, marital_status, marital_kid_count, holiday, address, address_province
					FROM ".$this->mtr."ggi_user
					".$advanced."
					".$limit."
				)AS gu
				LEFT JOIN ".$this->mtr."ggi_app_session_user gasu ON gu.id=gasu.user_id
				LEFT OUTER JOIN ".$this->mtr."device_identifier AS di ON gasu.device_identifier_id = di.id
				LEFT OUTER JOIN ".$this->mtr."ggi_user_email AS gue ON gu.id = gue.user_id
				LEFT OUTER JOIN ".$this->mtr."ggi_user_phone gup ON gu.id=gup.user_id
				LEFT OUTER JOIN ".$this->mtr."ggi_app_session gas ON di.id=gas.device_identifier_id
				LEFT OUTER JOIN distribution_channel dc ON gas.channel_id=dc.id";
	
		$row = $this->db->fetchAll($sql);
		$device = $this->getDeviceFragment();
		$data = array();
		foreach($row as $r) :
			$code = trim(str_replace("SAMSUNG", "", strtoupper($r->device_model)));
			$r->device_name = $device[$code]->product_name;
			array_push($data, $r);
		endforeach;
		return $data;
	}
	
	public function getCustomerSgiftCount($search, $dataToSearch)
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
		$advanced = ($temp <= 0) ? " WHERE (name ~* ".$this->escape($search)." or marital_status ~* ".$this->escape($search)." 
									or gender ~* ".$this->escape($search)." or marital_status ~* ".$this->escape($search)." 
									or holiday ~* ".$this->escape($search)." or address_province ~* ".$this->escape($search)." 
									or id in (
										select user_id from ".$this->mtr."ggi_user_phone where phone_number ~* ".$this->escape($search)."
									) or id in (
										select user_id from ".$this->mtr."ggi_user_email where email ~* ".$this->escape($search)."
									) or id in (
										select user_id from ".$this->mtr."ggi_app_session_user as mgasu 
										left join ".$this->mtr."device_identifier AS mdi on mgasu.device_identifier_id = mdi.id
										where concat(manufactur,' ',model) ~* ".$this->escape($search)." or mdi.name ~* ".$this->escape($search)."
									))" 
								: 
								" WHERE (name ~* ".$this->escape($dataToSearch['name'])." AND marital_status ~* ".$this->escape($dataToSearch['marital_status'])." 
									AND gender ~* ".$this->escape($dataToSearch['gender'])."
									AND holiday ~* ".$this->escape($dataToSearch['holiday'])." AND address_province ~* ".$this->escape($dataToSearch['address'])." 
									AND id in (
										select user_id from ".$this->mtr."ggi_user_phone where phone_number ~* ".$this->escape($dataToSearch['phone'])."
									) AND id in (
										select user_id from ".$this->mtr."ggi_user_email where email ~* ".$this->escape($dataToSearch['email'])."
									) )";
		
		if ($dataToSearch['device_model'] != "" || $dataToSearch['imei'] != "") {
			$advanced .= " AND id in (
										select user_id from ".$this->mtr."ggi_app_session_user as mgasu 
										left join ".$this->mtr."device_identifier AS mdi on mgasu.device_identifier_id = mdi.id
										where concat(manufactur,' ',model) ~* ".$this->escape($dataToSearch['device_model'])." AND mdi.name ~* ".$this->escape($dataToSearch['imei'])."
									)";
		}
		
		if ($dataToSearch['start_dob'] != "" && $dataToSearch['end_dob'] != "") {
			$advanced .= " AND date_of_birth BETWEEN ".$this->escape($dataToSearch['start_dob'])." AND ".$this->escape($dataToSearch['end_dob']);
		}
		
		if ($dataToSearch['start_registered'] != "" && $dataToSearch['end_registered'] != "") {
			$advanced .= " AND creation_time BETWEEN ".$this->escape($dataToSearch['start_registered'])." AND ".$this->escape($dataToSearch['end_registered']);
		}
		
		$sql = "SELECT 
				 COUNT(*) AS jumlah
				FROM ".$this->mtr."ggi_user
				".$advanced;
		$row = $this->db->fetchRow($sql);
		return $row->jumlah;
	}
	
	// --------------------------------------------------------------------------------------
	// --------------------------------------------------------------------------------------
	
	public function getTotalDailyActive(){
		
		$sql = "SELECT
					ggi_promo_users_total_daily,
					ggi_promo_users_premium_daily,
					ggi_promo_users_mid_daily,
					ggi_promo_users_entry_daily,
					ggi_promo_users_tab_daily,
					ggi_promo_users_other_daily
				FROM
					ggi_promo_users
					ORDER BY date DESC
					LIMIT 1";
		return $this->db->fetchAll($sql);
	}
	
	public function getPromoSegment($search, $offset, $limit, $datasearch=array(), $start_date='', $end_date='', $range=''){
		$advanced ='';
		$temp = 0;
		$where = array();
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (title ~* '".$search."')";
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND creation_time::date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		if($range!=""){
			$advanced .= " AND creation_time::date BETWEEN (NOW() - INTERVAL '".$range." DAY') AND NOW()";
		}
		
		$sql = "select id, TO_CHAR(creation_time, 'YYYY-MM-DD HH:mm') AS creation_time, title, total_user, total_device from ggi_promo_segment ".$advanced." ORDER BY creation_time DESC OFFSET ".$offset." LIMIT ".$limit;
		// echo $sql;
		return $this->db->fetchAll($sql);
	}
	
	public function getPromoSegmentDetail($id=''){
		
		$sql = "select title, TO_CHAR(gps.creation_time, 'YYYY-MMM-DD HH:mm') AS creation_time, segment_rule_type, segment_rule_input
						from ggi_promo_segment gps
						left outer join ggi_promo_segment_detail gpsd on gps.id=gpsd.promo_segment_id";
		if($id!='')
			$sql .= " where gps.id=".$this->escape($id);
		else
			$sql = "select TO_CHAR(gps.creation_time, 'YYYY-MMM-DD HH:mm') AS creation_time, title, total_user, total_device, segment_rule_type, segment_rule_input
						from ggi_promo_segment gps
						left outer join ggi_promo_segment_detail gpsd on gps.id=gpsd.promo_segment_id";
		return $this->db->fetchAll($sql);
	}
	
	public function getTotalPromoSegment($search, $datasearch=array(), $start_date='', $end_date='', $range=''){
		$advanced ='';
		$temp = 0;
		$where = array();
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (title ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND creation_time::date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		if($range!=""){
			$advanced .= " AND creation_time::date BETWEEN NOW() - INTERVAL '".$range." DAY' AND NOW()";
		}
		
		$sql = $this->db->select()
						->from("ggi_promo_segment", array("total" => "COUNT(id)"));
		$sql .= $advanced;
		$result = $this->db->fetchRow($sql);
		return $result->total;
	}
	
	//-------------------------------------------------------------
	//PROVIDER REQUEST
	
	public function getProviderRequest($search, $offset, $limit, $datasearch=array(), $start_date='', $end_date='', $range='')
	{
		$advanced ='';
		$temp = 0;
		$where = array();
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (url ~* '".$search."')";
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND creation_time::date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		if($range!=""){
			$advanced .= " AND creation_time::date BETWEEN (NOW() - INTERVAL '".$range." DAY') AND NOW()";
		}
		
		$sql = "select id, TO_CHAR(creation_time, 'YYYY-MM-DD HH:mm:ss') AS creation_time, url 
		from (
		SELECT id,creation_time,url
		FROM mtr2_provider_request
		WHERE url ~* 'merchantoffer'
		) AS pr ".$advanced." ORDER BY creation_time DESC OFFSET ".$offset." LIMIT ".$limit;
		return $this->db->fetchAll($sql);
	}

	public function getProviderRequestDetail($id='')
	{
		$sql = "select pr.id, TO_CHAR(pr.creation_time, 'YYYY-MMM-DD HH:mm:ss') AS creation_time, pr.last_update_time, p.name AS provider_name, url, request_id, payload, response, http_headers
						from 
						mtr2_provider_request pr
						LEFT JOIN provider p ON pr.provider_id=p.id";
		if($id!='')
			$sql .= " where pr.id=".$this->escape($id);
		else
			$sql = "select pr.id, TO_CHAR(pr.creation_time, 'YYYY-MMM-DD HH:mm:ss') AS creation_time, pr.last_update_time, p.name AS provider_name, url, request_id, payload, response, http_headers
						from mtr2_provider_request pr
						LEFT JOIN provider p ON pr.provider_id=p.id";
		return $this->db->fetchAll($sql);
	}

	public function getTotalProviderRequest($search, $datasearch=array(), $start_date='', $end_date='', $range='')
	{
		$advanced ='';
		$temp = 0;
		$where = array();
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (url ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND creation_time::date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		if($range!=""){
			$advanced .= " AND creation_time::date BETWEEN NOW() - INTERVAL '".$range." DAY' AND NOW()";
		}
		
		$sql = $this->db->select()
						->from("mtr2_provider_request", array("total" => "COUNT(id)"));
		$sql .= $advanced;
		$result = $this->db->fetchRow($sql);
		return $result->total;
	}
	
	//-------------------------------------------------------------
	
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
								 imei ~* ".$this->escape($dataToSearch['imei'])." AND
								 c.device_model ~* ".$this->escape($dataToSearch['device_model'])." AND 
								 ('Sgift') ~* ".$this->escape($dataToSearch['app'])." AND
								 status ~* ".$this->escape($dataToSearch['status'])
								 : 
								"name ~* ".$this->escape($search)." OR 
								 email ~* ".$this->escape($search)." OR 
								 imei ~* ".$this->escape($search)." OR 
								 c.device_model ~* ".$this->escape($search)." OR 
								 ('Sgift') ~* ".$this->escape($search)." OR 
								 status ~* ".$this->escape($search);
								 
		$sql = $this->db->select()
						->from(array("c" => "customer_sgift_today_test"), array("customer_id"=> "id", "name","email","imei","type","device_model","app" => "('Sgift')",
															"status"))
						->joinLeft(array("md" => "msdevice_price"), "md.device_model ~* c.device_model", array("price" => "price"))
						->where($advanced);
		$table = $this->db->fetchAll($sql);
		
		$fd = $this->getFDTodayFragment();
		$ld = $this->getLDTodayFragment();
		$device = $this->getDeviceFragment();
		
		$data = array();
		foreach ($table as $row) : 
			$code = trim(str_replace("SAMSUNG","",strtoupper($row->device_model)));
			$row->first_download_date = $fd[$row->customer_id]->to_char; 
			$row->last_download_date = $ld[$row->customer_id]->to_char;
			$row->product_name = $device[$code]->product_name;
			$data[$row->imei] = $row;
		endforeach;
		return $data;
	}
	
	public function getFDTodayFragment()
	{
		$sql = $this->db->select()
						->from("first_download_today", array("user_id", "to_char"));
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$data[$r->user_id] = $r;
		endforeach;
		return $data;
	}
	
	public function getLDTodayFragment()
	{
		$sql = $this->db->select()
						->from("last_download_today", array("user_id", "to_char"));
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$data[$r->user_id] = $r;
		endforeach;
		return $data;
	}
	
	public function getDeviceFragment()
	{
		$sql = $this->db->select()
						->from("device_product", array("code", "product_name"));
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$data[$r->code] = $r;
		endforeach;
		return $data;
	}
	
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
								 imei ~* ".$this->escape($dataToSearch['imei'])." AND
								 device_model ~* ".$this->escape($dataToSearch['device_model'])." AND 
								 ('Sgift') ~* ".$this->escape($dataToSearch['app'])." AND
								 ( CASE WHEN LOWER(status) = 'active' THEN 'enabled' ELSE 'disabled' END ) ~* ".$this->escape($dataToSearch['status'])
								 : 
								"name ~* ".$this->escape($search)." OR 
								 email ~* ".$this->escape($search)." OR 
								 imei ~* ".$this->escape($search)." OR 
								 device_model ~* ".$this->escape($search)." OR 
								 ('Sgift') ~* ".$this->escape($search)." OR 
								 ( CASE WHEN LOWER(status) = 'active' THEN 'enabled' ELSE 'disabled' END ) ~* ".$this->escape($search);
								 
		$sql = $this->db->select()
						->from("customer_sgift_today", array("Jumlah" => "COUNT(email)"))
						->where($advanced);
						
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function getMerchantType()
	{
		$sql = $this->db->select()
						->distinct()
						->from("provider_merchant", array( "id" => "MIN(merchant_id)", 
															"name" => "name"))
						->group("name")
						->order("name ASC");
		return $this->db->fetchAll($sql);
	}
		
	public function getMerchantNameByID($merchantID)
	{
		$sql = $this->db->select()
						->from("provider_merchant", array("name" => "name"))
						->where("merchant_id = ?", $this->validate($merchantID));
		$row = $this->db->fetchRow($sql);
		return $row->name;
	}
	
	public function getProvider()
	{
		$sql = $this->db->select()
						->distinct()
						->from("provider", array("id", "name"))
						->order("name ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function getProviderNameByID($providerID)
	{
		$sql = $this->db->select()
						->from("provider", array("name" => "name"))
						->where("id = ?", $this->validate($providerID));
		$row = $this->db->fetchRow($sql);
		return $row->name;
	}
	public function getPromoDetail($promo_id,$batch_id,$campaign_id, $provider_id, $channel_id){
		$search = "select
						dc.name as channel,
						pm.name as merchant_name,
						coc.name as coupon_title,
						claim_type,
						coc.description,
						coc.tnc,
						sf.name as promo_image
					from
					(
						select channel_id, provider_id, offer_id, batch_id, campaign_id, name, description, tnc
						from ".$this->mtr."channel_offer_campaign
							where channel_id=".$this->escape($channel_id)." and provider_id=".$this->escape($provider_id)." and offer_id=".$this->escape($promo_id)."  and batch_id=".$this->escape($batch_id)." and campaign_id=".$this->escape($campaign_id)."
					)as coc

					left outer join distribution_channel dc on coc.channel_id=dc.id
					left outer join ".$this->mtr."provider_offer po on coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id
					left outer join ".$this->mtr."provider_merchant pm on po.provider_id=pm.provider_id and po.merchant_id=pm.merchant_id
					left outer join static_file sf ON po.banner_static_file_id=sf.id
					";
		// $this->logToFirebug($search);
		return $this->db->fetchAll($search);
		// echo $search;
	}	
	public function getAllPromoFragment($limit, $offset, $q, $arr, $merchant_name = '', $merchantList = array(), $cs = false){
		try{
			$set = "";
			$where = "";
			
			if (count($merchantList) > 0) {
				$comma = "";
				$merchants = "";
				foreach ($merchantList as $merchant) : 
					$merchants .= $comma.$this->escape($merchant);
					$comma = ",";
				endforeach;
				$adv_merchant .= ($merchants=='')?'':(" AND EXISTS 
														( 
															SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
															WHERE EXISTS 
															( 
																SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
																WHERE merchant_id IN (".$merchants.") AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id 
															) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id 
														) ");
			} else {
				$adv_merchant .= ($merchant_name=='')?'':(" AND EXISTS 
														( 
															SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
															WHERE EXISTS 
															( 
																SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
																WHERE merchant_id =".$this->escape($merchant_name)." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id 
															) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id 
														)");
			}
			
			if (count($arr) > 0) {
				$where = "WHERE (EXISTS(
							SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
							WHERE claim_type ~* ".$this->escape($arr['claim_type'])." AND EXISTS (
								SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
								WHERE fpm.name ~* ".$this->escape($arr['merchant'])." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id
							) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id
						) AND fcoc.name ~* ".$this->escape($arr['coupon_title'])." AND TO_CHAR(fcoc.creation_time, 'YYYY-MM-DD HH24:MI') ~* ".$this->escape($arr['publish_date'])
						.") ".$adv_merchant;
			} else {
				$where = "WHERE (EXISTS(
							SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
							WHERE claim_type ~* ".$this->escape($q)." OR EXISTS (
								SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
								WHERE name ~* ".$this->escape($q)." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id
							) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id
						) OR fcoc.name ~* ".$this->escape($q)." OR TO_CHAR(fcoc.creation_time, 'YYYY-MM-DD HH24:MI') ~* ".$this->escape($q).") ".$adv_merchant;
			}
			
			if ($cs == true) {
				if ($where != "") {
					$where .= " AND current_timestamp BETWEEN fcoc.start_time AND fcoc.end_time";
				} else {
					$where .= " WHERE current_timestamp BETWEEN fcoc.start_time AND fcoc.end_time";
				}
			}
			
			$add1 = ($arr['start_start'] != "" && $arr['end_start'] != "" ) ? "AND EXTRACT(EPOCH FROM start_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_start']).") AND EXTRACT(EPOCH FROM start_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_start']).")" : "";
			$add2 = ($arr['start_end'] != "" && $arr['end_end'] != "" ) ? "AND EXTRACT(EPOCH FROM end_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_end']).") AND EXTRACT(EPOCH FROM end_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_end']).")" : ""; 
			$add3 = ($arr['start_publish'] != "" && $arr['end_publish'] != "" ) ? "AND EXTRACT(EPOCH FROM creation_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_publish']).") AND EXTRACT(EPOCH FROM creation_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_publish']).")" : ""; 
			
			if($limit!='all') $set = " LIMIT $limit OFFSET $offset ";
			
			$normal_search = "
			SELECT DISTINCT
			CONCAT (coc.channel_id,'-', coc.provider_id,'-', coc.offer_id,'-', coc.batch_id,'-', coc.campaign_id) as promo_id,
			dc.name as channel,
			pm.name as merchant_name,
			coc.name as coupon_title,
			claim_type,
			TO_CHAR(coc.start_time,'DD Mon YYYY HH24:MI') as start_date,
			TO_CHAR(coc.end_time,'DD Mon YYYY HH24:MI') as end_date,
			TO_CHAR(coc.creation_time,'DD Mon YYYY HH24:MI:SS') as publish_date,
			coc.creation_time
			from (
				SELECT channel_id, provider_id, offer_id, batch_id, campaign_id, name, start_time, end_time, creation_time
				FROM ".$this->mtr."channel_offer_campaign AS fcoc
				".$where." 
				".$add1."
				".$add2."
				".$add3."
				ORDER BY creation_time DESC
				".$set."
			) AS coc
			left outer join distribution_channel dc on (coc.channel_id=dc.id)
			left outer join ".$this->mtr."provider_offer po on (coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id)
			left outer join ".$this->mtr."provider_merchant pm on (po.provider_id=pm.provider_id and po.merchant_id=pm.merchant_id)
			";
						
			$normal_search .= "order by coc.creation_time DESC";
		
			$this->logToFirebug($normal_search);
			
			$row = $this->db->fetchAll($normal_search);
			
			$promo_id = array();
			foreach($row as $r) : 
				array_push($promo_id, $this->escape($r->promo_id));
			endforeach;
			$rowf2 = $this->getAllPromoFragment2($promo_id);
			$rowf3 = $this->getAllPromoFragment3($promo_id);
			$rowf4 = $this->getAllPromoFragment4($promo_id);
			
			$arr = array();
			foreach($row as $r) :
				$r->total_coupon = $rowf2[$r->promo_id];
				$r->total_redeem = $rowf3[$r->promo_id];
				$r->total_claim = $rowf4[$r->promo_id];
				array_push($arr, $r);
			endforeach;
			return $arr;
			return $row;
		} catch(Zend_Exception $e)
		{
			echo $e->getMessage();
		}
	}
	public function getAllPromoFragmentCSV($limit, $offset, $q, $arr, $merchant_name = '', $merchantList = array(), $cs = false){
		try{
			$set = "";
			$where = "";
			
			if (count($merchantList) > 0) {
				$comma = "";
				$merchants = "";
				foreach ($merchantList as $merchant) : 
					$merchants .= $comma.$this->escape($merchant);
					$comma = ",";
				endforeach;
				$adv_merchant .= ($merchants=='')?'':(" AND EXISTS 
														( 
															SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
															WHERE EXISTS 
															( 
																SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
																WHERE merchant_id IN (".$merchants.") AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id 
															) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id 
														) ");
			} else {
				$adv_merchant .= ($merchant_name=='')?'':(" AND EXISTS 
														( 
															SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
															WHERE EXISTS 
															( 
																SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
																WHERE merchant_id =".$this->escape($merchant_name)." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id 
															) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id 
														)");
			}
			
			if (count($arr) > 0) {
				$where = "WHERE (EXISTS(
							SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
							WHERE claim_type ~* ".$this->escape($arr['claim_type'])." AND EXISTS (
								SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
								WHERE fpm.name ~* ".$this->escape($arr['merchant'])." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id
							) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id
						) AND fcoc.name ~* ".$this->escape($arr['coupon_title'])." AND TO_CHAR(fcoc.creation_time, 'YYYY-MM-DD HH24:MI') ~* ".$this->escape($arr['publish_date'])
						.") ".$adv_merchant;
			} else {
				$where = "WHERE (EXISTS(
							SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
							WHERE claim_type ~* ".$this->escape($q)." OR EXISTS (
								SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
								WHERE name ~* ".$this->escape($q)." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id
							) AND fcoc.provider_id=fpo.provider_id and fcoc.offer_id=fpo.offer_id and fcoc.batch_id=fpo.batch_id
						) OR fcoc.name ~* ".$this->escape($q)." OR TO_CHAR(fcoc.creation_time, 'YYYY-MM-DD HH24:MI') ~* ".$this->escape($q).") ".$adv_merchant;
			}
			
			if ($cs == true) {
				if ($where != "") {
					$where .= " AND current_timestamp BETWEEN fcoc.start_time AND fcoc.end_time";
				} else {
					$where .= " WHERE current_timestamp BETWEEN fcoc.start_time AND fcoc.end_time";
				}
			}
			
			$add1 = ($arr['start_start'] != "" && $arr['end_start'] != "" ) ? "AND EXTRACT(EPOCH FROM start_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_start']).") AND EXTRACT(EPOCH FROM start_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_start']).")" : "";
			$add2 = ($arr['start_end'] != "" && $arr['end_end'] != "" ) ? "AND EXTRACT(EPOCH FROM end_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_end']).") AND EXTRACT(EPOCH FROM end_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_end']).")" : ""; 
			$add3 = ($arr['start_publish'] != "" && $arr['end_publish'] != "" ) ? "AND EXTRACT(EPOCH FROM creation_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_publish']).") AND EXTRACT(EPOCH FROM creation_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_publish']).")" : ""; 
			
			if($limit!='all') $set = " LIMIT $limit OFFSET $offset ";
			
			$normal_search = "
			SELECT DISTINCT
			CONCAT (coc.channel_id,'-', coc.provider_id,'-', coc.offer_id,'-', coc.batch_id,'-', coc.campaign_id) as promo_id,
			dc.name as channel,
			pm.name as merchant_name,
			coc.name as coupon_title,
			regexp_replace(coc.tnc, E'[\\n\\r]+', ' ', 'g' ) AS tnc,
			regexp_replace(coc.description, E'[\\n\\r]+', ' ', 'g' ) AS description,
			claim_type,
			TO_CHAR(coc.start_time,'DD Mon YYYY HH24:MI') as start_date,
			TO_CHAR(coc.end_time,'DD Mon YYYY HH24:MI') as end_date,
			TO_CHAR(coc.creation_time,'DD Mon YYYY HH24:MI:SS') as publish_date,
			coc.creation_time
			from (
				SELECT channel_id, provider_id, offer_id, batch_id, campaign_id, name, start_time, end_time, creation_time, description, tnc
				FROM ".$this->mtr."channel_offer_campaign AS fcoc
				".$where." 
				".$add1."
				".$add2."
				".$add3."
				ORDER BY creation_time DESC
				".$set."
			) AS coc
			left outer join distribution_channel dc on (coc.channel_id=dc.id)
			left outer join ".$this->mtr."provider_offer po on (coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id)
			left outer join ".$this->mtr."provider_merchant pm on (po.provider_id=pm.provider_id and po.merchant_id=pm.merchant_id)
			";
			$normal_search .= "order by coc.creation_time DESC";
		
			$this->logToFirebug($normal_search);
			
			$row = $this->db->fetchAll($normal_search);
			
			$promo_id = array();
			foreach($row as $r) : 
				array_push($promo_id, $this->escape($r->promo_id));
			endforeach;
			
			$rowf2 = $this->getAllPromoFragment2($promo_id);
			$rowf3 = $this->getAllPromoFragment3($promo_id);
			$rowf4 = $this->getAllPromoFragment4($promo_id);
			
			$arr = array();
			foreach($row as $r) :
				$r->total_coupon = $rowf2[$r->promo_id];
				$r->total_redeem = $rowf3[$r->promo_id];
				$r->total_claim = $rowf4[$r->promo_id];
				array_push($arr, $r);
			endforeach;
			return $arr;
			return $row;
		} catch(Zend_Exception $e)
		{
			echo $e->getMessage();
		}
	}	
	public function getAllPromoFragment2($promo_id = "")
	{
		$promo_col = "CONCAT (channel_id, provider_id, offer_id, batch_id, campaign_id)";
		$promo_id = str_replace("-","", $promo_id);
		$where_pr = ($promo_id == "") ? $promo_col." ~* ''" : $promo_col." IN (".implode(",",$promo_id).")";
		$sql = $this->db->select()
						->distinct()
						->from($this->mtr."campaign_redeem_code", array("promo_id" => "CONCAT (channel_id,'-', provider_id,'-', offer_id,'-', batch_id,'-', campaign_id)",
																	   "total_coupon" => "COUNT(offer_id)"))
						->where($where_pr)
						->group(array("channel_id", "provider_id","offer_id","batch_id","campaign_id"));
		$row = $this->db->fetchAll($sql);
		$arr = array();
		foreach($row as $r) : 
		
			$arr[$r->promo_id] = $r->total_coupon;
		endforeach;
		return $arr;
	}
	
	public function getAllPromoFragment3($promo_id = "")
	{
		$promo_col = "CONCAT (channel_id, provider_id, offer_id, batch_id, campaign_id)";
		$promo_id = str_replace("-","", $promo_id);
		$where_pr = ($promo_id == "") ? $promo_col." ~* ''" : $promo_col." IN (".implode(",",$promo_id).")";
		$sql = $this->db->select()
						->distinct()
						->from($this->mtr."campaign_redeem", array("promo_id" => "CONCAT (channel_id,'-', provider_id,'-', offer_id,'-', batch_id,'-', campaign_id)",
																  "total_redeem" => "COUNT(offer_id)"))
						->where($where_pr)
						->group(array("channel_id", "provider_id", "offer_id", "batch_id", "campaign_id" ));
		$row = $this->db->fetchAll($sql);
		$arr = array();
		foreach($row as $r) : 
			$arr[$r->promo_id] = $r->total_redeem;
		endforeach;
		return $arr;
	}
	
	public function getAllPromoFragment4($promo_id = "")
	{
		$promo_col = "CONCAT (channel_id, provider_id, offer_id, batch_id, campaign_id)";
		$promo_id = str_replace("-","", $promo_id);
		$where_pr = ($promo_id == "") ? $promo_col." ~* ''" : $promo_col." IN (".implode(",",$promo_id).")";
		$sql = "select distinct
				CONCAT (t1.channel_id,'-', t1.provider_id,'-', t1.offer_id,'-', t1.batch_id,'-', t1.campaign_id) as promo_id,
				count(redeem_code) AS total_claim from
				(select
				cr.provider_id, channel_id, cr.offer_id,cr. batch_id, campaign_id, cc.redeem_code
				from
				".$this->mtr."campaign_redeem cr
				left outer join ".$this->mtr."campaign_claim cc on cr.redeem_code=cc.redeem_code
				)as t1
				where ".$where_pr."
				group by t1.channel_id, t1.provider_id, t1.offer_id, t1.batch_id, t1.campaign_id";						
		$row = $this->db->fetchAll($sql);
		$arr = array();
		foreach($row as $r) : 
			$arr[$r->promo_id] = $r->total_claim;
		endforeach;
		return $arr;
	}
	
	public function getAllPromoCount($q, $arr, $merchant_name = '', $merchantList = array(), $cs = false, $range = array()){
		$normal_search = "
		select
		COUNT(coc.channel_id) AS jumlah
		from ".$this->mtr."channel_offer_campaign coc
		left outer join ".$this->mtr."channel_offer co on (coc.channel_id=co.channel_id and coc.provider_id=co.provider_id and coc.offer_id=co.offer_id and coc.batch_id=co.batch_id)
		left outer join ".$this->mtr."provider_offer po on (coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id)
		left outer join ".$this->mtr."provider_merchant pm on (po.provider_id=pm.provider_id and po.merchant_id=pm.merchant_id)
		";
		
		$search=
		"(coc.name ~* '".$q."' OR
		claim_type ~* '".$q."' OR
		TO_CHAR(coc.creation_time,'DD Mon YYYY HH:MI') ~* '".$q."' OR
		pm.name ~* '".$q."'
		)";
			
		$advanced = "";
		if($q=='' && sizeof($arr)==0){
			$normal_search.=" WHERE ".$search;
		}
		else if($q=='' && sizeof($arr)>0){
			$normal_search.=" WHERE ";
			$separator =' ';
			foreach($arr as $k=>$v){ 
				if($v!=''){
					$normal_search.= $separator.$k." ~* '".$v."'";
					$separator=" AND ";
				}
			}
		}
		else{
			$normal_search.=" WHERE ".$search;
			foreach($arr as $k=>$v){
				if($v!=''){
					$normal_search.= ' AND '.$k." ~* '".$v."'";
				}
			}
		}
		
		if (count($merchantList) > 0) {
			$comma = "";
			$merchants = "";
			foreach ($merchantList as $merchant) : 
				$merchants .= $comma.$this->escape($merchant);
				$comma = ",";
			endforeach;
			$normal_search.= ($merchants=='')?'':(" AND pm.merchant_id IN (".$merchants.")");
		} else {
			$normal_search.= ($merchant_name=='')?'':(" AND pm.merchant_id=".$this->escape($merchant_name));
		}
		
		$normal_search .= ($range['start_start'] != "" && $range['end_start'] != "" ) ? " AND EXTRACT(EPOCH FROM coc.start_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_start']).") AND EXTRACT(EPOCH FROM coc.start_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_start']).")" : "";
		$normal_search .= ($range['start_end'] != "" && $range['end_end'] != "" ) ? " AND EXTRACT(EPOCH FROM coc.end_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_end']).") AND EXTRACT(EPOCH FROM coc.end_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_end']).")" : ""; 
		$normal_search .= ($range['start_publish'] != "" && $range['end_publish'] != "" ) ? " AND EXTRACT(EPOCH FROM coc.creation_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_publish']).") AND EXTRACT(EPOCH FROM coc.creation_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_publish']).")" : ""; 
			
		
		//change
		if ($cs == true) {
			$normal_search.= " AND current_timestamp BETWEEN coc.start_time AND coc.end_time";
		}
				
		$this->logToFirebug($normal_search);
		
		$row = $this->db->fetchRow($normal_search);
		return $row->jumlah;
	}
	
	public function getRedeem($search, $dataToSearch, $offset, $length, $merchant_name = '', $merchantList = array()){
		if (count($merchantList) > 0) {
			$comma = "";
			$merchants = "";
			foreach ($merchantList as $merchant) : 
				$merchants .= $comma.$this->escape($merchant);
				$comma = ",";
			endforeach;
			$adv_merchant.= ($merchants=='')?'':(" AND EXISTS 
												   ( SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
												   WHERE ( EXISTS 
												   ( SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
													  WHERE fpm.merchant_id IN (".$merchants.") AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id 
													) 

													) AND fcr.provider_id = fpo.provider_id and fcr.offer_id = fpo.offer_id and fcr.batch_id = fpo.batch_id )");
		} else {
			$adv_merchant.= ($merchant_name=='')?'':(" AND EXISTS 
													   ( SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo 
													   WHERE ( EXISTS 
													   ( SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm 
														  WHERE fpm.merchant_id = ".$this->escape($merchant_name)." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id 
														) 
														) AND fcr.provider_id = fpo.provider_id and fcr.offer_id = fpo.offer_id and fcr.batch_id = fpo.batch_id )");
		}
		
		$where = "";
		if (count($dataToSearch) > 0) {
			$add1 = ($dataToSearch['start_claim'] != "" && $dataToSearch['end_claim'] != "" ) ? "AND EXTRACT(EPOCH FROM claim_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($dataToSearch['start_claim']).") AND EXTRACT(EPOCH FROM claim_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($dataToSearch['end_claim']).")" : "";
			$add2 = ($dataToSearch['start_redeem'] != "" && $dataToSearch['end_redeem'] != "" ) ? "AND EXTRACT(EPOCH FROM redeem_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($dataToSearch['start_redeem']).") AND EXTRACT(EPOCH FROM redeem_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($dataToSearch['end_redeem']).")" : ""; 
		
			$where = "WHERE (EXISTS (
				SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo
				WHERE (claim_type ~* ".$this->escape($dataToSearch['claim_type'])." AND EXISTS (
					SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm
					WHERE fpm.name ~* ".$this->escape($dataToSearch['merchant'])." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id
				)
				) AND fcr.provider_id = fpo.provider_id and fcr.offer_id = fpo.offer_id and fcr.batch_id = fpo.batch_id
			) AND EXISTS (
				SELECT channel_id, provider_id, offer_id, batch_id, campaign_id FROM ".$this->mtr."channel_offer_campaign AS fcoc
				WHERE fcoc.name ~* ".$this->escape($dataToSearch['promo_name'])." AND fcr.channel_id = fcoc.channel_id and fcr.provider_id = fcoc.provider_id and fcr.offer_id=fcoc.offer_id and fcr.batch_id=fcoc.batch_id and fcr.campaign_id=fcoc.campaign_id
			) AND EXISTS (
				SELECT channel_id, provider_id, offer_id, batch_id FROM ".$this->mtr."channel_offer AS fco
				WHERE fco.name ~* ".$this->escape($dataToSearch['channel'])." AND fcr.channel_id=fco.channel_id and fcr.provider_id=fco.provider_id and fcr.offer_id=fco.offer_id and fcr.batch_id=fco.batch_id
			) AND EXISTS (
				SELECT provider_id, channel_id, batch_id, campaign_id FROM ".$this->mtr."campaign_claim AS fcc
				WHERE fcr.provider_id = fcc.provider_id AND fcr.redeem_code = fcc.redeem_code AND fcr.offer_id = fcc.offer_id AND fcr.batch_id = fcc.batch_id
				".$add1."
			) AND CONCAT(fcr.channel_id,'-',fcr.provider_id,'-', fcr.offer_id,'-', fcr.batch_id,'-', fcr.campaign_id) ~* ".$this->escape($dataToSearch['promo_id'])." AND redeem_code ~* ".$this->escape($dataToSearch['redeem_code']).") 
			".$add2." ".$adv_merchant;
		} else {
			$where = "WHERE (EXISTS (
				SELECT provider_id, offer_id, batch_id FROM ".$this->mtr."provider_offer AS fpo
				WHERE (claim_type ~* ".$this->escape($search)." OR EXISTS (
					SELECT provider_id, merchant_id FROM ".$this->mtr."provider_merchant AS fpm
					WHERE fpm.name ~* ".$this->escape($search)." AND fpo.provider_id = fpm.provider_id and fpo.merchant_id = fpm.merchant_id
				)
				) AND fcr.provider_id = fpo.provider_id and fcr.offer_id = fpo.offer_id and fcr.batch_id = fpo.batch_id
			) OR EXISTS (
				SELECT channel_id, provider_id, offer_id, batch_id, campaign_id FROM ".$this->mtr."channel_offer_campaign AS fcoc
				WHERE fcoc.name ~* ".$this->escape($search)." AND fcr.channel_id = fcoc.channel_id and fcr.provider_id = fcoc.provider_id and fcr.offer_id=fcoc.offer_id and fcr.batch_id=fcoc.batch_id and fcr.campaign_id=fcoc.campaign_id
			) OR EXISTS (
				SELECT channel_id, provider_id, offer_id, batch_id FROM ".$this->mtr."channel_offer AS fco
				WHERE fco.name ~* ".$this->escape($search)." AND fcr.channel_id=fco.channel_id and fcr.provider_id=fco.provider_id and fcr.offer_id=fco.offer_id and fcr.batch_id=fco.batch_id
			) OR CONCAT(fcr.channel_id,'-',fcr.provider_id,'-', fcr.offer_id,'-', fcr.batch_id,'-', fcr.campaign_id) ~* ".$this->escape($search)." OR redeem_code ~* ".$this->escape($search)
			.") ".$adv_merchant;
		} 
		$limit = ($length == "") ? "" : " LIMIT ".$length." OFFSET ".$offset;	
		$sql="
		select
		dc.name as channel, 
		CONCAT(cr.channel_id,'-',cr.provider_id,'-', cr.offer_id,'-', cr.batch_id,'-', cr.campaign_id) as promo_id, 
		coc.name as promo_name,
		TO_CHAR(redeem_time,'YYYY-MM-DD HH24:MI') AS redeem_time,
		TO_CHAR(claim_time,'YYYY-MM-DD HH24:MI') AS claim_time,
		pm.name as merchant_name,
		claim_type,
		cr.redeem_code as voucher_id,
		pot.name as outlet_name
		from (
			SELECT channel_id, provider_id, offer_id, batch_id, campaign_id, redeem_code, redeem_time FROM
			".$this->mtr."campaign_redeem AS fcr 
			".$where."
			ORDER BY redeem_time DESC 
			".$limit."
		) AS cr
		left outer join ".$this->mtr."channel_offer_campaign coc on (cr.channel_id=coc.channel_id and cr.provider_id=coc.provider_id and cr.offer_id=coc.offer_id and cr.batch_id=coc.batch_id and cr.campaign_id=coc.campaign_id)
		left outer join distribution_channel dc on (coc.channel_id=dc.id)
		left outer join ".$this->mtr."provider_offer po on (cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id)
		left outer join ".$this->mtr."provider_merchant pm on (po.provider_id=pm.provider_id and po.merchant_id=pm.merchant_id)
		left outer join ".$this->mtr."campaign_claim cc on (cr.provider_id=cc.provider_id and cr.offer_id=cc.offer_id and cr.batch_id=cc.batch_id and cr.redeem_code=cc.redeem_code)
		left outer join ".$this->mtr."provider_outlet pot on (cc.provider_id=pot.provider_id and cc.outlet_id=pot.outlet_id)
		".$advanced." 
		ORDER BY redeem_time DESC";
		
		$this->logToFirebug($sql);
		return $this->db->fetchAll($sql);
	}
	
	public function getRedeemCount($search, $dataToSearch, $merchant_name = '', $merchantList = array(), $range = array())
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
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (pm.name ~* '".$search."' or coc.name ~* '".$search."' or cr.redeem_code ~* '".$search."' or co.name ~* '".$search."' or claim_type ~* '".$search."' or CONCAT(cr.channel_id,'-',cr.provider_id,'-', cr.offer_id,'-', cr.batch_id,'-', cr.campaign_id) ~* '".$search."')";
		
		if (count($merchantList) > 0) {
			$comma = "";
			$merchants = "";
			foreach ($merchantList as $merchant) : 
				$merchants .= $comma.$this->escape($merchant);
				$comma = ",";
			endforeach;
			$advanced.= ($merchants=='')?'':(" AND pm.merchant_id IN (".$merchants.")");
		} else {
			$advanced.= ($merchant_name=='')?'':(" AND pm.merchant_id=".$this->escape($merchant_name));
		}
		
		$advanced .= ($range['start_claim'] != "" && $range['end_claim'] != "" ) ? " AND EXTRACT(EPOCH FROM cc.claim_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_claim']).") AND EXTRACT(EPOCH FROM cc.claim_time) <= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_claim']).")" : "";
		$advanced .= ($range['start_redeem'] != "" && $range['end_redeem'] != "" ) ? "AND EXTRACT(EPOCH FROM cr.redeem_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_redeem']).") AND EXTRACT(EPOCH FROM cr.redeem_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_redeem']).")" : ""; 
		
		
		$sql="
		select
		COUNT(cr.offer_id) as total
		from ".$this->mtr."campaign_redeem cr
		left outer join ".$this->mtr."channel_offer_campaign coc on (cr.channel_id=coc.channel_id and cr.provider_id=coc.provider_id and cr.offer_id=coc.offer_id and cr.batch_id=coc.batch_id and cr.campaign_id=coc.campaign_id)
		left outer join ".$this->mtr."channel_offer co on (cr.channel_id=co.channel_id and cr.provider_id=co.provider_id and cr.offer_id=co.offer_id and cr.batch_id=co.batch_id)
		left outer join ".$this->mtr."provider_offer po on (cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id)
		left outer join ".$this->mtr."provider_merchant pm on (po.provider_id=pm.provider_id and po.merchant_id=pm.merchant_id)
		LEFT OUTER JOIN ".$this->mtr."campaign_claim cc ON (cr.provider_id=cc.provider_id and cr.offer_id=cc.offer_id and cr.batch_id=cc.batch_id and cr.redeem_code=cc.redeem_code)
		".$advanced;		
		$row = $this->db->fetchRow($sql);
		
		$this->logToFirebug($sql);
		return $row->total;
	}
	
	//GALAXY GIFT ID PAGE	
	public function getSearchFragment($q, $arr, $offset, $length, $channel_id = ""){
		$limit = "";
		if ($length != "")
		{
			$limit = " LIMIT $length OFFSET $offset"; 
		}
		
		$where = "";
		$add_channel = ($channel_id != "") ? " AND fcr.channel_id = ".$this->escape($channel_id) : "";
		
		if ($q != "") { 
			$where = "WHERE (fcr.user_id IN (
				SELECT user_id FROM ".$this->mtr."ggi_user_email WHERE email ~* ".$this->escape($q)."
			) OR fcr.user_id IN (
				SELECT user_id FROM ".$this->mtr."ggi_user_phone WHERE phone_number ~* ".$this->escape($q)."
			) OR EXISTS (
				SELECT channel_id, provider_id, offer_id, batch_id, campaign_id FROM ".$this->mtr."provider_offer AS fo
				WHERE merchant_id in (
					SELECT merchant_id FROM ".$this->mtr."provider_merchant WHERE name ~* ".$this->escape($q)."
				) AND fcr.provider_id = fo.provider_id AND  fcr.offer_id = fo.offer_id AND fcr.batch_id = fo.batch_id 
			) OR fcr.device_identifier_id IN (
				SELECT id FROM ".$this->mtr."device_identifier 
				WHERE name ~* ".$this->escape($q)." OR model ~* ".$this->escape($q)." 
			) OR EXISTS (
				SELECT provider_id, channel_id, offer_id, batch_id, campaign_id FROM ".$this->mtr."campaign_claim AS fcc
				WHERE (outlet_id IN (
					SELECT outlet_id FROM ".$this->mtr."provider_outlet 
					WHERE name ~* ".$this->escape($q)." OR address ~* ".$this->escape($q)."
				) ) AND fcr.provider_id = fcc.provider_id AND fcr.redeem_code = fcc.redeem_code AND fcr.offer_id = fcc.offer_id AND fcr.batch_id = fcc.batch_id
			) OR fcr.redeem_code ~* ".$this->escape($q).") ".$add_channel;
		} else if (count($arr) > 0) {
			$add1 = ($arr['start_claim'] != "" && $arr['end_claim'] != "" ) ? "AND EXTRACT(EPOCH FROM claim_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_claim']).") AND EXTRACT(EPOCH FROM claim_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_claim']).")" : "";
			$add2 = ($arr['start_redeem'] != "" && $arr['end_redeem'] != "" ) ? "AND EXTRACT(EPOCH FROM redeem_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($arr['start_redeem']).") AND EXTRACT(EPOCH FROM redeem_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($arr['end_redeem']).")" : ""; 
		
			$where = "WHERE fcr.user_id IN (
				SELECT user_id FROM ".$this->mtr."ggi_user_email WHERE email ~* ".$this->escape($arr['email'])."
			) AND fcr.user_id IN (
				SELECT user_id FROM ".$this->mtr."ggi_user_phone WHERE phone_number ~* ".$this->escape($arr['phone_number'])."
			) AND EXISTS (
				SELECT channel_id, provider_id, offer_id, batch_id, campaign_id FROM ".$this->mtr."provider_offer AS fo
				WHERE merchant_id in (
					SELECT merchant_id FROM ".$this->mtr."provider_merchant WHERE name ~* ".$this->escape($arr['merchant'])."
				) AND fcr.provider_id = fo.provider_id AND  fcr.offer_id = fo.offer_id AND fcr.batch_id = fo.batch_id 
			) AND fcr.device_identifier_id IN (
				SELECT id FROM ".$this->mtr."device_identifier 
				WHERE name ~* ".$this->escape($arr['imei'])." AND model ~* ".$this->escape($arr['model'])." 
			) AND EXISTS (
				SELECT provider_id, channel_id, batch_id, campaign_id FROM ".$this->mtr."campaign_claim AS fcc
				WHERE fcr.redeem_code ~* ".$this->escape($arr['redeem_code'])." OR (fcr.provider_id = fcc.provider_id AND fcr.redeem_code = fcc.redeem_code AND fcr.offer_id = fcc.offer_id AND fcr.batch_id = fcc.batch_id 
				 ".$add1.")
			) ".$add2." ".$add_channel;
		} else if ($add_channel != ""){
			$add_channel = ($channel_id != "") ? " fcr.channel_id = ".$this->escape($channel_id) : "";
			$where = "WHERE ".$add_channel;
		}
		
		$normal_search = " select DISTINCT
		a.channel_id,
		a.provider_id,
		a.offer_id as promo_id,
		a.batch_id,
		a.campaign_id,
		a.user_id as user_id,
		coc.name as promo_name,
		f.email AS email_user,
		g.phone_number as telp,
		h.name as merchant_name,
		TO_CHAR(coc.start_time,'YYYY-MM-DD HH24:MI') as start_time,
		TO_CHAR(coc.end_time,'YYYY-MM-DD HH24:MI') as end_time,
		e.type,
		e.name as imei,
		e.manufactur as manufactur,
		e.model as device_model,
		dp.product_name as product_name,
		TO_CHAR(a.redeem_time,'YYYY-MM-DD HH24:MI') AS redeem_time,
		TO_CHAR(d.claim_time,'YYYY-MM-DD HH24:MI') AS claim_time,
		a.redeem_code as voucher_id,
		c.outlet_id,
		c.name as outlet_name,
		c.address as outlet_address
		from
		(
			SELECT channel_id, provider_id, offer_id, batch_id, campaign_id, user_id, redeem_code, redeem_time , device_identifier_id
			FROM ".$this->mtr."campaign_redeem AS fcr
			".$where."
			ORDER BY redeem_time DESC
			".$limit."
		) AS a
		left outer join ".$this->mtr."ggi_user_email f on (a.user_id=f.user_id)
		left outer join ".$this->mtr."ggi_user_phone g on (a.user_id=g.user_id)
		left outer join ".$this->mtr."provider_offer b on (a.provider_id=b.provider_id and a.offer_id=b.offer_id and a.batch_id=b.batch_id)
		left outer join ".$this->mtr."provider_merchant h on (b.merchant_id=h.merchant_id)
		left outer join ".$this->mtr."channel_offer_campaign coc on(a.channel_id=coc.channel_id and a.provider_id=coc.provider_id and a.offer_id=coc.offer_id and a.batch_id=coc.batch_id and a.campaign_id=coc.campaign_id)
		
		LEFT OUTER JOIN ".$this->mtr."device_identifier e ON (a.device_identifier_id=e.id)
		LEFT OUTER JOIN ".$this->mtr."campaign_claim d ON (a.provider_id=d.provider_id and a.offer_id=d.offer_id and a.batch_id=d.batch_id and a.redeem_code=d.redeem_code)
		LEFT OUTER JOIN ".$this->mtr."provider_outlet c ON (d.outlet_id=c.outlet_id)
		LEFT OUTER JOIN device_product dp ON (e.model ~* dp.code)
		";
		
		$normal_search .= "ORDER BY redeem_time DESC";
		
		$this->logToFirebug($normal_search);
		return $this->db->fetchAll($normal_search);
	}
	
	public function getSearch($q, $arr, $offset, $length){
		$normal_search = " select DISTINCT
		a.channel_id,
		a.provider_id,
		a.offer_id as promo_id,
		a.batch_id,
		a.campaign_id,
		a.user_id as user_id,
		coc.name as promo_name,
		f.email AS email_user,
		g.phone_number as telp,
		h.name as merchant_name,
		TO_CHAR(coc.start_time,'YYYY-MM-DD HH24:MI') as start_time,
		TO_CHAR(coc.end_time,'YYYY-MM-DD HH24:MI') as end_time,
		e.type,
		e.name as imei,
		e.manufactur as manufactur,
		e.model as device_model,
		dp.product_name as product_name,
		TO_CHAR(a.redeem_time,'YYYY-MM-DD HH24:MI') AS redeem_time,
		TO_CHAR(d.claim_time,'YYYY-MM-DD HH24:MI') AS claim_time,
		a.redeem_code as voucher_id,
		c.outlet_id,
		c.name as outlet_name,
		c.address as outlet_address
		from
		federated_campaign_redeem a
		left outer join federated_ggi_user_email f on (a.user_id=f.user_id)
		left outer join federated_ggi_user_phone g on (a.user_id=g.user_id)
		left outer join federated_provider_offer b on (a.provider_id=b.provider_id and a.offer_id=b.offer_id and a.batch_id=b.batch_id)
		left outer join federated_provider_merchant h on (b.merchant_id=h.merchant_id)
		left outer join federated_channel_offer_campaign coc on(a.channel_id=coc.channel_id and a.provider_id=coc.provider_id and a.offer_id=coc.offer_id and a.batch_id=coc.batch_id and a.campaign_id=coc.campaign_id)
		LEFT OUTER JOIN federated_device_identifier e ON (a.device_identifier_id=e.id)
		LEFT OUTER JOIN federated_campaign_claim d ON (a.provider_id=d.provider_id and a.offer_id=d.offer_id and a.batch_id=d.batch_id and a.redeem_code=d.redeem_code)
		LEFT OUTER JOIN federated_provider_outlet c ON (d.outlet_id=c.outlet_id)
		LEFT OUTER JOIN device_product dp ON (e.model ~* dp.code)
		";
		$search="(f.email ~* '".$q."' OR
			g.phone_number ~* '".$q."' OR
			d.redeem_code ~* '".$q."' OR
			coc.offer_id ~* '".$q."' OR
			coc.name ~* '".$q."' OR
			h.name ~* '".$q."' OR
			TO_CHAR(coc.start_time, 'DD Mon YYYY') ~* '".$q."' OR
			TO_CHAR(coc.end_time, 'DD Mon YYYY') ~* '".$q."' OR
			e.type ~* '".$q."' OR
			e.name ~* '".$q."' OR
			e.manufactur ~* '".$q."' OR
			e.model ~* '".$q."' OR
			TO_CHAR(a.redeem_time, 'DD Mon YYYY') ~* '".$q."' OR
			TO_CHAR(d.claim_time, 'DD Mon YYYY') ~* '".$q."' OR
			c.outlet_id ~* '".$q."' OR
			c.name ~* '".$q."' OR
			c.address ~* '".$q."' )";
		$advanced = "";
		if($q=='' && sizeof($arr)==0){
		}
		else if($q=='' && sizeof($arr)>0){
			$normal_search.=" WHERE ";
			$separator =' ';
			foreach($arr as $k=>$v){ 
				if($v!=''){
					$normal_search.= $separator.$k." ~* '".$v."'";
					$separator=" AND ";
				}
			}
		}
		else if($q!='' && sizeof($arr)==0){
			$normal_search.=" WHERE ".$search;
		}
		else{
			$normal_search.=" WHERE ".$search;
			foreach($arr as $k=>$v){
				if($v!=''){
					$normal_search.= ' AND '.$k." ~* '".$v."'";
				}
			}
		}
		
		$normal_search .= "ORDER BY redeem_time DESC";
		if ($length != "")
		{
			$normal_search.= " LIMIT $length OFFSET $offset"; 
		}
		$this->logToFirebug($normal_search);
		return $this->db->fetchAll($normal_search);
	}
	
	public function getPromoCount($q, $arr, $range, $channel_id = "")
	{
		$normal_search = "SELECT COUNT(a.offer_id) AS total
			FROM
			".$this->mtr."campaign_redeem a
			left outer join ".$this->mtr."ggi_user_email f on (a.user_id=f.user_id)
			left outer join ".$this->mtr."ggi_user_phone g on (a.user_id=g.user_id)
			left outer join ".$this->mtr."provider_offer b on (a.provider_id=b.provider_id and a.offer_id=b.offer_id and a.batch_id=b.batch_id)
			left outer join ".$this->mtr."provider_merchant h on (b.merchant_id=h.merchant_id)
			left outer join ".$this->mtr."channel_offer_campaign coc on(a.channel_id=coc.channel_id and a.provider_id=coc.provider_id and a.offer_id=coc.offer_id and a.batch_id=coc.batch_id and a.campaign_id=coc.campaign_id)
			LEFT OUTER JOIN ".$this->mtr."device_identifier e ON (a.device_identifier_id=e.id)
			LEFT OUTER JOIN ".$this->mtr."campaign_claim d ON (a.provider_id=d.provider_id and a.offer_id=d.offer_id and a.batch_id=d.batch_id and a.redeem_code=d.redeem_code)
			LEFT OUTER JOIN ".$this->mtr."provider_outlet c ON (d.outlet_id=c.outlet_id)
			";

		$add_channel = ($channel_id != "") ? "AND a.channel_id = ".$this->escape($channel_id) : "";
			
		$search="(f.email ~* '".$q."' OR
			g.phone_number ~* '".$q."' OR
			d.redeem_code ~* '".$q."' OR
			a.offer_id ~* '".$q."' OR
			b.name ~* '".$q."' OR
			h.name ~* '".$q."' OR
			TO_CHAR(coc.start_time, 'DD Mon YYYY') ~* '".$q."' OR
			TO_CHAR(coc.end_time, 'DD Mon YYYY') ~* '".$q."' OR
			e.type ~* '".$q."' OR
			e.name ~* '".$q."' OR
			e.manufactur ~* '".$q."' OR
			e.model ~* '".$q."' OR
			TO_CHAR(a.redeem_time, 'DD Mon YYYY') ~* '".$q."' OR
			TO_CHAR(d.claim_time, 'DD Mon YYYY') ~* '".$q."' OR
			c.outlet_id ~* '".$q."' OR
			c.name ~* '".$q."' OR
			c.address ~* '".$q."' )";
		$search .= $add_channel;
		
		$advanced = "";
		if($q=='' && sizeof($arr)==0){
			$normal_search.=" WHERE ".$search;
		}
		else if($q=='' && sizeof($arr)>0){
			$normal_search.=" WHERE ";
			$separator =' ';
			foreach($arr as $k=>$v){ 
				if($v!=''){
					$normal_search.= $separator.$k." ~* '".$v."'";
					$separator=" AND ";
				}
			}
		}
		else if($q!='' && sizeof($arr)==0){
			$normal_search.=" WHERE ".$search;
		}
		else{
			$normal_search.=" WHERE ".$search;
			foreach($arr as $k=>$v){
				if($v!=''){
					$normal_search.= ' AND '.$k." ~* '".$v."'";
				}
			}
		}

		$normal_search .= ($range['start_claim'] != "" && $range['end_claim'] != "" ) ? " AND EXTRACT(EPOCH FROM d.claim_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_claim']).") AND EXTRACT(EPOCH FROM d.claim_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_claim']).")" : "";
		$normal_search .= ($range['start_redeem'] != "" && $range['end_redeem'] != "" ) ? "AND EXTRACT(EPOCH FROM a.redeem_time) >= EXTRACT(EPOCH FROM TIMESTAMP WITH TIME ZONE ".$this->escape($range['start_redeem']).") AND EXTRACT(EPOCH FROM a.redeem_time) <= EXTRACT(EPOCH FROM  TIMESTAMP WITH TIME ZONE ".$this->escape($range['end_redeem']).")" : ""; 
		
		
		$row = $this->db->fetchRow($normal_search);
		//echo $normal_search;
		return $row->total;
	}
	
	public function getUserSearch($user_id){
		$search = "SELECT
					k.id as user_id,
					k.name as user_name,
					i.name as user_image,
					k.date_of_birth as user_dob,
					k.gender as user_gender,
					k.marital_status as user_marital,
					k.marital_kid_count as user_kid,
					k.holiday as user_religion,
					k.address as user_address,
					k.address_province as user_province,
					min(a.redeem_time)as first_redeem,
					max(a.redeem_time) as last_redeem,
					count(a.redeem_code) as total_redeem_user,
					t2.claim_type as first_redeem_type,
					t1.claim_type as last_redeem_type
					FROM ".$this->mtr."ggi_user k 
					LEFT OUTER JOIN static_file i ON (k.avatar_static_file_id=i.id)
					LEFT OUTER JOIN ".$this->mtr."ggi_user_email f ON (k.id=f.user_id)
					LEFT OUTER JOIN ".$this->mtr."campaign_redeem a ON (k.id=a.user_id)
					LEFT OUTER JOIN ".$this->mtr."provider_offer po ON (a.offer_id=po.offer_id AND a.batch_id=po.batch_id)
					LEFT OUTER JOIN 
					(
					select user_id, claim_type from ".$this->mtr."campaign_redeem cr join ".$this->mtr."provider_offer po on cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id
					where user_id=".$this->escape($user_id)."
					order by claim_type asc
					limit 1
					) as t2 on (t2.user_id=k.id)
					LEFT OUTER JOIN 
					(
						select user_id, claim_type
						from
						".$this->mtr."campaign_redeem cr join ".$this->mtr."provider_offer po on cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id
						where user_id=".$this->escape($user_id)."
						order by claim_type desc
						LIMIT 1
					) AS t1 ON (t1.user_id=k.id)
					WHERE k.id=".$this->escape($user_id)."
					group by
					k.id,
					k.name,
					i.name,
					k.date_of_birth,
					k.gender,
					k.marital_status,
					k.marital_kid_count,
					k.holiday,
					k.address,
					k.address_province, t2.claim_type,
					t1.claim_type";
		$this->logToFirebug($search);
		
		return $this->db->fetchAll($search);
	}
	
	public function getPromoSearch($promo_id,$batch_id,$campaign_id, $provider_id, $channel_id){
		$search = " select
					coc.channel_id,
					coc.provider_id,
					coc.offer_id as promo_id,
					coc.batch_id,
					coc.campaign_id,
					coc.name as promo_name,
					coc.tnc as tnc,
					TO_CHAR(coc.start_time,'DD Mon YYYY HH24:MI') as start_time,
					TO_CHAR(coc.end_time,'DD Mon YYYY HH24:MI') as end_time,
					i.name as promo_image,
					j.model as eligible_device,
					dp.product_name,
					cu.name as pm_name,
					(select count(offer_id) from ".$this->mtr."campaign_redeem_code where channel_id=coc.channel_id and provider_id=coc.provider_id and offer_id=coc.offer_id and batch_id=coc.batch_id and campaign_id=coc.campaign_id) as total_coupon,
					(select count(offer_id) from ".$this->mtr."campaign_redeem where channel_id=coc.channel_id and provider_id=coc.provider_id and offer_id=coc.offer_id and batch_id=coc.batch_id and campaign_id=coc.campaign_id) as total_redeem,
					(select count(redeem_code) from
						(select
							cr.provider_id, channel_id, cr.offer_id,cr. batch_id, campaign_id, device_identifier_id, user_id, redeem_time,
							cc.redeem_code, claim_time
						 from
							".$this->mtr."campaign_redeem cr
							left outer join ".$this->mtr."campaign_claim cc on cr.redeem_code=cc.redeem_code
						)as t1
						where channel_id=coc.channel_id and provider_id=coc.provider_id and offer_id=coc.offer_id and batch_id=coc.batch_id and campaign_id=coc.campaign_id)
					as total_claim
					from
					(
						SELECT channel_id, provider_id, offer_id, batch_id, campaign_id, name, tnc, start_time, end_time
						FROM ".$this->mtr."channel_offer_campaign
						WHERE channel_id=".$this->escape($channel_id)." and provider_id=".$this->escape($provider_id)." and offer_id=".$this->escape($promo_id)." and batch_id=".$this->escape($batch_id)." and campaign_id=".$this->escape($campaign_id)."
					) coc
					left outer join ".$this->mtr."provider_offer b on (coc.provider_id=b.provider_id and coc.offer_id=b.offer_id and coc.batch_id=b.batch_id)
					left outer JOIN static_file i ON (b.banner_static_file_id=i.id)
					left outer join ".$this->mtr."campaign_eligible_device_model j ON (coc.provider_id=j.provider_id and coc.channel_id=j.channel_id and coc.offer_id=j.offer_id and coc.batch_id=j.batch_id and coc.campaign_id=j.campaign_id)
					left outer join device_product dp ON (j.model ~* dp.code)
					left outer join cms_user_distribution_channel cudc ON (coc.channel_id=cudc.channel_id)
					left outer join cms_user cu ON (cudc.user_id=cu.id)";
					
		$this->logToFirebug($search);
		return $this->db->fetchAll($search);
	}
	
	public function getAllReview()
	{
		$sql = " SELECT
				guf.last_update_time as review_date,
				guf.user_id,
				gu.name,
				gie.email,
				(guf.rating * 5) as star_rating,
				guf.message as review_text

				FROM ggi_user_feedback guf
				LEFT OUTER JOIN ggi_user gu ON (guf.user_id=gu.id)
				LEFT OUTER JOIN ggi_user_email gie ON (guf.user_id=gie.user_id) ORDER BY review_date DESC ";
		return $this->db->query($sql);
	}
	
	public function getMoreReview($search, $dataToSearch, $offset = 0, $set = 25, $start_date = "", $end_date = "")
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
					"WHERE (gu.name ~* '".$search."' or 
							gie.email ~* '".$search."' or 
							(guf.rating * 5)::text ~* '".$search."' or 
							guf.message ~* '".$search."' or 
							TO_CHAR(guf.last_update_time, 'DD Mon YYYY HH:MI') ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND guf.last_update_time BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".floor($offset);
		
		$sql = " SELECT DISTINCT guf.id,
				TO_CHAR(guf.last_update_time,'DD Mon YYYY HH:MI') as review_date,
				guf.last_update_time,
				guf.user_id,
				gu.name,
				gie.email,
				(guf.rating * 5) as star_rating,
				guf.message as review_text
				FROM ".$this->mtr."ggi_user_feedback guf
				LEFT OUTER JOIN ".$this->mtr."ggi_user gu ON (guf.user_id=gu.id)
				LEFT OUTER JOIN ".$this->mtr."ggi_user_email gie ON (guf.user_id=gie.user_id) 
				".$advanced."
				ORDER BY guf.last_update_time DESC 
				".$limit;
		
		return $this->db->fetchAll($sql);
	}
	
	public function getReviewCount($search, $dataToSearch, $start_date, $end_date)
	{
		$where = array();
		$advanced = "";
		$temp = 0;
		
		foreach ($dataToSearch as $key => $value) : 
			if($value!="")
			{
				array_push($where, ($key." LIKE ".$this->escape("%".$value."%")));
				$temp++;
			}
		endforeach;
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : 
							"WHERE (gu.name ~* '".$search."' or 
							gie.email ~* '".$search."' or 
							(guf.rating * 5)::text ~* '".$search."' or 
							guf.message ~* '".$search."' or 
							TO_CHAR(guf.last_update_time, 'DD Mon YYYY HH:MI') ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND guf.last_update_time BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".floor($offset);
		
		$sql = " SELECT COUNT(guf.last_update_time) AS total FROM ".$this->mtr."ggi_user_feedback guf
				LEFT OUTER JOIN ".$this->mtr."ggi_user gu ON (guf.user_id=gu.id)
				LEFT OUTER JOIN ".$this->mtr."ggi_user_email gie ON (guf.user_id=gie.user_id) 
				".$advanced;
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
	
	public function getAllReviewBy($col, $method)
	{
		$sql = " SELECT
				DATE_FORMAT(guf.last_update_time, \"%e-%b-%Y\") as review_date,
				guf.user_id,
				gu.name,
				gie.email,
				guf.rating as star_rating,
				guf.message as review_text

				FROM ggi_user_feedback guf
				LEFT OUTER JOIN ggi_user gu ON (guf.user_id=gu.id)
				LEFT OUTER JOIN ggi_user_email gie ON (guf.user_id=gie.user_id)";
		
		return $this->db->fetchAll($sql);
	}
	
	// Basic Function
	public function insert($dataToInsert)
	{
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete()
	{
	}
	
	// Custom Function place here --
	public function getCustomer($q, $arr, $offset , $length)
	{
		$normal_search ="SELECT gi.name, ge.email, di.name as imei, CONCAT(CONCAT(di.manufactur,' '),di.model) AS device_model,  'S Gift' as app,'active' AS status FROM ggi_user as gi 
						LEFT JOIN ggi_user_email AS ge ON gi.id = ge.user_id
						LEFT JOIN device_identifier AS di ON gi.id = di.id";
		
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
			if (strcasecmp($status, "disabled") == 0) {
				$sql = "DELETE FROM ggi_app_session WHERE device_identifier_id IN (SELECT id FROM device_identifier WHERE name = ".$this->escape($device_identifier).")";
			} else if (strcasecmp($status, "enabled") == 0) {
				$sql = "INSERT INTO ggi_app_session (device_identifier_id, creation_time, last_update_time, time_zone, gcm_reg_id )
						SELECT id, NOW(), NOW(), 'Asia/Jakarta', 'gcm' FROM device_identifier WHERE name = ".$this->escape($device_identifier);
			}
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
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : 
					" WHERE ( gi.name ~* '".$search."' OR
							  ge.email ~* '".$search."' OR
						      di.name ~* '".$search."' OR
						      di.model ~* '".$search."' OR
						      'S Gift' ~* '".$search."' OR
						      (
								CASE 
								WHEN gas.name IS NOT NULL THEN 'active'
								ELSE 'suspended'
								END
							  ) ~* '".$search."'
						    )";
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".floor($offset);
		
		$normal_search = "SELECT gi.name, ge.email, di.name as imei, 'imei' AS type, CONCAT(CONCAT(di.manufactur,' '),di.model) AS device_model, 'S Gift' as app,
						   (
						  		CASE 
						  		WHEN gas.device_identifier_id::text <> '' AND gas.device_identifier_id IS NOT NULL THEN 'active'
						  		ELSE 'suspended'
						  		END
						  ) AS status,
						  product_name, price, (
						  SELECT TO_CHAR(min(redeem_time), 'DD Mon YYYY HH24:MI') FROM campaign_redeem WHERE user_id = gi.id
						  ) as first_download_date, (
						  SELECT TO_CHAR(max(redeem_time), 'DD Mon YYYY HH24:MI') FROM campaign_redeem WHERE user_id = gi.id
						  ) as last_download_date
						  FROM ggi_user as gi 
						  LEFT JOIN ggi_user_email AS ge ON gi.id = ge.user_id
						  LEFT JOIN device_identifier AS di ON gi.id = di.id
						  LEFT JOIN device_product AS dm ON di.model ~* dm.code
						  LEFT JOIN msdevice_price AS md ON dm.code = md.device_model
						  LEFT JOIN ggi_app_session AS gas ON di.id = gas.device_identifier_id
						  ".$advanced." 
						  ORDER BY (
						  		CASE 
						  		WHEN gas.name IS NOT NULL THEN 'active'
						  		ELSE 'suspended'
						  		END
						  ) DESC
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
					" WHERE ( gi.name LIKE '%".$search."%' OR
							  ge.email LIKE '%".$search."%' OR
						      di.name LIKE '%".$search."%' OR
						      di.model LIKE '%".$search."%' OR
						      'S Gift' LIKE '%".$search."%' OR
						      (
								CASE 
								WHEN gas.name IS NOT NULL THEN 'active'
								ELSE 'suspended'
								END
							  ) LIKE '%".$search."%'
						    )";
		
		$sql = "SELECT COUNT(gi.name) AS total
						  FROM ggi_user as gi 
						  LEFT JOIN ggi_user_email AS ge ON gi.id = ge.user_id
						  LEFT JOIN device_identifier AS di ON gi.id = di.id
						  LEFT JOIN ggi_app_session AS gas ON di.id = gas.device_identifier_id
						  ".$advanced;
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}

	public function changeEmail($device_identifier, $newemail)
	{
		$sql = "UPDATE ggi_user_email SET email = ".$this->escape($newemail)." WHERE user_id IN (
						SELECT id FROM device_identifier
						WHERE name = ".$this->escape($device_identifier)."
				)";
		return $this->db->query($sql);
	}
	
	// ----------------------------------------------------------------------------
	// USER BASE
	// ----------------------------------------------------------------------------
	public function getRegUser(){
		
		$sql = $this->db->select()
						->from(array("di" => $this->mtr."device_identifier"), array("total" => "COUNT(*)"))
						->joinLeft(array("gasu" => $this->mtr."ggi_app_session_user"), "di.id=gasu.device_identifier_id", array())
						->joinLeft(array("dseg" => "device_segment3"), "di.model=dseg.device_model", array("segment"))
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
	
	public function getActiveUser($segment='', $interval=''){
		$where_time='';
		$where_segment='';
		if($segment=='Other'){
			$where_time=" where redeem_time BETWEEN NOW() - INTERVAL '".$interval." DAY' AND NOW()";
			$where_segment=" where segment IS NULL";
		}
		else if($segment!=''){
			$where_time=" where redeem_time BETWEEN NOW() - INTERVAL '".$interval." DAY' AND NOW()";
			$where_segment=" where segment in(".$this->escape($segment).")";
		}

		else{
			$where_time=" where redeem_time BETWEEN NOW() - INTERVAL '".$interval." DAY' AND NOW()";
			$where_segment=" where segment in('Mid' , 'Premium', 'Tab', 'Entry')";			
		} 
		$sql ="select count(slr2.session_id) as total
					from
					(
						select distinct session_id
						from
						(
							select distinct session_id, redeem_job_id
							from
							server_log_redeem3
							".$where_time."
						) as slr
						left outer join ".$this->mtr."ggi_app_session gas on slr.session_id=gas.name
						left outer join ".$this->mtr."device_identifier di on gas.device_identifier_id=di.id
						left outer join device_segment3 dseg on di.model=dseg.device_model
						".$where_segment."
					)as slr2";
		$row = $this->db->fetchRow($sql);
		return $row->total;			
	}
	
	public function getAllActiveUser($type = "monthly"){
		$sql = $this->db->select()
						->from("ggi_active_users", array("total_".$type => "ggi_active_users_total_".$type,
														 "premium_".$type => "ggi_active_users_premium_".$type,
														 "mid_".$type => "ggi_active_users_mid_".$type,
														 "entry_".$type => "ggi_active_users_entry_".$type, 
														 "tab_".$type => "ggi_active_users_tab_".$type,
														 "other_".$type => "ggi_active_users_other_".$type))
						->order("date DESC")
						->limit(1,0);
		$row = $this->db->fetchRow($sql);
		return $row;		
	}	
	
	// ----------------------------------------------------------------------------

	public function getTotalPromo($segment='', $interval='') {
		$where=" where channel_id in(1, 2, 3) and start_time BETWEEN NOW() - INTERVAL '".$interval." DAY' AND NOW()";
		$sql='SELECT channel_id, count(*) as total_promo
			FROM '.$this->mtr.'channel_offer_campaign coc
		'.$where.' group by channel_id';
		
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$r->channel_id = ($r->channel_id == "") ? "all" : $r->channel_id;
			$data[$r->channel_id] = $r->total_promo;
		endforeach;
		return $data;	
	}	

	public function getTotalVoucher($segment='', $interval=''){
		$end_date = date('Y-m-d');
		$start_date = date_create($end_date);
		date_add($start_date, date_interval_create_from_date_string("-".$interval." days"));
		$start_date = date_format($start_date, 'Y-m-d');
		$where=" where crc.channel_id in(1, 2, 3)";
		$sql='SELECT channel_id, COUNT(*) as total_voucher
			FROM 
			(
				SELECT channel_id, redeem_code FROM 
				'.$this->mtr.'campaign_redeem_code crc
				'.$where.' and exists (
					select channel_id, provider_id, offer_id, batch_id, campaign_id from channel_offer_campaign 
					where start_time  BETWEEN '.$this->escape($start_date).' AND '.$this->escape($end_date).' 
					and crc.channel_id=channel_id and crc.provider_id=provider_id 
					and crc.offer_id=offer_id and crc.batch_id=batch_id and crc.campaign_id=campaign_id
				)
			) as t1
			group by channel_id';
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$r->channel_id = ($r->channel_id == "") ? "all" : $r->channel_id;
			$data[$r->channel_id] = $r->total_voucher;
		endforeach;
		return $data;
	}

	public function getTotalRedeem($segment='', $interval=''){
		$where_time = "where redeem_time BETWEEN NOW() - INTERVAL '".$interval." DAY' AND NOW()";
		$where_segment = "WHERE segment in ('Premium', 'Entry', 'Mid', 'Tab') OR segment is null";
		$sql="SELECT segment, COUNT(*) AS total_redeem
				FROM
				(
					SELECT DISTINCT device_identifier_id
					FROM ".$this->mtr."campaign_redeem
					".$where_time."
				)AS cr
				LEFT OUTER JOIN ".$this->mtr."device_identifier di ON cr.device_identifier_id=di.id
				LEFT OUTER JOIN device_segment3 dseg ON di.model=dseg.device_model
				".$where_segment."
				GROUP BY segment
		";
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$r->segment = ($r->segment == "") ? "other" : $r->segment;
			$data[$r->segment] = $r->total_redeem;
		endforeach;
		return $data;
	}

	public function getTotalClaim($segment='', $interval='', $group = false){	
		$where='';
		$end_date = date('Y-m-d');
		$start_date = date_create($end_date);
		date_add($start_date, date_interval_create_from_date_string("-".$interval." days"));
		$start_date = date_format($start_date, 'Y-m-d');
		
		$where = " WHERE segment in ('Premium', 'Entry', 'Mid', 'Tab') OR segment is null";
		$group_col = ($group == true) ? "segment," : "";
		$grouping = ($group == true) ? "GROUP BY segment" : "";
		$sql="
		SELECT ".$group_col." COUNT(t1.device_identifier_id) AS total_claim
		FROM
		(
			SELECT DISTINCT device_identifier_id,di.model
			FROM
			(
				SELECT redeem_code, provider_id, offer_id, batch_id
				FROM ".$this->mtr."campaign_claim
				WHERE claim_time  BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date)."
			)AS cc
			JOIN ".$this->mtr."campaign_redeem cr ON cc.redeem_code=cr.redeem_code AND cc.provider_id=cr.provider_id AND cc.offer_id=cr.offer_id AND cc.batch_id=cr.batch_id
			LEFT OUTER JOIN ".$this->mtr."device_identifier di ON cr.device_identifier_id=di.id
		)AS t1
		LEFT OUTER JOIN device_segment3 dseg ON t1.model=dseg.device_model
		".$where."
		".$grouping;
		
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach ($row as $r) : 
			$r->segment = ($r->segment == "") ? "other" : $r->segment;
			$data[$r->segment] = $r->total_claim;
		endforeach;
		return $data;	
	}

	public function getActiveUserTotal() {
		$sql= $this->db->select()
					   ->from("ggi_active_users", array("date","ggi_active_users_total_daily" => "COALESCE(ggi_active_users_total_daily,0)"))
					   ->order("date DESC");
		$row = $this->db->fetchAll($sql);
		return $row;		
	}
	
	public function getActiveUserPremium() {
		$sql = $this->db->select()
						->from("ggi_active_users", array("date", "ggi_active_users_premium_daily" => "COALESCE(ggi_active_users_premium_daily, 0)"))
						->order("date DESC");
		$row = $this->db->fetchAll($sql);
		return $row;
	}
	
	public function getActiveUserMid() {
		$sql = $this->db->select()
						->from("ggi_active_users", array("date", "ggi_active_users_mid_daily" => "COALESCE(ggi_active_users_mid_daily, 0)"))
						->order("date DESC");
		$row = $this->db->fetchAll($sql);
		return $row;		
	}
	
	public function getActiveUserEntry(){
		$sql = $this->db->select()
						->from("ggi_active_users", array("date", "ggi_active_users_entry_daily" => "COALESCE(ggi_active_users_entry_daily, 0)"))
						->order("date DESC");
		$row = $this->db->fetchAll($sql);
		return $row;
	}		
	
	public function getActiveUserTab(){
		$sql = $this->db->select()
						->from("ggi_active_users", array("date", "ggi_active_users_tab_daily" => "COALESCE(ggi_active_users_tab_daily, 0)"))
						->order("date DESC");
		$row = $this->db->fetchAll($sql);
		return $row;		
	}
	
	public function getActiveUserForAll(){	
		$sql = $this->db->select()
						->from("ggi_active_users", array("datelabel" => "TO_CHAR(date, 'dd Mon')",
														 "date",
														 "total" => "COALESCE(ggi_active_users_total_daily,0)",
														 "premium" => "COALESCE(ggi_active_users_premium_daily,0)",
														 "mid" => "COALESCE(ggi_active_users_mid_daily,0)",
														 "entry" => "COALESCE(ggi_active_users_entry_daily,0)",
														 "tab" => "COALESCE(ggi_active_users_tab_daily,0)"))
						->order("date ASC");
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
	public function getActiveUserForWeekly(){	
		$sql="SELECT date_trunc('week', date) as datelabel, MAX(ggi_active_users_total_daily) as total, MAX(ggi_active_users_premium_daily) as premium, 
		MAX(ggi_active_users_mid_daily) as mid, MAX(ggi_active_users_entry_daily) as entry, MAX(ggi_active_users_tab_daily) as tab  FROM ggi_active_users_test GROUP BY 1 ORDER BY 1";
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
	public function getActiveUserForMonthly(){	
		$sql="SELECT date_trunc('month', date) as datelabel, MAX(ggi_active_users_total_daily) as total, MAX(ggi_active_users_premium_daily) as premium, 
MAX(ggi_active_users_mid_daily) as mid, MAX(ggi_active_users_entry_daily) as entry, MAX(ggi_active_users_tab_daily) as tab  FROM ggi_active_users_test GROUP BY 1 ORDER BY 1";
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
	
	public function getMinutesClaim($curdate, $start_time='', $end_time='', $promo_id='') {
		$where='';
		if($start_time!='' && $end_time!=''){
			$where = " AND TO_CHAR(claim_time, 'HH24:MI') > '".$start_time."' AND TO_CHAR(claim_time, 'HH24:MI') < '".$end_time."' ";
		}
		if($promo_id!=''){
			$promo = explode('-', $promo_id);
			$where .= " AND channel_id=".$promo[0]." AND cc.provider_id='".$promo[1]."' AND cc.offer_id='".$promo[2]."' AND cc.batch_id='".$promo[3]."' AND campaign_id='".$promo[4]."'";
		}
		$sql=" SELECT COUNT(cc.redeem_code) as total, TO_CHAR(claim_time, 'YYYY-MM-DD HH24:MI') AS curdate, TO_CHAR(claim_time, 'HH24:MI') AS curtime
				FROM mtr2_campaign_claim cc
				JOIN mtr2_campaign_redeem cr ON cc.redeem_code=cr.redeem_code AND cc.provider_id=cr.provider_id AND cc.offer_id=cr.offer_id AND cc.batch_id=cr.batch_id
				WHERE claim_time::DATE = '".$curdate."'".$where."
				GROUP BY curdate, curtime
				ORDER BY curdate ASC, curtime ASC";
		return $sql;
	}	
	
	public function getMinutesRedeem($curdate, $start_time='', $end_time='', $promo_id=''){
		$where='';
		if($start_time!='' && $end_time!=''){
			$where = " AND TO_CHAR(redeem_time, 'HH24:MI') > '".$start_time."' AND TO_CHAR(redeem_time, 'HH24:MI') < '".$end_time."' ";
		}
		if($promo_id!=''){
			$promo = explode('-', $promo_id);
			$where .= " AND channel_id=".$promo[0]." AND provider_id='".$promo[1]."' AND offer_id='".$promo[2]."' AND batch_id='".$promo[3]."' AND campaign_id='".$promo[4]."'";
		}
		$sql=" SELECT COUNT(redeem_code)  as total, redeem_time::DATE AS curdate, TO_CHAR(redeem_time, 'HH24:MI') AS curtime
				FROM mtr2_campaign_redeem
				WHERE redeem_time::DATE = '".$curdate."'".$where."
				GROUP BY curdate, curtime
				ORDER BY curdate DESC, curtime ASC";
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
	
	public function getWeekPromo($type){
		$sql = " SELECT date_trunc('week', ".$type."_time) AS \"time\" , count(*) AS \"total\"
					FROM mtr2_campaign_".$type."
					WHERE ".$type."_time > now() - interval '11 week'
					GROUP BY 1
					ORDER BY 1;";
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
	
	public function getMonthPromo($type){
		$sql=" SELECT date_trunc('month', ".$type."_time) AS \"time\" , count(*) AS \"total\"
					FROM mtr2_campaign_".$type."
					WHERE ".$type."_time > now() - interval '1 year'
					GROUP BY 1
					ORDER BY 1;";
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
	
	public function getCurrentPromo(){
		$sql = "select
				CONCAT (channel_id,'-', provider_id,'-', offer_id,'-', batch_id,'-', campaign_id) as promo_id,name
				from mtr2_channel_offer_campaign
				where start_time::date = current_date";
		$row = $this->db->fetchAll($sql);
		return $row;	
	}
}