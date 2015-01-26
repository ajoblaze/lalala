<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
class Db 
{
	var $mysqli;
	var $table;
	var $primary_column;
	var $kode;
	public function __construct()
	{
		// No Constructor
	}
	
	public function createConnection($dbconfig)
	{
		// Create Connection
		$this->mysqli = new mysqli(
									$dbconfig->host,
									$dbconfig->username,
									$dbconfig->pass,
									$dbconfig->db
									);
		if ($this->mysqli->connect_errno)
		{
			echo "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
			die("");
		}
		
		return $this->mysqli;
	}
	
	// Specific Function
	public function getNewID($length = 4)
	{
		$now = getdate();
		$sql = "SELECT RIGHT(MAX(".$this->primary_column."),".$length.") AS number FROM ".$this->table." WHERE ".$this->primary_column." LIKE ".$this->escape($now["year"].str_pad($now["mon"],2,"0",STR_PAD_LEFT)."%");
		$res = $this->db->query($sql);
		$row = $res->fetch_object();
		$num = $row->number + 1;
		return $now["year"].str_pad($now["mon"],2,"0",STR_PAD_LEFT).$this->kode.str_pad($num,$length,"0",STR_PAD_LEFT);
	}
	
	// Basic Function
	public function encrypt($x)
	{
		return sha1($x);
	}
	
	public function basic_delete($x)
	{
		$sql = "DELETE FROM ".$this->table." WHERE ".$this->primary_column." = ".$this->escape($x);
		return $this->mysqli->query($sql);
	}
	
	public function validate($x)
	{
		return str_replace('"',"&quot;",str_replace("'","`",strip_tags($x)));
	}
	
	public function escape($x)
	{
		return "'".$this->validate($x)."'";
	}
	
	public function __destruct()
	{
		
	}
}