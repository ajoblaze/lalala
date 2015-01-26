<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Download extends Db_PDO
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
	public function getDownload($search, $dataToSearch, $offset = 0, $set = 100, $publisher = "")
	{	
		$where_pub = ($publisher != "") ? " AND p.id in
											(
												select p.id from product as p
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
											
		$where = (sizeof($dataToSearch) <= 0) ? "(CONCAT(p.name,'-',TO_CHAR(dh.download_time, 'DD Mon YYYY')) ~* ".$this->escape($search). " OR u.name ~* ".$this->escape($search)." OR u.email ~* ".$this->escape($search)." OR dr.device_identifier::text ~* ".$this->escape($search)." OR dr.device_model ~* ".$this->escape($search)." OR dseg.segment ~* ".$this->escape($search).")"
				  : 
				  "CONCAT(p.name,'-',TO_CHAR(dh.download_time, 'DD Mon YYYY')) ~* ".$this->escape($dataToSearch['product']). " AND u.name ~* ".$this->escape($dataToSearch['name'])." AND u.email ~* ".$this->escape($dataToSearch['email'])." AND dr.device_identifier::text ~* ".$this->escape($dataToSearch['device_identifier'])." AND dr.device_model ~* ".$this->escape($dataToSearch['device_model'])." AND dseg.segment ~* ".$this->escape($dataToSearch['segment']);
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		
		if ($dataToSearch['start_download'] != "" && $dataToSearch['end_download'] != "") {
			$where .= " AND download_time BETWEEN ".$this->escape($dataToSearch['start_download'])." AND ".$this->escape($dataToSearch['end_download']);
		}
		
		$where .= $where_pub;

		$sql = $this->db->select()
						->from(array("dh" => $this->mtr."download_history"), array("id", "download_url", "download_time" => "TO_CHAR(dh.download_time, 'YYYY-MM-DD HH24:MI:SS')"))
						->joinLeft(array("p" => $this->mtr."product"), "p.id = dh.product_id", array("product" => "name", "release_date"))
						->joinLeft(array("u" => $this->mtr."app_user"), "u.id = dh.app_user_id", array("name", "email"))
						->joinLeft(array("dr" => $this->mtr."device_reg"), "dr.id = dh.device_reg_id", array("device_model", "device_identifier"))
						->joinLeft(array("dseg" => "device_segment3"), "REPLACE(dr.device_model, 'Samsung ', '') = dseg.device_model", array("segment", "product_name"))
						->where($where);
		$sql .= $limit;
		return $this->db->fetchAll($sql);
	}
	
	public function getDownloadCount($search, $dataToSearch, $publisher = "")
	{	
		$where_pub = ($publisher != "") ? " AND p.id in
											(
												select p.id from product as p
												join product as p2 on p.parent_product_id = p2.id
												join publisher as pu on p2.publisher_id = pu.id
												where pu.name = ".$this->escape($publisher)."
											)" : "";
		$where = (sizeof($dataToSearch) <= 0) ? "(CONCAT(p.name,'-',TO_CHAR(dh.download_time, 'DD Mon YYYY')) ~* ".$this->escape($search). " OR u.name ~* ".$this->escape($search)." OR u.email ~* ".$this->escape($search)." OR dr.device_identifier::text ~* ".$this->escape($search)." OR dr.device_model ~* ".$this->escape($search)." OR dseg.segment ~* ".$this->escape($search).")"
				  : 
				  "CONCAT(p.name,'-',TO_CHAR(dh.download_time, 'DD Mon YYYY')) ~* ".$this->escape($dataToSearch['product']). " AND u.name ~* ".$this->escape($dataToSearch['name'])." AND u.email ~* ".$this->escape($dataToSearch['email'])." AND dr.device_identifier::text ~* ".$this->escape($dataToSearch['device_identifier'])." AND dr.device_model ~* ".$this->escape($dataToSearch['device_model'])." AND dseg.segment ~* ".$this->escape($dataToSearch['segment']);
		if ($dataToSearch['start_download'] != "" && $dataToSearch['end_download'] != "") {
			$where .= " AND download_time BETWEEN ".$this->escape($dataToSearch['start_download'])." AND ".$this->escape($dataToSearch['end_download']);
		}
		
		$where .= $where_pub;

		$sql = $this->db->select()
						->from(array("dh" => $this->mtr."download_history"), array("Jumlah" => "COUNT(*)"))
						->joinLeft(array("p" => $this->mtr."product"), "p.id = dh.product_id", array())
						->joinLeft(array("u" => $this->mtr."app_user"), "u.id = dh.app_user_id", array())
						->joinLeft(array("dr" => $this->mtr."device_reg"), "dr.id = dh.device_reg_id", array())
						->joinLeft(array("dseg" => "device_segment3"), "REPLACE(dr.device_model, 'Samsung ', '') = dseg.device_model", array())
						->where($where);
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
}