<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Outlet extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column  = "";
		$this->table = "";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	public function getOutletPerPromo($q, $datasearch, $offset = 0, $set = 100) {
		$advanced = "";
		$claim_type = "";
		if(count($datasearch["claim_type"]) != 0) {
			$claim_type = " AND mpo.claim_type in(";
			$coma = "";
			foreach($datasearch["claim_type"] as $d){
				$claim_type .= $coma." '$d'";
				$coma = ",";
			}
			$claim_type .= ")";
		}
		
		if($q != '' && $q!=null){
			$advanced =" WHERE ((CONCAT(poo.provider_id,'-', poo.offer_id,'-', poo.batch_id)) ~* ".$this->escape($q)." OR poo.merchant_id ~* ".$this->escape($q)." OR poo.outlet_id ~* ".$this->escape($q)."
							OR EXISTS 
							( SELECT provider_id, offer_id, batch_id, claim_type FROM mtr2_provider_offer AS mpo 
							WHERE ( mpo.name ~* ".$this->escape($q)." ".$claim_type." OR start_time::text ~* ".$this->escape($q)." OR end_time::text ~* ".$this->escape($q).") AND poo.provider_id=mpo.provider_id and poo.offer_id=mpo.offer_id and poo.batch_id=mpo.batch_id 
							)) 
						 OR merchant_id IN ( SELECT merchant_id FROM mtr2_provider_merchant WHERE name ~* ".$this->escape($q)." )";
		}
		else if (sizeof($datasearch) > 0){
			$start_search = "";
			$end_search = "";
			if($datasearch['start_time'] != '' && $datasearch['start_time'] != null) {
				$start_search = " AND start_time >= ".$this->escape($datasearch['start_time'])."::date ";
			}
			if($datasearch['end_time'] != '' && $datasearch['end_time'] != null) {
				$end_search = " AND end_time <= ".$this->escape($datasearch['end_time'])."::date ";
			}
			
			$advanced = " WHERE ((CONCAT(poo.provider_id,'-', poo.offer_id,'-', poo.batch_id)) ~* ".$this->escape($datasearch['promo_id'])." AND poo.merchant_id ~* ".$this->escape($datasearch['merchant_id'])." AND poo.outlet_id ~* ".$this->escape($datasearch['outlet_id'])."
							AND EXISTS 
							( SELECT provider_id, offer_id, batch_id, claim_type FROM mtr2_provider_offer AS mpo 
							WHERE ( mpo.name ~* ".$this->escape($datasearch['promo_name'])." ".$claim_type." ".$start_search." ".$end_search.") AND poo.provider_id=mpo.provider_id and poo.offer_id=mpo.offer_id and poo.batch_id=mpo.batch_id 
							)) 
						 AND merchant_id IN ( SELECT merchant_id FROM mtr2_provider_merchant WHERE name ~* ".$this->escape($datasearch['merchant_name'])." )
					";
		}
		
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : ""; 
		
		$sql = "SELECT
					p.name AS provider_name, 
					CONCAT (poo.provider_id,'-', poo.offer_id,'-',poo.batch_id) as promo_id,
					mpo.name AS promo_name, 
					claim_type,
					poo.merchant_id, 
					pm.name AS merchant_name, 
					poo.outlet_id, 
					pot.name AS outlet_name, 
					TO_CHAR(start_time, 'YYYY-MM-DD HH:MI:SS') AS start_time, 
					TO_CHAR(end_time, 'YYYY-MM-DD HH:MI:SS') AS end_time
				FROM 
				(
					SELECT merchant_id, outlet_id, provider_id, offer_id, batch_id
					FROM provider_offer_outlet  AS poo
					".$advanced."
					".$limit."
				) AS poo
				LEFT OUTER JOIN mtr2_provider_offer mpo ON poo.provider_id=mpo.provider_id and poo.offer_id=mpo.offer_id and poo.batch_id=mpo.batch_id
				LEFT OUTER JOIN mtr2_provider_merchant pm ON poo.merchant_id=pm.merchant_id
				LEFT OUTER JOIN mtr2_provider_outlet pot ON poo.outlet_id=pot.outlet_id
				LEFT OUTER JOIN provider p ON poo.provider_id=p.id 
				";

				
		return $this->db->fetchAll($sql);
	}
	
	public function getCountPerPromo($q, $datasearch) 
	{	
		$advanced = "";
		$claim_type = "";
		if(count($datasearch["claim_type"]) != 0) {
			$claim_type = " AND mpo.claim_type in(";
			$coma = "";
			foreach($datasearch["claim_type"] as $d){
				$claim_type .= $coma." '$d'";
				$coma = ",";
			}
			$claim_type .= ")";
		}
		
		
		if($q != '' && $q!=null){
			$advanced =" WHERE ((CONCAT(poo.provider_id,'-', poo.offer_id,'-', poo.batch_id)) ~* ".$this->escape($q)." OR poo.merchant_id ~* ".$this->escape($q)." OR poo.outlet_id ~* ".$this->escape($q)."
							OR EXISTS 
							( SELECT provider_id, offer_id, batch_id, claim_type FROM mtr2_provider_offer AS mpo 
							WHERE ( mpo.name ~* ".$this->escape($q)." ".$claim_type." OR start_time::text ~* ".$this->escape($q)." OR end_time::text ~* ".$this->escape($q).") AND poo.provider_id=mpo.provider_id and poo.offer_id=mpo.offer_id and poo.batch_id=mpo.batch_id 
							)) 
						 OR merchant_id IN ( SELECT merchant_id FROM mtr2_provider_merchant WHERE name ~* ".$this->escape($q)." )";
		}
		else if (sizeof($datasearch) > 0){
			$start_search = "";
			$end_search = "";
			
			if($datasearch['start_time'] != '' && $datasearch['start_time'] != null) {
				$start_search = " AND start_time >= ".$this->escape($datasearch['start_time'])."::date ";
			}
			if($datasearch['end_time'] != '' && $datasearch['end_time'] != null) {
				$end_search = " AND end_time <= ".$this->escape($datasearch['end_time'])."::date ";
			}
			
			$advanced = " WHERE ((CONCAT(poo.provider_id,'-', poo.offer_id,'-', poo.batch_id)) ~* ".$this->escape($datasearch['promo_id'])." AND poo.merchant_id ~* ".$this->escape($datasearch['merchant_id'])." AND poo.outlet_id ~* ".$this->escape($datasearch['outlet_id'])."
							AND EXISTS 
							( SELECT provider_id, offer_id, batch_id, claim_type FROM mtr2_provider_offer AS mpo 
							WHERE ( mpo.name ~* ".$this->escape($datasearch['promo_name'])." ".$claim_type." ".$start_search." ".$end_search.") AND poo.provider_id=mpo.provider_id and poo.offer_id=mpo.offer_id and poo.batch_id=mpo.batch_id 
							)) 
						 AND merchant_id IN ( SELECT merchant_id FROM mtr2_provider_merchant WHERE name ~* ".$this->escape($datasearch['merchant_name'])." )
					";
		}
		
		$sql = "SELECT
					COUNT(*) AS total_merchant_promo
				FROM 
					provider_offer_outlet poo "
					.$advanced;
					
		$row = $this->db->fetchRow($sql);
		return $row->total_merchant_promo;
	}
//---------------------------------------------------	
	public function getOutletPerMerchant($q_merchant, $datasearch, $offset = 0, $set = 100) {
		$sql = "SELECT
					po.merchant_id, 
					pm.name AS merchant_name, 
					outlet_id, 
					po.name AS outlet_name, 
					city_name AS city
				FROM
					mtr2_provider_outlet po
					LEFT OUTER JOIN mtr2_provider_merchant pm ON po.merchant_id=pm.merchant_id ";
		
		if($q_merchant != '' && $q_merchant !=null){
			$sql.=" WHERE po.merchant_id ~* ".$this->escape($q_merchant).
						" OR pm.name ~* ".$this->escape($q_merchant).
						" OR outlet_id ~* ".$this->escape($q_merchant).
						" OR po.name ~* ".$this->escape($q_merchant).
						" OR city_name ~* ".$this->escape($q_merchant);
		}
		else {
			$sql.=" WHERE po.merchant_id ~* ".$this->escape($datasearch['merchant_id']).
						" AND pm.name ~* ".$this->escape($datasearch['merchant_name']).
						" AND outlet_id ~* ".$this->escape($datasearch['outlet_id']).
						" AND po.name ~* ".$this->escape($datasearch['outlet_name']).
						" AND city_name ~* ".$this->escape($datasearch['city_merchant']);
		}			
		
		$sql .= ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : ""; 
		
		return $this->db->fetchAll($sql);
	}
	
	public function getCountPerMerchant($q_merchant, $datasearch) {
		$sql = "SELECT
					COUNT(po.merchant_id) AS total_merchant_outlet
				FROM
					mtr2_provider_outlet po
					LEFT OUTER JOIN mtr2_provider_merchant pm ON po.merchant_id=pm.merchant_id ";
		
		if($q_merchant != '' && $q_merchant !=null){
			$sql.=" WHERE po.merchant_id ~* ".$this->escape($q_merchant).
						" OR pm.name ~* ".$this->escape($q_merchant).
						" OR outlet_id ~* ".$this->escape($q_merchant).
						" OR po.name ~* ".$this->escape($q_merchant).
						" OR city_name ~* ".$this->escape($q_merchant);
		}
		else {
			$sql.=" WHERE po.merchant_id ~* ".$this->escape($datasearch['merchant_id']).
						" AND pm.name ~* ".$this->escape($datasearch['merchant_name']).
						" AND outlet_id ~* ".$this->escape($datasearch['outlet_id']).
						" AND po.name ~* ".$this->escape($datasearch['outlet_name']).
						" AND city_name ~* ".$this->escape($datasearch['city_merchant']);
		}			
		
		$row = $this->db->fetchRow($sql);
		return $row->total_merchant_outlet;
	}
}
