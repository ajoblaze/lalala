<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Historysuspend extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "historyid";
		$this->table = "trhistory_suspend";
		$this->kode = "HT";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getHistorySuspend()
	{
		$sql = $this->db->select()
						->from(array("hs" => $this->table), array("historyID" => "historyid",
																  "email" => "email",
																  "device_identifier" => "device_identifier", 
																  "device_type" => "device_type", 
																  "app" => "app",
																  "actions" => "actions",
																  "approve_status" => "approve_status",
																  "created_date" => "TO_CHAR(hs.created_date, 'DD Mon YYYY HH24:MI')",
																  "userID" => "userid",
																  "approveBy" => "approveby",
																  "approveDate" => "TO_CHAR(approvedate, 'DD Mon YYYY HH24:MI')"))
						->joinLeft(array("mu" => "msuser"), "hs.userid = mu.userid", array("username"));
		return $this->db->fetchAll($sql);
	}
		
	public function getHistorySuspendById($historyID)
	{
		$sql = $this->db->select()
						->from(array("hs" => $this->table), array("historyID" => "historyid",
																  "email" => "email",
																  "device_identifier" => "device_identifier", 
																  "device_type" => "device_type", 
																  "app" => "app",
																  "actions" => "actions",
																  "approve_status" => "approve_status",
																  "created_date" => "TO_CHAR(hs.created_date, 'DD Mon YYYY HH24:MI')",
																  "userID" => "userid",
																  "approveBy" => "approveby",
																  "approveDate" => "TO_CHAR(approvedate, 'DD Mon YYYY HH24:MI')"))
						->joinLeft(array("mu" => "msuser"), "hs.userid = mu.userid", array("username"))
						->where($this->primary_column." = ".$this->escape($historyID));
		$row = $this->db->fetchRow($sql);
		return $row;
	}
	
	public function insert($dataToInsert)
	{
		try {
			$this->db->beginTransaction();
			$historyID = $this->getNewID();
			$data = array(
							"historyid" => $this->validate($historyID),
							"email" => $this->validate($dataToInsert['email']),
							"device_identifier" => $this->validate($dataToInsert['device_identifier']),
							"device_type" => $this->validate($dataToInsert['device_type']),
							"app" => $this->validate($dataToInsert['app']),
							"actions" => $this->validate($dataToInsert['actions']),
							"reason_actions" => $this->validate($dataToInsert['reason_actions']),
							"created_date" => date('Y-m-d H:i:s'),
							"userid" => $this->validate($dataToInsert['userID'])
						 );
			$this->db->insert($this->table, $data);
			$this->db->commit();
			return $historyID;
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	
	public function update($dataToEdit)
	{
		try {
			$this->db->beginTransaction();
			$data = array("approve_status" => $this->validate($dataToEdit['approve_status']),
						  "approveby" => $this->validate($dataToEdit['approveBy']),
						  "approvedate" => date('Y-m-d H:i:s'),
						  "reason_approve" => $this->validate($dataToEdit['reason_approve']));
			$this->db->update($this->table, $data, $this->primary_column." = ".$this->escape($dataToEdit['historyID']));
			$this->db->commit();
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	public function delete()
	{
	}
	
	// Custom Function place here -- 
	public function getHistorySuspendByEmail($email, $app)
	{
		$sql = $this->db->select()
						->from(array("ht" => $this->table), array("historyID" => "historyid",
																  "email" => "email",
																  "app" => "app",
																  "actions" => "actions",
																  "approve_status" => "( CASE WHEN approve_status = 0 THEN 'HAS NOT BEEN DECIDED.' WHEN approve_status = 1 THEN 'APPROVED' WHEN approve_status = 2 THEN 'REJECTED' END )",
																  "reason_actions" => "reason_actions",
																  "created_date" => "TO_CHAR(ht.created_date, 'DD Mon YYYY HH24:MI')",
																  "userID" => "userid",
																  "approveBy" => "approveby",
																  "approveDate" => "TO_CHAR(approvedate, 'DD Mon YYYY HH24:MI')"))
						->join(array("mu" => "msuser"), "mu.userid = ht.userid", array("username"))
						->where("ht.email = ?", $this->validate($email))
						->where("ht.app = ?", $this->validate($app));
		$this->logToFirebug($sql->__toString());
		return $this->db->fetchAll($sql);
	}
	
	public function getHistorySuspendByImei($imei, $app)
	{
		$sql = $this->db->select()
						->from(array("ht" => $this->table), array("historyID" => "historyid",
																   "email" => "email",
																   "app" => "app",
																   "actions" => "actions",
																   "device_identifier" => "device_identifier",
																   "approve_status" => "( CASE WHEN approve_status = 0 THEN 'HAS NOT BEEN DECIDED.' WHEN approve_status = 1 THEN 'APPROVED BY ADMIN' WHEN approve_status = 2 THEN 'REJECTED BY ADMIN' WHEN approve_status = 3 THEN 'VERIFIED BY USER' WHEN approve_status = 4 THEN 'CANCELLED BY USER' END )",
																   "reason_actions" => "reason_actions",
																   "created_date" => "created_date",
																   "userID" => "userid",
																   "approveBy" => "approveby",
																   "approveDate" => "approvedate"))
						->join(array("mu" => "msuser"), "ht.userid = mu.userid", array("username"))
						->where("ht.device_identifier = ?", $this->validate($imei))
						->where("ht.app = ?", $this->validate($app));
		$this->logToFirebug($sql->__toString());
		return $this->db->fetchAll($sql);
	}	
	
	public function isApprove($historyID) 
	{	
		$sql = $this->db->select()
						->from($this->table, array("Jumlah" => "COUNT(*)"))
						->where("historyid = ?",$this->validate($historyID))
						->where("approve_status <> ?", 0);
		$row = $this->db->fetchRow($sql);
		return ($row->Jumlah > 0) ? true : false; 
	}
}