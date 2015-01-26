<?php 
// Created by Albert Ricia 
// On : 11 / 09 / 2014
require_once "Db/Db_PDO.php";

class Db_Pg_Sent extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column  = "sentID";
		$this->table = "trsent";
		$this->kode = "ST";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getSent($offset = 0, $limit = 25)
	{
		$sql = $this->db->select()
						->from(array("tr" => $this->table), array("userID" => "userid",
																  "to_email" => "to_email",
																  "subject" => "subject",
																  "messages" => "messages",
																  "created_date" => "created_date"))
						->join(array("mu" => "msuser"), "mu.userid = tr.userid", array("username"));
		return $this->db->fetchAll($sql);
	}
		
	public function getSentById($sentID)
	{
		$sql = $this->db->select()
						->from(array("tr" => $this->table), array("userID" => "userid",
																  "to_email" => "to_email",
																  "subject" => "subject",
																  "messages" => "messages",
																  "created_date" => "created_date"))
						->join(array("mu" => "msuser"), "mu.userid = tr.userid", array("username"))
						->where($this->primary_column." = ?", $this->validate($sentID));
		$row = $this->db->fetchRow($sql);
		return $row;
	}
	
	public function insert($dataToInsert)
	{		
		try {
			$this->db->beginTransaction();
			$sentID = $this->getNewID();
			$data = array("sentid" => $this->validate($sentID),
						  "userid" => $this->validate($dataToInsert['userID']),
						  "to_email" => $this->validate($dataToInsert['to_email']),
						  "subject" => $this->validate($dataToInsert['subject']),
						  "messages" => $this->validate($dataToInsert['messages']),
						  "created_date" => date('Y-m-d H:i:s'));
			$this->db->insert($this->table, $data);
			$this->db->commit();
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete()
	{
	}
}