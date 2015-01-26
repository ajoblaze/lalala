<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Product extends Db_PDO
{
	var $db;
	var $mtr = "mtr2_";
	public function __construct($dbconfig)
	{
		$this->primary_column  = "";
		$this->table = "";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}

	public function update($dataToEdit)
	{
	}
	
	public function delete()
	{
	}
	
	// Custom Function place here -- 
	
	public function getCountMerchantPromoAmount($q, $datasearch){
		$where = "";
		
		$sql = $this->db->select()
						->from(array("pm" => $this->mtr."provider_merchant"), array("Jumlah" => "COUNT(*)"));
				
		if($q != '' && $q!=null){
			$sql.=" WHERE pm.name ~* ".$this->escape($q);
		}
		else if($datasearch != '' && $datasearch!=null){
			$search_merchant= false;
			if($datasearch["merchant_name"] !=null){
				$sql.=" WHERE pm.name ~* ".$this->escape($datasearch["merchant_name"]);
				$search_merchant = true;
			}
		}
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function getMerchantPromoAmount($q, $datasearch, $offset=0, $limit = 100){
		$where = "";
		
		if(count($datasearch["segment"])!=0){
			$where = " AND channel_id in(";
			$coma = "";
			foreach($datasearch["segment"] as $d){
				$where.=$coma." $d";
				$coma=",";
			}
			
			$where .=")";
		}
		
		$sql = "SELECT
				pm.merchant_id, pm.name as merchant FROM ".$this->mtr."provider_merchant pm ";
				
		if($q != '' && $q!=null){
			$sql.=" WHERE pm.name ~* ".$this->escape($q);
		}
		else if($datasearch != '' && $datasearch!=null){
			$search_merchant= false;
			if($datasearch["merchant_name"] !=null){
				$sql.=" WHERE pm.name ~* ".$this->escape($datasearch["merchant_name"]);
				$search_merchant = true;
			}
		}
		
		$sql .= " ORDER BY pm.name ASC";
		if($limit!='all'){
			$sql.=" offset $offset limit $limit ";
		}
		$row = $this->db->fetchAll($sql);
		
		$jmlpromo = $this->getJmlPromoFragment($where);
		$jmlvoucher = $this->getJmlVoucherFragment($where);
		$jmlredeem = $this->getJmlRedeemFragment($where);
		$jmlclaim = $this->getJmlClaimFragment($where);
		$jmlavg = $this->getAverageFragment();
		
		$data = array();
		foreach ($row as $r) : 
			$r->jumlah_promo = $jmlpromo[$r->merchant_id]->jumlah;
			$r->jumlah_voucher = $jmlvoucher[$r->merchant_id]->jumlah;
			$r->jumlah_redeem = $jmlredeem[$r->merchant_id]->jumlah;
			$r->jumlah_claim = $jmlclaim[$r->merchant_id]->jumlah;
			$r->average_minute = $jmlavg[$r->merchant_id]->average_minutes;
			array_push($data, $r);
		endforeach;
		
		return $row;
	}

	public function getAverageFragment()
	{
		$sql = $this->db->select()
						->from("temp_average_minutes", array("merchant_id", "name", "average_minutes"));
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->merchant_id] = $r;
		endforeach;
		return $data;
	}
	
	public function getJmlPromoFragment($where, $flag = false, $merchant_id = "")
	{
		$sql = "";
		if ($flag == true) {
			$sql = $this->db->select()
							->from(array("coc" => $this->mtr."channel_offer_campaign"), array("jumlah" => "COUNT(*)" , 
																						"promo_id" => "CONCAT (coc.channel_id,'-',coc.provider_id,'-',coc.offer_id,'-',coc.batch_id,'-',coc.campaign_id)"))
							->joinLeft(array("po" => $this->mtr."provider_offer"), "coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id", array("merchant_id"))
							->where("po.merchant_id ~* ? ".$where, $this->validate($merchant_id))
							->group(array("po.merchant_id","promo_id"));
		} else {
			$sql = $this->db->select()
							->from(array("coc" => $this->mtr."channel_offer_campaign"), array("jumlah" => "COUNT(*)"))
							->joinLeft(array("po" => $this->mtr."provider_offer"), "coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id", array("merchant_id"))
							->where("po.merchant_id ~* ? ".$where, $this->validate($merchant_id))
							->group(array("po.merchant_id"));
		}
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) :
			if ($flag == true) {
				$data[$r->promo_id] = $r;
			} else {
				$data[$r->merchant_id] = $r;
			}
		endforeach;
		return $data;
	}
	
	public function getJmlVoucherFragment($where, $flag = false, $merchant_id = "")
	{
		$sql = "";
		if ($flag == true) {
			$sql = $this->db->select()
							->from(array("crc" => $this->mtr."campaign_redeem_code"), array("jumlah" => "COUNT(*)",
																					"channel_id","provider_id","offer_id","batch_id","campaign_id"))
							->joinLeft(array("po" => $this->mtr."provider_offer"), "crc.provider_id=po.provider_id and crc.offer_id=po.offer_id and crc.batch_id=po.batch_id", array("merchant_id"))
							->where("po.merchant_id ~* ? ".$where, $this->validate($merchant_id))
							->group(array("po.merchant_id","crc.channel_id","crc.provider_id","crc.offer_id","crc.batch_id","crc.campaign_id"));
		} else {
			$sql = $this->db->select()
							->from(array("crc" => $this->mtr."campaign_redeem_code"), array("jumlah" => "COUNT(*)"))
							->joinLeft(array("po" => $this->mtr."provider_offer"), "crc.provider_id=po.provider_id and crc.offer_id=po.offer_id and crc.batch_id=po.batch_id", array("merchant_id"))
							->where("po.merchant_id ~* ? ".$where, $this->validate($merchant_id))
							->group(array("po.merchant_id"));
		}
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) :
			if ($flag == true) {
				$r->promo_id = $r->channel_id."-".$r->provider_id."-".$r->offer_id."-".$r->batch_id."-".$r->campaign_id;
				$data[$r->promo_id] = $r;
			} else {
				$data[$r->merchant_id] = $r;
			}
		endforeach;
		return $data;
	}
	
	public function getJmlRedeemFragment($where, $flag = false, $merchant_id = "")
	{
		$sql = "";
		if ($flag == true) {
			$sql = $this->db->select()
							->from(array("cr" => $this->mtr."campaign_redeem"), array("jumlah" => "COUNT(*)",
																			 "promo_id" => "CONCAT (cr.channel_id,'-',cr.provider_id,'-',cr.offer_id,'-',cr.batch_id,'-',cr.campaign_id)"))
							->joinLeft(array("po" => $this->mtr."provider_offer"), "cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id", array("merchant_id"))
							->where("po.merchant_id ~* ? ".$where, $this->validate($merchant_id))
							->group(array("po.merchant_id","promo_id"));
		} else {
			$sql = $this->db->select()
							->from(array("cr" => $this->mtr."campaign_redeem"), array("jumlah" => "COUNT(*)"))
							->joinLeft(array("po" => $this->mtr."provider_offer"), "cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id", array("merchant_id"))
							->where("po.merchant_id ~* ? ".$where, $this->validate($merchant_id))
							->group(array("po.merchant_id"));
		}
		
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) :
			if ($flag == true) {
				$data[$r->promo_id] = $r;
			} else {
				$data[$r->merchant_id] = $r;
			}
		endforeach;
		return $data;
	}
	
	public function getJmlClaimFragment($where, $flag = false, $merchant_id = '')
	{
		$sql = "";
		if ($flag == true)
		{
			$sql = "select channel_id,provider_id,offer_id,batch_id,campaign_id, merchant_id, count(redeem_code) AS jumlah from
						(select
							cr.provider_id, channel_id, cr.offer_id,cr. batch_id, campaign_id, device_identifier_id, user_id, redeem_time,
							cc.redeem_code, claim_time,merchant_id
						 from
							".$this->mtr."campaign_redeem cr
							left outer join ".$this->mtr."campaign_claim cc on cr.redeem_code=cc.redeem_code
						)as t1
					where merchant_id ~* ".$this->escape($merchant_id)." $where
					GROUP BY merchant_id, channel_id,provider_id,offer_id,batch_id,campaign_id";
		} else {
			$sql = "select merchant_id, count(redeem_code) AS jumlah from
					(select
						cr.provider_id, channel_id, cr.offer_id,cr. batch_id, campaign_id, device_identifier_id, user_id, redeem_time,
						cc.redeem_code, claim_time,merchant_id
					 from
						".$this->mtr."campaign_redeem cr
						left outer join ".$this->mtr."campaign_claim cc on cr.redeem_code=cc.redeem_code
					)as t1
				where merchant_id ~* ".$this->escape($merchant_id)." $where
				GROUP BY merchant_id";
		}
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) :
			if ($flag == true) {
				$r->promo_id = $r->channel_id."-".$r->provider_id."-".$r->offer_id."-".$r->batch_id."-".$r->campaign_id;
				$data[$r->promo_id] = $r;
			} else {
				$data[$r->merchant_id] = $r;
			}
		endforeach;
		return $data;
	}
	
	public function getMerchantPromoDetail($q, $datasearch, $merchant_id, $offset=0, $limit=100){
	
		$sql = " select
				pm.merchant_id, pm.name as merchant,
				CONCAT (coc.channel_id,'-', coc.provider_id,'-', coc.offer_id,'-', coc.batch_id,'-', coc.campaign_id) as promo_id,
				coc.name as promo_name,
				to_char(coc.start_time,'YYYY/MM/DD HH24:MI') as start_time, to_char(coc.end_time,'YYYY/MM/DD HH24:MI') as end_time,
				(
				select
					to_char(cast(max(redeem_time) as timestamp), 'YYYY/MM/DD HH24:MI' )
					from ".$this->mtr."campaign_redeem cr
					left outer join ".$this->mtr."provider_offer po on cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id
					where cr.channel_id=coc.channel_id and cr.provider_id=coc.provider_id and cr.offer_id=coc.offer_id and cr.batch_id=coc.batch_id and cr.campaign_id=coc.campaign_id
				)as last_redeem_time,

				ceil(extract(epoch from((
				select
				max(redeem_time)
				from ".$this->mtr."campaign_redeem cr
				left outer join ".$this->mtr."provider_offer po on cr.provider_id=po.provider_id and cr.offer_id=po.offer_id and cr.batch_id=po.batch_id
				where cr.channel_id=coc.channel_id and cr.provider_id=coc.provider_id and cr.offer_id=coc.offer_id and cr.batch_id=coc.batch_id and cr.campaign_id=coc.campaign_id
				)-(
				coc.start_time
				)))/60)as minutes

				from ".$this->mtr."channel_offer_campaign coc
				left outer join ".$this->mtr."provider_offer po on coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id
				left outer join ".$this->mtr."provider_merchant pm on po.merchant_id=pm.merchant_id and po.provider_id=pm.provider_id ";
		$search = false;
					
		if($datasearch != '' && $datasearch!=null){
			$search_merchant= false;
			if($datasearch["merchant_name_detail"] !=null && $datasearch["merchant_name_detail"] !=''){
				$sql.=" WHERE pm.name ~* ".$this->escape($datasearch["merchant_name_detail"]);
				
				$search_merchant = true;
				$search = true;
			}
			if ($datasearch["start_date"] != "" && $datasearch["end_date"] != "") {
				if($search_merchant == true){
					$sql.=" AND ";
				}
				else{
					$sql.=" WHERE ";
				}
				$sql.= " coc.start_time::date BETWEEN ".$this->escape($datasearch["start_date"])." AND ".$this->escape($datasearch["end_date"]);
				
				$search_merchant = true;
				$search = true;
			}
			if($datasearch["promotion_name_detail"] !=null && $datasearch["promotion_name_detail"] !=''){
				if($search_merchant == true){
					$sql.=" AND ";
				}
				else{
					$sql.=" WHERE ";
				}
				$sql.=" coc.name ~* ".$this->escape($datasearch["promotion_name_detail"]);
				
				$search_merchant = true;
				$search = true;
			}
		}
		else {
			$sql.=" WHERE (pm.name ~* ".$this->escape($q)." OR coc.name ~* ".$this->escape($q).")";	
		}
		
		if(count($datasearch["segment_detail"])!=0){
			if($search==true){
				$sql.=" AND ";
			}
			else{
				$sql.=" WHERE ";
			}
			$where = " channel_id in(";
			$coma = "";
			foreach($datasearch["segment_detail"] as $d){
				$where.=$coma." $d";
				$coma=",";
			}
			
			$where .=")";
		}
		$sql .= $where;
		$advanced = " AND pm.merchant_id = ".$this->escape($merchant_id);
		$sql.=$advanced;

		$sql.=" group by pm.name, pm.merchant_id,coc.channel_id,coc.provider_id,coc.offer_id,coc.batch_id,coc.campaign_id";
		
		// if($limit!='all'){
		// 	$sql.=" offset $offset limit $limit ";
		// }
				
		$row = $this->db->fetchAll($sql);
		
		$jmlvoucher = $this->getJmlVoucherFragment("", true, $merchant_id);
		$jmlredeem = $this->getJmlRedeemFragment("", true, $merchant_id);
		$jmlclaim = $this->getJmlClaimFragment("", true, $merchant_id);
		$data = array();
		foreach ($row as $r) : 
			$r->jumlah_voucher = $jmlvoucher[$r->promo_id]->jumlah;
			$r->jumlah_redeem = $jmlredeem[$r->promo_id]->jumlah;
			$r->total_claim = $jmlclaim[$r->promo_id]->jumlah;
			array_push($data, $r);
		endforeach;
		
		return $data;
	}
	
	public function getCountMerchantPromoDetail($q, $merchant_id, $datasearch){
	
		$sql = " select COUNT(*) AS jumlah
				from ".$this->mtr."channel_offer_campaign coc
				left outer join ".$this->mtr."provider_offer po on coc.provider_id=po.provider_id and coc.offer_id=po.offer_id and coc.batch_id=po.batch_id
				left outer join ".$this->mtr."provider_merchant pm on po.merchant_id=pm.merchant_id and po.provider_id=pm.provider_id ";
		$search = false;
		if($datasearch != '' && $datasearch!=null){
			$search_merchant= false;
			if($datasearch["merchant_name_detail"] !=null && $datasearch["merchant_name_detail"] !=''){
				$sql.=" WHERE pm.name ~* ".$this->escape($datasearch["merchant_name_detail"]);
				
				$search_merchant = true;
				$search = true;
			}
			if ($datasearch["start_date"] != "" && $datasearch["end_date"] != "") {
				if($search_merchant == true){
					$sql.=" AND ";
				}
				else{
					$sql.=" WHERE ";
				}
				$sql.= " coc.start_time::date BETWEEN ".$this->escape($datasearch["start_date"])." AND ".$this->escape($datasearch["end_date"]);
				
				$search_merchant = true;
				$search = true;
			}
			if($datasearch["promotion_name_detail"] !=null && $datasearch["promotion_name_detail"] !=''){
				if($search_merchant == true){
					$sql.=" AND ";
				}
				else{
					$sql.=" WHERE ";
				}
				$sql.=" coc.name ~* ".$this->escape($datasearch["promotion_name_detail"]);
				
				$search_merchant = true;
				$search = true;
			}
		} else {
			$sql.=" WHERE (pm.name ~* ".$this->escape($q)." OR coc.name ~* ".$this->escape($q).")";
			
			$search = true;
		}

		if(count($datasearch["segment_detail"])!=0){
			if($search==true){
				$sql.=" AND ";
			}
			else{
				$sql.=" WHERE ";
			}
			$where = " channel_id in(";
			$coma = "";
			foreach($datasearch["segment_detail"] as $d){
				$where.=$coma." $d";
				$coma=",";
			}
			
			$where .=")";
		}

		
		$sql.=$where;
		$advanced = " AND pm.merchant_id = ".$this->escape($merchant_id);
		$sql.=$advanced;
		
		$row = $this->db->fetchRow($sql);
		return $row->jumlah;
	}
}