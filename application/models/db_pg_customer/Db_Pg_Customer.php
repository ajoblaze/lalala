<?php
require_once "Db/Db_PDO.php";

class Db_Pg_Customer extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column  = "customer_id";
		$this->table = "customer";
		$this->kode = "CS";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	public function getCustomer($search, $dataToSearch, $limit = 0, $offset = 100, $project = "")
	{
		try {
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
			
			$advanced = ($temp>0 ) ? implode(" AND ", $where) : 
									"(name ~* ".$this->escape($search)." OR 
									 email ~* ".$this->escape($search)." OR 
									 imei ~* ".$this->escape($search)." OR 
									 device_model ~* ".$this->escape($search)." OR 
									 app ~* ".$this->escape($search)." OR 
									 status ~* ".$this->escape($search).")";	
			if ($project != "") {
				$advanced .= " AND app ~* ".$this->escape($project);
			}
			
			$set = "";
			if ($offset != ""){
				$set = " LIMIT ".$offset." OFFSET ".$limit;
			}
			
			$sql = "SELECT DISTINCT c.customer_id, c.name, c.email, c.imei, c.identifier_type AS type, c.device_model, c.app, 
					( CASE WHEN LOWER(c.status) = 'enabled' THEN 'active' ELSE 'suspended' END ) AS status, 
					 TO_CHAR(first_download, 'DD Mon YYYY HH24:MI') AS first_download_date, 
					TO_CHAR(last_download, 'DD Mon YYYY HH24:MI') AS last_download_date, 
					dp.product_name, c.customer_value AS price
					FROM ( 
						SELECT customer_id, name, email, imei, identifier_type, device_model, app, status, 
						customer_value, first_download, last_download, creation_time FROM customer
						WHERE (".$advanced.") AND imei <> '' AND email <> ''
						ORDER BY TO_CHAR(COALESCE(first_download,'1-1-1'), 'YYYY-MM-DD HH24:MI') DESC
						".$set."
					) AS c 
					LEFT JOIN device_product AS dp
					ON c.device_model ~* dp.code
					ORDER BY first_download_date DESC";
					
			$this->logToFirebug($sql);
			return $this->db->fetchAll($sql);
		} catch (Zend_Exception $e) {
			$this->logToFirebug($e->getMessage());
		}
	}
	
	public function getCustomerCount($search, $dataToSearch, $project = "")
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
		
		$advanced = "(";
		$advanced .= ($temp>0 ) ? implode(" AND ", $where) : 
								"name ~* ".$this->escape($search)." OR 
								 email ~* ".$this->escape($search)." OR 
								 imei ~* ".$this->escape($search)." OR 
								 device_model ~* ".$this->escape($search)." OR 
								 app ~* ".$this->escape($search)." OR 
								 status ~* ".$this->escape($search);
		$advanced .= ")";
		if ($project != "") {
			$advanced .= " AND app ~* ".$this->escape($project);
		}
			
		$sql=$this->db->select()
						->distinct()
						->from("customer", array("Jumlah" => "COUNT(customer_id)"))
						->where($advanced)
						->where("imei <> ''")
						->where("email <> ''");
		
		$this->logToFirebug($sql->__toString());
		$row = $this->db->fetchRow($sql);
		return $row->Jumlah;
	}
	
	public function suspendOrActivate($app, $device_id, $type = "enabled") {
		try {
			$this->db->beginTransaction();
			$data = array("status" => $type);
			$this->db->update($this->table, $data, "imei = ".$this->escape($device_id)." AND app ~* ".$this->escape($app));
			$this->db->commit();
		} catch (Zend_Exception $e)
		{
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
	}
	
	public function changeEmail($app, $device_id, $new_email) {
		try {
			$this->db->beginTransaction();
			$data = array("email" => $new_email);
			$this->db->update($this->table, $data, "imei = ".$this->escape($device_id)." AND app ~* ".$this->escape($app));
			$this->db->commit();
		} catch (Zend_Exception $e)
		{
			$this->db->rollback();
			$this->logToFirebug($e->getMessage());
		}
	}
}