<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Promo extends Db_PDO
{
	var $db;
	var $mtr = "mtr_";
	public function __construct($dbconfig)
	{
		$this->primary_column = "";
		$this->table = "";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	//Basic Function
	public function getPromoProduct($promo_id){
		$sql = $this->db->select()
						->from(array("p1" => $this->mtr."product"), array("id", "name", "release_date", "price"))
						->joinLeft(array("p2" => $this->mtr."product"), "p1.parent_product_id = p2.id" , array("group" => "name"))
						->joinLeft(array("pc" => $this->mtr."product_category"), "pc.product_id = p2.id", array("category_id", "product_id"))
						->joinLeft(array("c" => $this->mtr."category"), "c.id = pc.category_id", array("category" => "name"))
						->joinLeft(array("pu" => $this->mtr."publisher"), "p2.publisher_id = pu.id", array("author" => "name"))
						->where("upper(p1.type) = ?", "SINGLE")
						->where("p2.promo_id = ".$this->escape($promo_id)." OR p1.promo_id = ".$this->escape($promo_id));
		return $this->db->fetchAll($sql);
	}
	
	public function getPromoDevice($promo_id)
	{
		$sql = $this->db->select()
						->from(array("pe" => $this->mtr."promo_eligible_device"), array("promo_id", "device_id"))
						->join(array("pd" => $this->mtr."promo_device_model"), "pe.device_id = pd.id", array("name", "manufactur", "model", "status"))
						->where("promo_id = ?", $this->validate($promo_id));				
		return $this->db->fetchAll($sql);
	}
	
	public function getPromo($search, $dataToSearch, $offset = 0, $set = 100, $cs = false, $publisher = "")
	{	
		$where_pub = ($publisher != "") ?  " AND id in
											(
												select p.promo_id from product as p
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
		$where = (sizeof($dataToSearch) <= 0) ? "promo_name ~* ".$this->escape($search)
				  : 
				  "promo_name ~* ".$this->escape($dataToSearch['promo_name']);
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		
		if ($dataToSearch['start_start_date'] != "" && $dataToSearch['end_start_date'] != "") {
			$where .= " AND start_date BETWEEN ".$this->escape($dataToSearch['start_start_date'])." AND ".$this->escape($dataToSearch['end_start_date']);
		}
		if ($dataToSearch['start_end_date'] != "" && $dataToSearch['end_end_date'] != "") {
			$where .= " AND end_date BETWEEN ".$this->escape($dataToSearch['start_end_date'])." AND ".$this->escape($dataToSearch['end_start_date']);
		}
		if ($dataToSearch['start_valid_date'] != "" && $dataToSearch['end_valid_date'] != "") {
			$where .= " AND valid_date BETWEEN ".$this->escape($dataToSearch['start_valid_date'])." AND ".$this->escape($dataToSearch['end_valid_date']);
		}
		
		$where .= $where_pub;
		
		$where_cs = ($cs == true) ? "current_timestamp BETWEEN start_date AND end_date" : "id::text ~* ''";
		
		$sql = $this->db->select()	
						->from($this->mtr."promo", array("id",
														  "promo_name", 
														  "start_date" => "TO_CHAR(start_date, 'YYYY-MM-DD HH24:MI')",
														  "end_date" => "TO_CHAR(end_date, 'YYYY-MM-DD HH24:MI')",
														  "valid_date" => "TO_CHAR(valid_date, 'YYYY-MM-DD HH24:MI')"))
						->where($where)
						->where($where_cs);
		$sql .= $limit;
		return $this->db->fetchAll($sql);
	}
	
	public function getPromoCount($search, $dataToSearch, $cs = false, $publisher = "")
	{	
		$where_pub = ($publisher != "") ?  " AND id in
											(
												select p.promo_id from product as p
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
		$where = (sizeof($dataToSearch) <= 0) ? "promo_name ~* ".$this->escape($search)
				  : 
				  "promo_name ~* ".$this->escape($dataToSearch['promo_name']);
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		
		if ($dataToSearch['start_start_date'] != "" && $dataToSearch['end_start_date'] != "") {
			$where .= " AND start_date BETWEEN ".$this->escape($dataToSearch['start_start_date'])." AND ".$this->escape($dataToSearch['end_start_date']);
		}
		if ($dataToSearch['start_end_date'] != "" && $dataToSearch['end_end_date'] != "") {
			$where .= " AND end_date BETWEEN ".$this->escape($dataToSearch['start_end_date'])." AND ".$this->escape($dataToSearch['end_start_date']);
		}
		if ($dataToSearch['start_valid_date'] != "" && $dataToSearch['end_valid_date'] != "") {
			$where .= " AND valid_date BETWEEN ".$this->escape($dataToSearch['start_valid_date'])." AND ".$this->escape($dataToSearch['end_valid_date']);
		}
		
		$where .= $where_pub;
		
		$where_cs = ($cs == true) ? "current_timestamp BETWEEN start_date AND end_date" : "id::text ~* ''";
		
		$sql = $this->db->select()	
						->from($this->mtr."promo", array("Jumlah" => "COUNT(*)"))
						->where($where)
						->where($where_cs);
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
}