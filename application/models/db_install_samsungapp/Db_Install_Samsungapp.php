<?php
/*
	Created By Albert Ricia 
  	Date : 15 August 2014
*/
require_once "Db/Db_PDO.php";

class Db_Install_Samsungapp extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->table = "install_samsungapp";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}

	private function getProjectidbyName($project_name){
		$sql = $this->db->select()
						->from(array('p'=>'project'), array("projectid"))
						->join(array('mp'=>'msproject'), 'p.linkid = mp.projectid',array())
						->where('mp.projectlink=?', $this->validate($project_name));
		// $select = "select p.projectid from project p join msproject mp on p.linkid = mp.projectid where mp.projectlink=$project_name";
		$row = $this->db->fetchRow($sql);
		return $row->projectid;
	}
	
	public function getDataById($id)
	{
		try {
			$sql = $this->db->select()
							->from("install_samsungapp", array("id" => "id",
															   "projectid" => "projectid",
															   "datelabel" => "TO_CHAR(install_date, 'dd Mon YYYY')",
															   "install_date" => "install_date",
															   "daily_install" => "daily_install"))
							->where("id = ?", $this->validate($id));
			//echo $sql;
			return $this->db->fetchRow($sql);
		}
		
		catch (Zend_Exception $e) {
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
	}
	
	public function chkExists($install_date, $project) {
		$sql = $this->db->select()
						->from($this->table, array("jumlah" => "COUNT(*)"))
						->where("install_date = ?", $this->validate($install_date))
						->where("projectid = ?", $this->validate($project));
		$row = $this->db->fetchRow($sql);
		return ($row->jumlah > 0) ? true : false ;
	}
	
	public function insertSamsungApp($project_name, $daily_install, $install_date) {
		$project = $this->getProjectidbyName($project_name);
		
		try {
			if ($this->chkExists($install_date, $project) == false) {
				$this->db->beginTransaction();
				$data = array(
								"install_date" => $this->validate($install_date),
								"projectid" => $this->validate($project),
								"daily_install" => $this->validate($daily_install)
								);
				$this->db->insert($this->table, $data);
				$this->db->commit();
				return "success";
			} else {
				return "not success";
			}
		}
		
		catch (Zend_Exception $e) {
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
		
		return "failed";
	}
	
	public function editSamsungApp($id, $daily_install) {
	
		try{
			$this->db->beginTransaction();
			$data = array("daily_install" => $this->validate($daily_install));
			
			$where = "id = ".$this->escape($id);
			
			$sql = $this->db->update($this->table, $data, $where);
			$this->logToFirebug($sql);
			$this->db->commit();
			return "success";
		}
		catch (Zend_Exception $e) {
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
		
		return "failed";
	}
	
	public function deleteSamsungApp($id)
	{
		$project = $this->getProjectidbyName($project_name);
		
		try {
			$this->db->beginTransaction();
			
			// Use Basic Delete instead of custom
			
			$where = "id = ".$this->escape($id);
			
			$sql = $this->db->delete($this->table, $where);
			$this->logToFirebug($sql);
			$this->db->commit();
			return "success";
		}
		
		catch (Zend_Exception $e) {
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
			echo $e->getMessage();
		}
		
		return "failed";
	}
}

