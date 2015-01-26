<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Serverlog extends Db_PDO
{
	var $db;
	var $mtr = "mtr2_";
	public function __construct($dbconfig)
	{
		$this->primary_column = "";
		$this->table = "";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	public function getRedeemLog($search, $datasearch,$limit=100, $offset=0, $start_redeem = "", $end_redeem = "")
	{
		$where = array();
		$temp=0;
		$advanced="";
		
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (pm.name ~* '".$search."' or coc.name ~* '".$search."' or email ~* '".$search."' or di.name ~* '".$search."' or di.model ~* '".$search."' or result_message ~* '".$search."')";
		
		if ($start_redeem != "" && $end_redeem != "") {
			$advanced .= " AND redeem_time BETWEEN ".$this->escape($start_redeem)." AND ".$this->escape($end_redeem);
		}
		
		$normal_search = "
		select
			TO_CHAR(redeem_time, 'YYYY-MM-DD HH24:MI') AS redeem_time,
			di.name as imei_or_mac,
			di.type,
			gu.name,
			email,
			CONCAT (slr.channel_id,'-', slr.provider_id,'-', slr.offer_id,'-', slr.batch_id,'-', slr.campaign_id) as promo_id,
			coc.name as promo_name,
			pm.name as merchant,
			claim_type,
			di.model,
			slr.redeem_job_id,
			result_message as status
		from
		server_log_redeem3 slr
		left outer join ".$this->mtr."ggi_app_session gas on slr.session_id=gas.name
		left outer join ".$this->mtr."ggi_app_session_user gasu on gas.device_identifier_id=gasu.device_identifier_id
		left outer join ".$this->mtr."ggi_user gu on gasu.user_id=gu.id
		left outer join ".$this->mtr."ggi_user_email gue on gasu.user_id=gue.user_id
		left outer join ".$this->mtr."device_identifier di on gas.device_identifier_id=di.id
		left outer join ".$this->mtr."channel_offer_campaign coc on slr.channel_id::text = coc.channel_id::text and slr.provider_id::text = coc.provider_id::text and slr.offer_id::text=coc.offer_id::text and slr.batch_id::text=coc.batch_id::text and slr.campaign_id::text = coc.campaign_id::text
		left outer join ".$this->mtr."provider_offer po on slr.provider_id::text = po.provider_id::text and slr.offer_id::text=po.offer_id::text and slr.batch_id::text=po.batch_id::text
		left outer join ".$this->mtr."provider_merchant pm on po.merchant_id=pm.merchant_id
		".$advanced."
		 ORDER BY redeem_time DESC ";
		 
		$normal_search .="
		limit $limit
		offset $offset
		";
		
		$row = $this->db->fetchAll($normal_search);
		
		$dp = $this->getDeviceFragment();
		
		$data = array();
		foreach($row as $r) : 
			$r->product_name = $dp[$r->model]->product_name;
			array_push($data, $r);
		endforeach;
		
		return $data;
	}
	
	public function getDeviceFragment()
	{
		$sql = "SELECT id, product_name, code FROM device_product";
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) :
			$data[$r->code] = $r;
		endforeach;
		return $data;
	}
	
	public function getRedeemCountLog($search, $datasearch, $start_redeem = "", $end_redeem = "") 
	{
		$where = array();
		$temp=0;
		$advanced="";
		
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (pm.name ~* '".$search."' or coc.name ~* '".$search."' or email ~* '".$search."' or di.name ~* '".$search."' or di.model ~* '".$search."' or result_message ~* '".$search."')";
		
		if ($start_redeem != "" && $end_redeem != "") {
			$advanced .= " AND redeem_time BETWEEN ".$this->escape($start_redeem)." AND ".$this->escape($end_redeem);
		}
		
		$normal_search = "
		select
			COUNT(*) AS jumlah
		from
		server_log_redeem3 slr
		left outer join ".$this->mtr."ggi_app_session gas on slr.session_id=gas.name
		left outer join ".$this->mtr."ggi_app_session_user gasu on gas.device_identifier_id=gasu.device_identifier_id
		left outer join ".$this->mtr."ggi_user gu on gasu.user_id=gu.id
		left outer join ".$this->mtr."ggi_user_email gue on gasu.user_id=gue.user_id
		left outer join ".$this->mtr."device_identifier di on gas.device_identifier_id=di.id
		left outer join ".$this->mtr."channel_offer_campaign coc on slr.channel_id::text = coc.channel_id::text and slr.provider_id::text = coc.provider_id::text and slr.offer_id::text=coc.offer_id::text and slr.batch_id::text=coc.batch_id::text and slr.campaign_id::text = coc.campaign_id::text
		left outer join ".$this->mtr."provider_offer po on slr.provider_id::text = po.provider_id::text and slr.offer_id::text=po.offer_id::text and slr.batch_id::text=po.batch_id::text
		left outer join ".$this->mtr."provider_merchant pm on po.merchant_id=pm.merchant_id
		".$advanced;
		 		
		$row = $this->db->fetchRow($normal_search);
		return $row->jumlah;
	}
	
	public function getClaimLog($search, $datasearch, $limit=100, $offset=0, $start_claim = "", $end_claim = "")
	{
		$where = array();
		$temp=0;
		$advanced="";
		
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (pm.name ~* '".$search."' or coc.name ~* '".$search."' or email ~* '".$search."' or di.name ~* '".$search."' or di.model ~* '".$search."' or result_message ~* '".$search."' or slc.redeem_code ~* '".$search."')";
		
		if ($start_claim != "" && $end_claim != "") {
			$advanced .= " AND slc.claim_time BETWEEN ".$this->escape($start_claim)." AND ".$this->escape($end_claim);
		}
		
		$normal_search = "
		select
			TO_CHAR(slc.claim_time,'YYYY-MM-DD HH24:MI') AS claim_time,
			di.name as imei_or_mac,
			di.type,
			gu.name,
			email,
			CONCAT (slc.channel_id,'-', slc.provider_id,'-', slc.offer_id,'-', slc.batch_id,'-', slc.campaign_id) as promo_id,
			coc.name as promo_name,
			pm.name as merchant,
			slc.redeem_code,
			claim_type,
			di.model,
			result_message as status
		from
		server_log_claim2 slc
		left outer join ".$this->mtr."ggi_app_session gas on slc.session_id=gas.name
		left outer join ".$this->mtr."ggi_app_session_user gasu on gas.device_identifier_id=gasu.device_identifier_id
		left outer join ".$this->mtr."ggi_user gu on gasu.user_id=gu.id
		left outer join ".$this->mtr."ggi_user_email gue on gasu.user_id=gue.user_id
		left outer join ".$this->mtr."device_identifier di on gas.device_identifier_id=di.id
		left outer join ".$this->mtr."channel_offer_campaign coc on slc.channel_id::text=coc.channel_id::text and slc.provider_id::text=coc.provider_id::text and slc.offer_id::text=coc.offer_id::text and slc.batch_id::text=coc.batch_id::text and slc.campaign_id::text=coc.campaign_id::text
		left outer join ".$this->mtr."provider_offer po on slc.provider_id::text=po.provider_id::text and slc.offer_id::text=po.offer_id::text and slc.batch_id::text=po.batch_id::text
		left outer join ".$this->mtr."provider_merchant pm on po.merchant_id=pm.merchant_id
		left outer join ".$this->mtr."campaign_claim cl on slc.redeem_code::text=cl.redeem_code::text
		".$advanced."
		ORDER BY claim_time DESC
		limit $limit
		offset $offset
		";
		
		$row = $this->db->fetchAll($normal_search);
		
		$dp = $this->getDeviceFragment();
		
		$data = array();
		foreach($row as $r) : 
			$r->product_name = $dp[$r->model]->product_name;
			array_push($data, $r);
		endforeach;

		return $data;
	}
	
	public function getClaimCountLog($search, $datasearch, $start_claim = "", $end_claim = "")
	{
		$where = array();
		$temp=0;
		$advanced="";
		
		foreach($datasearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}	
		$advanced = ($temp>0 ) ? "WHERE (".implode(" AND ", $where).")" : "WHERE (pm.name ~* '".$search."' or coc.name ~* '".$search."' or email ~* '".$search."' or di.name ~* '".$search."' or di.model ~* '".$search."' or result_message ~* '".$search."' or slc.redeem_code ~* '".$search."')";
		
		if ($start_claim != "" && $end_claim != "") {
			$advanced .= " AND slc.claim_time BETWEEN ".$this->escape($start_claim)." AND ".$this->escape($end_claim);
		}
		
		$normal_search = "
		select
		COUNT(*) AS jumlah
		from
		server_log_claim2 slc
		left outer join ".$this->mtr."ggi_app_session gas on slc.session_id=gas.name
		left outer join ".$this->mtr."ggi_app_session_user gasu on gas.device_identifier_id=gasu.device_identifier_id
		left outer join ".$this->mtr."ggi_user gu on gasu.user_id=gu.id
		left outer join ".$this->mtr."ggi_user_email gue on gasu.user_id=gue.user_id
		left outer join ".$this->mtr."device_identifier di on gas.device_identifier_id=di.id
		left outer join ".$this->mtr."channel_offer_campaign coc on slc.channel_id::text=coc.channel_id::text and slc.provider_id::text=coc.provider_id::text and slc.offer_id::text=coc.offer_id::text and slc.batch_id::text=coc.batch_id::text and slc.campaign_id::text=coc.campaign_id::text
		left outer join ".$this->mtr."provider_offer po on slc.provider_id::text=po.provider_id::text and slc.offer_id::text=po.offer_id::text and slc.batch_id::text=po.batch_id::text
		left outer join ".$this->mtr."provider_merchant pm on po.merchant_id=pm.merchant_id
		left outer join ".$this->mtr."campaign_claim cl on slc.redeem_code=cl.redeem_code
		".$advanced;
		
		$row = $this->db->fetchRow($normal_search);
		return $row->jumlah;
	}
}