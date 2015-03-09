<?php
require_once "Zend/Db.php";

class Db_PDO
{
	var $db;
	var $table;
	var $primary_column;
	var $kode;
	var $options, $writer, $logger;
	var $profiler;
	static $PDO_MYSQL = "Pdo_Mysql", 
		   $PDO_PGSQL = "Pdo_Pgsql", 
		   $PDO_SQLLITE = "Pdo_Sqllite",
		   $PDO_MSSQL = "Pdo_Mssql",
		   $PDO_DB2 = "Pdo_Ibm", // Informix Database and DB2 
		   $PDO_ORACLE = "Pdo_Oci";
	
	public function __construct()
	{
		$this->options = array(
								Zend_Db::AUTO_QUOTE_IDENTIFIERS => true,
								Zend_Db::ALLOW_SERIALIZATION => false,
								Zend_Db::CASE_FOLDING => Zend_Db::CASE_NATURAL,
								"persistent" => true
							  );
	}
	
 	public function createConnection($dbconfig, $PDO_TYPE = "Pdo_Mysql", $fetch_type = Zend_Db::FETCH_OBJ)
	{	
		 try{
			// Create Connection
			$this->db = Zend_Db::factory($PDO_TYPE, 
											  array(
													"host" => $dbconfig->host,
													"username" => $dbconfig->username,
													"password" => $dbconfig->pass,
													"dbname" => $dbconfig->db,
													"options" => $this->options,
													"profiler" => true
												)
											);
			$this->db->getConnection();
			
			$this->db->setFetchMode($fetch_type);
			
			$this->profiler = new Zend_Db_Profiler_Firebug("All DB Queries");
			$this->profiler->setEnabled(true);
			$this->db->setProfiler($this->profiler);
			
			return $this->db;
		} catch (Zend_Db_Adapter_Exception $e) {
			echo "Message : ".$e->getMessage();
		} catch (Zend_Exception $e) {
			echo "Message : ".$e->getMessage();
		}
		return NULL; 
	}
	
	// Specific Function
	public function getNewID($length = 4)
	{
		$now = getdate();
		$sql = "SELECT RIGHT(MAX(".$this->primary_column."),".$length.") AS number FROM ".$this->table." WHERE ".$this->primary_column." LIKE ".$this->escape($now["year"].str_pad($now["mon"],2,"0",STR_PAD_LEFT)."%");
		$this->logToFirebug($sql);
		$row = $this->db->fetchRow($sql);
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
		$this->db->delete($this->table, $this->primary_column." = ".$this->escape($x));
	}
	
	public function validate($x)
	{
		return str_replace('"',"&quot;",str_replace("'","`",strip_tags($x)));
	}
	
	public function escape($x)
	{
		return $this->db->quote($this->validate($x));
	}
	
	public function logToFirebug($text, $type = Zend_Log::INFO)
	{
		$this->writer = new Zend_Log_Writer_Firebug();
		$this->logger = new Zend_Log($this->writer);
		$this->logger->log($text, $type);
	}
	
	public function logToFile($text, $path = "log.txt")
	{
		$this->writer = new Zend_Log_Writer_Stream($path);
		$this->logger = new Zend_Log($this->writer);
		 
		$this->logger->info($text);
	}

	public function swap(&$obj1, &$obj2) {
		$temp = $obj1;
		$obj1 = $obj2;
		$obj2 = $temp;
	}

	public function selectionSort(&$array, $property, $property2 = "", $mode = "ASC")
	{
		
		for ($i=0 ; $i<sizeof($array)-1;$i++)
		{
			$max = $i;
			for ($j = $i+1; $j< sizeof($array); $j++) 
			{
				if ($property2 == "") {
					$str1 = ($mode == "ASC") ? $array[$j]->$property : $array[$max]->$property;
					$str2 = ($mode == "ASC") ? $array[$max]->$property : $array[$j]->$property;
				} else {
					$str1 = ($mode == "ASC") ? $array[$j]->$property.$array[$j]->$property2 : $array[$max]->$property.$array[$max]->$property2;
					$str2 = ($mode == "ASC") ? $array[$max]->$property.$array[$max]->$property2 : $array[$j]->$property.$array[$j]->$property2;
				}

				if (strcasecmp($str1, $str2) < 0) 
				{
					$max = $j;
				}
			}
			$this->swap($array[$max], $array[$i]);
		}
		return $array;
	}
	
	public function __destruct()
	{
		
	}
}