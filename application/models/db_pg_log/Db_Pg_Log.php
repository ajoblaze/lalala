<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Log extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column  = "logid";
		$this->table = "tr_cs_log";
		$this->kode = "LG";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}

	// Basic Function 
	public function getLog($search, $dataToSearch, $offset = 0, $set = 25, $start_date = "", $end_date = "")
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
		
		$advanced = ($temp>0 ) ? " WHERE (".implode(" AND ", $where).")" : " WHERE (username ~* '".$search."' or rl.rolename ~* '".$search."' 
				or TO_CHAR(tl.created_date,'DD Mon YYYY') ~* '".$search."' or activity_name ~* '".$search."' or detail_activity ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND tl.created_date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$limit = ($set == "") ? "" : " LIMIT ".$set." OFFSET ".$offset;
		
		$sql = $this->db->select()
						->from(array("tl" => $this->table), array("created_date","detail_activity"))
						->join(array("mu" => "msuser"), "mu.userid = tl.userid", array("username"))
						->join(array("ma" => "ms_cs_activity"), "ma.activityid = tl.activityid", array("activity_name"))
						->join(array("rl" => "msrole"), "rl.roleid = mu.roleid", array("roleName" => "rolename"));
		
		$sql .= $advanced." ORDER BY tl.created_date DESC ".$limit;
		return $this->db->fetchAll($sql);
	}
	
	public function getCount($search, $dataToSearch, $start_date = "", $end_date = "")
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
		
		$advanced = ($temp>0 ) ? " WHERE (".implode(" AND ", $where).")" : " WHERE (username ~* '".$search."' or rl.rolename ~* '".$search."' 
				or TO_CHAR(tl.created_date, 'DD Mon YYYY') ~* '".$search."' or activity_name ~* '".$search."' or detail_activity ~* '".$search."')";
		
		if ($start_date != "" && $end_date != "") {
			$advanced .= " AND tl.created_date BETWEEN ".$this->escape($start_date)." AND ".$this->escape($end_date);
		}
		
		$sql = $this->db->select()
						->from(array("tl" => $this->table), array("total" => "COUNT(logid)"))
						->join(array("mu" => "msuser"), "tl.userid = mu.userid", array())
						->join(array("ma" => "ms_cs_activity"), "ma.activityid = tl.activityid", array())
						->join(array("rl" => "msrole"), "mu.roleid = rl.roleid", array());
		$sql .= $advanced;
		
		$row = $this->db->fetchRow($sql);
		return $row->total;
	}
		
	public function getLogById($logID)
	{
		$sql = $this->db->select()
						->from(array("tl" => $this->table), array("created_date","detail_activity"))
						->join(array("mu" => "msuser"),"mu.userid = tl.userid", array("username"))
						->join(array("ma" => "ms_cs_activity"), "ma.activityid = tl.activityid", array("activity_name"))
						->join(array("rl" => "msrole"), "rl.roleid = mu.roleid", array("roleName" => "rolename"))
						->where("tl.".$this->primary_column." = ".$logID);
		$row = $this->db->fetchRow($sql);
		return $row;
	}
	
	public function insert($dataToInsert)
	{			
		try {
			$logID = $this->getNewID(7);
			$this->db->beginTransaction();
			$data = array(
							"logid" => $this->validate($logID),
							"userid" => $this->validate($dataToInsert['userID']),
							"created_date" => date("Y-m-d H:i:s"),
							"activityid" => $this->validate($dataToInsert['activityID']),
							"detail_activity" => $this->validate($dataToInsert['detail_activity'])
							);
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
	
	// Custom Function place here -- 
}