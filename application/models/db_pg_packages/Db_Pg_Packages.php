<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Packages extends Db_PDO
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
	public function getPackages($search, $dataToSearch, $offset = 0, $set = 100, $cs = false)
	{	
		$where = (sizeof($dataToSearch) <= 0) ? "CONCAT('Package-',mp.id,'-',name,'-',duration_in_days) ~* ".$this->escape($search) : "CONCAT('Package-',mp.id,'-',name,'-',duration_in_days) ~* ".$this->escape($dataToSearch['package_name']);
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		
		if ($dataToSearch['start_start_date'] != "" && $dataToSearch['end_start_date'] != "") {
			$where .= " AND start_date BETWEEN ".$this->escape($dataToSearch['start_start_date'])." AND ".$this->escape($dataToSearch['end_start_date']);
		}
		if ($dataToSearch['start_expire_date'] != "" && $dataToSearch['end_expire_date'] != "") {
			$where .= " AND expire_date BETWEEN ".$this->escape($dataToSearch['start_expire_date'])." AND ".$this->escape($dataToSearch['end_expire_date']);
		}

		$sql = $this->db->select()
						->from(array("mp" => $this->mtr."package"), array("id", "name", "pck_name" => "CONCAT('Package-',mp.id,'-',name,'-',duration_in_days)", "description", "start_date" => "TO_CHAR(start_date, 'YYYY-MM-DD HH24:MI')", 
																		  "expire_date" => "TO_CHAR(expire_date, 'YYYY-MM-DD HH24:MI')","status"))
						->join(array("mpp" => $this->mtr."package_price"), "mp.id = mpp.package_id ", array("duration_in_days", "price"))
						->where($where);		
		$sql .= $limit;
		$row = $this->db->fetchAll($sql);
		$subscribe = $this->getPackagesSubscribe();
		$data = array();
		foreach($row as $r) :
			$id = "package-".$r->id."-".$r->name."-".$r->duration_in_days;
			$r->transaction = $subscribe[$id]->Jumlah;
			array_push($data, $r);
		endforeach;
		return $data;
	}

	public function getPackagesSubscribe()
	{
		$sql = $this->db->select()
						->from($this->mtr."subscription_order_detail", array("product_name", "Jumlah" => "COUNT(DISTINCT order_id)" ))
						->group("product_name");
		$row = $this->db->fetchAll($sql);
		$data = array();
		foreach($row as $r) : 
			$data[$r->product_name] = $r;
		endforeach;
		return $data;
	}
	
	public function getPackagesCount($search, $dataToSearch, $cs = false)
	{	
		$where = (sizeof($dataToSearch) <= 0) ? "CONCAT('Package-',mp.id,'-',name,'-',duration_in_days) ~* ".$this->escape($search) : "CONCAT('Package-',mp.id,'-',name,'-',duration_in_days) ~* ".$this->escape($dataToSearch['package_name']);
		$limit = ($set != "") ? " LIMIT ".$set." OFFSET ".$offset : "";
		
		if ($dataToSearch['start_start_date'] != "" && $dataToSearch['end_start_date'] != "") {
			$where .= " AND start_date BETWEEN ".$this->escape($dataToSearch['start_start_date'])." AND ".$this->escape($dataToSearch['end_start_date']);
		}
		if ($dataToSearch['start_expire_date'] != "" && $dataToSearch['end_expire_date'] != "") {
			$where .= " AND expire_date BETWEEN ".$this->escape($dataToSearch['start_expire_date'])." AND ".$this->escape($dataToSearch['end_expire_date']);
		}

		$sql = $this->db->select()
						->from(array("mp" => $this->mtr."package"), array("Jumlah" => "COUNT(*)"))
						->join(array("mpp" => $this->mtr."package_price"), "mp.id = mpp.package_id ", array())
						->where($where);		
		$sql .= $limit;
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
}