<?php 
// Created by Albert Ricia 
// On : 11 / 09 / 2014
require_once "Db/Db_PDO.php";

class Db_Pg_Reply extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column  = "replyid";
		$this->table = "trreply";
		$this->kode = "RL";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getReply()
	{
		$sql = $this->db->select()
						->from(array("tr" => $this->table), array("replyid" => "replyid",
																  "userid" => "user_id",
																  "to_email" => "to_email",
																  "feedback_id" => "feedback_id",
																  "projectid" => "projectid",
																  "messages" => "messages",
																  "created_date" => "created_date"))
						->join(array("mu" => "msuser"), "mu.userid = tr.userid", array("username"));
		return $this->db->fetchAll($sql);
	}
		
	public function getReplyById($replyID)
	{
		$sql = $this->db->select()
						->from(array("tr" => $this->table), array("replyid" => "replyid",
																  "userid" => "userid",
																  "to_email" => "to_email",
																  "feedback_id" => "feedback_id",
																  "projectid" => "projectid",
																  "messages" => "messages",
																  "created_date" => "to_char(tr.created_date, 'DD mon YYYY')"))
						->join(array("mu" => "msuser"), "mu.userid = tr.userid", array("username"))
						->where($this->primary_column." = ?", $this->validate($replyID));
		$row = $this->db->fetchRow($sql);
		return $row;
		// echo $sql;
	}
	
	public function getExists($projectid){
		$sql = $this->db->select()
						->from(array("tr" => $this->table), array("feedback_id" => "feedback_id", "replyid" => "replyid"))
						->where("projectid = ?", $this->validate($projectid));
		return $this->db->fetchAll($sql);			
	}
	
	public function checkReply($feedback_id, $projectid) 
	{
		$sql = $this->db->select()
						->from(array("tr" => $this->table), array("Jumlah" => "COUNT(*)"))
						->where("feedback_id = ?", $this->validate($feedback_id))
						->where("projectid = ?", $this->validate($projectid));
		$row = $this->db->fetchRow($sql);
		return ($row->Jumlah > 0) ? true : false;
	}
	
	public function insert($dataToInsert)
	{		
		try {
			$this->db->beginTransaction();
			$replyID = $this->getNewID();
			$data = array("replyid" => $this->validate($replyID),
						  "userid" => $this->validate($dataToInsert['userid']),
						  "to_email" => $this->validate($dataToInsert['to_email']),
						  "feedback_id" => $this->validate($dataToInsert['feedback_id']),
						  "projectid" => $this->validate($dataToInsert['projectid']),
						  "messages" => $this->validate($dataToInsert['messages']),
						  "created_date" => date('Y-m-d H:i:s'));
			$this->db->insert($this->table, $data);
			$this->db->commit();
			return $replyID; 
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
		return "";
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete()
	{
	}
}