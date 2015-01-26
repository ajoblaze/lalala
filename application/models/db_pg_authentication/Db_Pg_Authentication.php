<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
require_once "Db/Db_PDO.php";

class Db_Pg_Authentication extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "id";
		$this->table = "trlink_exception";
		$this->kode = "";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getAuthentication()
	{
		$sql = $this->db->select()
						->from($this->table, array("id" => "id",
												   "roleID" => "roleid",
												   "link" => "link"))
						->order("roleid ASC");
		return $this->db->fetchAll($sql);
	}
		
	public function getAuthenticationByRole($roleID)
	{
		$sql = $this->db->select()
						->from($this->table, array("id" => "id",
												   "roleID" => "roleid",
												   "link" => "link"))
						->where("roleid = ".$this->escape($roleID));	
		return $this->db->fetchAll($sql);
	}
	
	public function insert($dataToInsert)
	{			
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete($id)
	{
		$this->basic_delete($id);
	}
	
	// Custom Function place here -- 
	public function checkLink($roleID, $link)
	{
		try {
			$sql = $this->db->select()
						->from($this->table, array( "Jumlah" => "COUNT(*)" ))
						->where("roleid = ?", $this->validate($roleID))
						->where("? ~* link", $this->validate($link));
			$row = $this->db->fetchRow($sql);
			return ($row->Jumlah > 0) ? false : true;
		} catch(Zend_Exception $e) {
			$this->logToFirebug($e->getMessage());
		}
		return true;
	}
}
