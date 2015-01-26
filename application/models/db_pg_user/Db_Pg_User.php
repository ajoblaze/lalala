<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
require_once "Db/Db_PDO.php";

class Db_Pg_User extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "userid";
		$this->table = "msuser";
		$this->kode = "US";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getUser()
	{
		$sql = $this->db->select()
						->from($this->table, array("userID" => 'userid',
												   "username" => 'username',
												   "pass" => 'pass',
												   "roleID" => 'roleid',
												   "created_date" => "TO_CHAR(created_date, 'DD Mon YYYY HH24:MI')",
												   "active" => 'active',
												   'provider_id' => 'provider_id',
												   'publisher_id' => 'publisher_id'));
		return $this->db->fetchAll($sql);
	}
		
	public function getUserById($userID)
	{
		$sql = $this->db->select()
						->from($this->table, array("userID" => "userid",
												   "username" => "username",
												   "pass" => "pass",
												   "roleID" => "roleid",
												   "created_date" => "TO_CHAR(created_date, 'DD Mon YYYY HH24:MI')",
												   "active" => "active",
												   'provider_id' => "provider_id",
												   'publisher_id' => 'publisher_id'))
						->where($this->primary_column." = ".$this->escape($userID));
		
		$row = $this->db->fetchRow($sql);
		return $row;
	}
	
	public function insert($dataToInsert, $projects, $merchants, $segments)
	{		
		try{
			$userID = $this->getNewID();
			$this->db->beginTransaction();
			$data = array(
							"userid" => $this->validate($userID),
							"username" => $this->validate($dataToInsert["username"]),
							"pass" => $this->validate($this->encrypt($dataToInsert["pass"])),
							"roleid" => $this->validate($dataToInsert['roleID']),
							"created_date" => date('Y-m-d H:i:s'),
							"active" => $this->validate($dataToInsert['active']),
							"provider_id" => $this->validate($dataToInsert['provider_id']),
							"publisher_id" => $this->validate($dataToInsert['publisher_id'])
						 );
			$this->db->insert($this->table, $data);
			
			//insert into trproject_user
			foreach ($projects as $project) : 
				$data = array( "projectid" => $this->validate($project),
							   "userid" => $this->validate($userID) );
				$res = $this->db->insert("trproject_user", $data);
			endforeach;
			
			//insert into merchants
			foreach ($merchants as $merchant) : 
				$data = array( "userid" => $this->validate($userID),
							   "roleid" => $this->validate($dataToInsert['roleID']),
							   "merchant_id" => $this->validate($merchant));
				$res = $this->db->insert("merchant_list", $data);
			endforeach;
			
			//insert into segments
			foreach ($segments as $segment) : 
				$data = array( "userid" => $this->validate($userID),
							   "segment" => $this->validate($segment));
				$res = $this->db->insert("trsegment_user", $data);
			endforeach;
		
			$this->db->commit();
		 } catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		 }
	}
	
	public function update($dataToEdit, $projects, $merchants, $segments)
	{
		try{
			$this->db->beginTransaction();
			$data = array(
							"username" => $this->validate($dataToEdit['username']),
							"roleid" => $this->validate($dataToEdit['roleID']),
							"active" => $this->validate($dataToEdit['active']),
							"provider_id" => $this->validate($dataToEdit['provider_id']),
							"publisher_id" => $this->validate($dataToEdit['publisher_id'])
						 );
			$this->db->update($this->table, $data, $this->primary_column." = ".$this->escape($dataToEdit['userID']));

			// Clean the Editable User
			$this->db->delete("trproject_user", "userid = ".$this->escape($dataToEdit['userID']));
			$this->db->delete("merchant_list", "userid = ".$this->escape($dataToEdit['userID']));
			$this->db->delete("trsegment_user", "userid = ".$this->escape($dataToEdit['userID']));
			
			foreach ($projects as $project) : 
				$data = array ( "projectid" => $this->validate($project),
								"userid" => $this->validate($dataToEdit['userID']));
				$this->db->insert("trproject_user", $data);
			endforeach;
			
			// insert into merchants
			foreach ($merchants as $merchant) : 
				$data = array( "userid" => $this->validate($dataToEdit['userID']),
							   "roleid" => $this->validate($dataToEdit['roleID']),
							   "merchant_id" => $this->validate($merchant));
				$res = $this->db->insert("merchant_list", $data);
			endforeach;
			
			// insert into segments
			foreach ($segments as $segment) : 
				$data = array( "userid" => $this->validate($dataToEdit['userID']),
							   "segment" => $this->validate($segment));
				$res = $this->db->insert("trsegment_user", $data);
			endforeach;
			
			$this->db->commit();
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	public function delete($id)
	{
		try {
			$this->db->beginTransaction();
			// Use Basic Delete instead of custom
			$this->basic_delete($id);
			$this->db->commit();
		} catch (Zend_Exception $e)
		{
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	// Custom Function place here -- 
	public function getProjectByUser($userID)
	{
		$sql =	$this->db->select()
						 ->from("trproject_user", array("projectID" => "projectid"))
						 ->where("userid = ".$this->escape($userID)); 		
		$res = $this->db->fetchAll($sql);
		$arr = array();
		foreach ($res as $row) : 
			array_push($arr,$row->projectID);
		endforeach;
		return $arr;
	}
	
	public function getSegmentByUser($userID)
	{
		$sql = $this->db->select()
						->from("trsegment_user", array("segment"))
						->where("userid = ?", $this->validate($userID));
		$res = $this->db->fetchAll($sql);
		$arr = array();
		foreach ($res as $row) : 
			array_push($arr,$row->segment);
		endforeach;
		return $arr;
	}
	
	public function getMerchantByUser($userID)
	{
		$sql = $this->db->select()
						->from("merchant_list", array("userid", "roleid", "merchant_id"))
						->where("userid = ".$this->escape($userID));
		$res = $this->db->fetchAll($sql);
		$arr = array();
		foreach ($res as $row) : 
			array_push($arr,$row->merchant_id);
		endforeach;
		return $arr;
	}
	
	// Added by Kovan.c
	// 18 / 08 / 2014
	 public function searchLogin($username)
	{	
		$sql = $this->db->select()
						->from(array("mu" => $this->table), array("userID" => "userid",
												   "username" => "username",
												   "pass" => "pass",
												   "roleID" => "roleid",
												   "created_date" => "created_date",
												   "active" => "active",
												   "provider_id" => "provider_id",
												   "publisher_id" => "publisher_id"))
						->join(array("mr" => "msrole"), "mr.roleid = mu.roleid", array("rolename"))
						->where("LOWER(username) =".$this->escape(strtolower($username)));
		$row = $this->db->fetchAll($sql);
		return $row;
	}
	
	public function chkValidPass($userID, $oldpass)
	{
		$sql = $this->db->select()
						->from($this->table, array("Jumlah" => "COUNT(*)"))
						->where($this->primary_column." = ".$this->escape($userID))
						->where("pass = ?", $this->validate($this->encrypt($oldpass)));
		$row = $this->db->fetchRow($sql);
		return ($row->Jumlah > 0) ? true : false;
	}
	
	 public function checkFirst($userID){
		$sql = $this->db->select()
						->from($this->table,array("flag"))
						->where($this->primary_column." = ".$this->escape($userID));
		$row = $this->db->fetchRow($sql);
		return $row->flag;
	}
	
	public function forgetPass($username, $default = "abc123")
	{
		try {
			$sql = $this->db->select()
							->from($this->table, array("jumlah" => "COUNT(username)"))
							->where("username = ".$this->escape($username));
			$row = $this->db->fetchRow($sql);
			if($row->jumlah>0)
			{	
				$this->db->beginTransaction();
				$data = array("pass" => $this->validate($this->encrypt($default)));
				$this->db->update($this->table, $data, "username = ".$this->escape($username));
				$this->db->commit();
				return 1;			
			}
			else return 0;	
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
			return 0;
		}
	}
	
	public function chkExists($email)
	{
		$sql = $this->db->select()
						->from($this->table, array("Jumlah" => "COUNT(*)"))
						->where("lower(username) = lower(".$this->escape($email).")");
		$row = $this->db->fetchRow($sql);
		return ($row->Jumlah>0) ? true : false;
	}
	
	public function chkExistsNotOld($email, $userID)
	{
		$sql = $this->db->select()
						->from($this->table, array("Jumlah" => "COUNT(*)"))
						->where("lower(username) = ?", $this->validate(strtolower($email)))
						->where("userid <> ?", $this->validate($userID));
		$row = $this->db->fetchRow($sql);
		return ($row->Jumlah>0) ? true : false;
	}
	
	public function getCountByActive()
	{
		$sql = $this->db->select()
						->from($this->table, array("Jumlah" => "COUNT(*)"))
						->where("active = ?", 1);
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function getUserForTable($search, $dataToSearch)
	{
		$where = array();
		$temp=0;
		$advanced="";
		foreach($dataToSearch as $key => $value)
		{
			if($value!="")
			{
				array_push($where, ($key." ~* ".$this->escape($value)));
				$temp++;
			}
		}
		
		$advanced = ($temp>0 ) ? " WHERE (".implode(" AND ", $where).")" : " WHERE (username ~* '".$search."' or rolename ~* '".$search."' or projectname ~* '".$search."' OR
				TO_CHAR(mu.created_date, 'DD Mon YYYY') ~* '".$search."' or CAST(active AS VARCHAR) ~* '".$search."')";
				
		$sql = $this->db->select()
						->from(array("mu" => $this->table), array("userID" => "userid",
												   "username" => "username",
												   "created_date" => "TO_CHAR(created_date, 'DD Mon YYYY HH24:MI')",
												   "active" => "active",
												   "provider_id" => "provider_id",
												   "publisher_id" => "publisher_id"))
						->joinLeft(array("rl" => "msrole"),"rl.roleid = mu.roleid", array("roleName" => "rolename"))
						->joinLeft(array("tu" => "trproject_user"), "tu.userid = mu.userid", array())
						->joinLeft(array("mp" => "msproject"), "mp.projectid = tu.projectid", array("projectName" => "COALESCE(projectname, '')", "projectlink" => "projectlink"));
		$sql .= $advanced." ORDER BY username ASC";
		$row = $this->db->fetchAll($sql);
		return $row;
	}
	
	public function getPass($userID)
	{
		$sql = $this->db->select()
						->from($this->table, array("pass"))
						->where($this->primary_column." = ".$this->escape($userID));
		$row = $this->db->fetchRow($sql);
		return $row->pass;
	}
	
	public function changePassword($dataToEdit)
	{
		try {
			$this->db->beginTransaction();
			$data = array( "pass" => $this->validate($this->encrypt($dataToEdit['newpass'])) );
			$this->db->update($this->table, $data, $this->primary_column." = ".$this->escape($dataToEdit['userID']));
			$this->db->commit();
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	public function changeFlag($userID){
		try {
			$this->db->beginTransaction();
			$data = array("flag" => 1);
			$this->db->update($this->table, $data, $this->primary_column." = ".$this->escape($userID));
			$this->db->commit();
		} catch (Zend_Exception $e) {
			$this->db->rollback();
			echo $e->getMessage();
		}
	}
	
	public function getUserByProject($projectID)
	{
		$sql = $this->db->select()
						->from(array("tu" => "trproject_user"), array("projectid", "userid"))
						->join(array("mu" => $this->table), "tu.userid = mu.userid", array("username"))
						->where("projectid = ?", $this->validate($projectID));
		return $this->db->fetchAll($sql);
	}
}