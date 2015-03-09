<?php
	//connect Direct, PDO, ORM
	//paling cepat: Direct ---> PHP primitif
	//PDO: zend + PHP Data Object ::: Perubahan terhadap DBMS tetap terjaga (tanpa mengubah kode)
	//ORM: kompleks, table berubah menjadi object, berat di memori
	require_once "Db/Db_PDO.php";
	
	class DB_User extends Db_PDO {
		public $db;
		
		public function __construct($dbconfig) {
			$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
		}
		
		public function __destruct() {
			
		}
		
		public function getUser() {
			$sql = $this->db->select()
							->from("msuser", array("id", 
												   "username", 
												   "password",
												   "role",
												   "name"));
			return $this->db->fetchAll($sql);
		}
		
		public function insertUser($dataInsert) {
			try {
				$this->db->beginTransaction();
				$this->db->insert("msuser", $dataInsert);
				$this->db->commit();
			} catch(Zend_Exception $e) {
				$this->db->rollback();
				echo $e->getMessage();
			}
		}
		
		public function deleteUser($userID) {
			try {
				$this->db->beginTransaction();
				$this->db->delete("msuser", "id=".$this->escape($userID));
				$this->db->commit();
			} catch(Zend_Exception $e) {
				$this->db->rollback();
				echo $e->getMessage();
			}
		}
		
		public function updateUser($userID, $dataEdit) {
			try {
				$this->db->beginTransaction();
				$this->db->update("msuser", $dataEdit, "id=".$this->escape($userID));
				$this->db->commit();
			} catch(Zend_Exception $e) {
				$this->db->rollback();
				echo $e->getMessage();
			}
		}
		
		public function insertNew($dataToInsert){
		  try{
			 $this->db->beginTransaction();
			 $this->db->insert("msuser", $dataToInsert);
			 $this->db->commit();
		 }catch(Zend_Exception $e){
			 $this->db->rollback();
			 echo $e->getMessage();
		 }
	   }

	   public function getUsername($prmtr){
		 $sql = $this->db->select()
						 ->from("msuser", array("username"))
						 ->where("username LIKE '".$prmtr."'");
		 return $this->db->fetchAll($sql);
	   }

	}
	
	
?>