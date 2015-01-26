<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
require_once "Db/Db_PDO.php";

class Db_Pg_Segment extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "id";
		$this->table = "device_segment3";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getSegment()
	{
		$sql = $this->db->select()
						->distinct()
						->from($this->table, array("segment"))
						->order("segment ASC");
		return $this->db->fetchAll($sql);
	}
		
	public function getAllDeviceSegment()
	{
		$sql = $this->db->select()
						->from($this->table, array("id" => "id",
												   "device_model" => "device_model",
												   "product_name" => "product_name",
												   "segment" => "segment"));
		return $this->db->fetchAll($sql);
	}
	
	public function getDeviceSegment($segment)
	{
		$sql = $this->db->select()
						->from($this->table, array("id" => "id",
												   "device_model" => "device_model",
												   "product_name" => "product_name",
												   "segment" => "segment"))
						->where("segment = ?", $this->validate($segment));
		return $this->db->fetchAll($sql);
	}
	
	public function insert($dataToInsert)
	{			
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete($roleID)
	{
		$this->basic_delete($roleID);
	}
	
	// Custom Function place here -- 
}
