<?php
  require_once "Db/Db_PDO.php";

  class Db_User extends Db_PDO{
    public $db;
    public function __construct($dbconfig){
      //create connection
      $this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
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

   public function getUsername(){
     $sql = $this->db->select()
                     ->from("msuser", array("username"));
     return $this->db->fetchAll($sql);
   }

    public function __destruct(){

    }
  }
