<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
require_once "Db/Db_PDO.php";

class Db_Pg_Role extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "roleid";
		$this->table = "msrole";
		$this->kode = "RL";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getRole()
	{
		$sql = $this->db->select()
						->from($this->table, array("roleID" => "roleid",
												   "roleName" => "rolename"))
						->order("roleid ASC");
		return $this->db->fetchAll($sql);
	}
		
	public function getRoleById($roleID)
	{
		$sql = $this->db->select()
						->from($this->table, array("roleID" => "roleid",
												   "roleName" => "rolename"))
						->where($this->primary_column." = ".$this->escape($roleID));
		$row = $this->db->fetchRow($sql);
		return $row;
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
