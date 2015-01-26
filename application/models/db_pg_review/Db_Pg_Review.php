<?php
/*
	Created By Albert Ricia
  	Date : 25 August 2014
*/
require_once "Db/Db_PDO.php";

class Db_Pg_Review extends Db_PDO
{
	var $db;
	public function __construct($dbconfig)
	{
		$this->primary_column = "projectid";
		$this->table = "review";
		$this->kode = "PR";
		$this->db = $this->createConnection($dbconfig, Db_PDO::$PDO_PGSQL);
	}
	
	// Basic Function 
	public function getAllReview($id)
	{
		$sql = $this->db->select()
						->from($this->table, array("review_date" => "review_date", 
												   "star_rating" => "star_rating", 
												   "review_title" => "review_title", 
												   "review_text" => "review_text"))
						->where("projectid = ?", $this->validate($id))
						->where("review_title <> ''")
						->where("review_text <> ''")
						->order("review_date ASC");
		return $this->db->fetchAll($sql);
	}
	
	public function insert($dataToInsert)
	{			
	}
	
	public function update($dataToEdit)
	{
	}
	
	public function delete()
	{
	}
	
	//Custom Function --
	public function getReviewBy($id, $col, $method)
	{
		$sql = $this->db->select()
						->distinct()
						->from($this->table, array("review_date" => "review_date", 
												   "star_rating" => "star_rating", 
												   "review_title" => "review_title", 
												   "review_text" => "review_text"))
						->where("projectid = ?", $this->validate($id))
						->where("review_title <> ''")
						->where("review_text <> ''")
						->order($col." ".$method);
		return $this->db->fetchAll($sql);
	}
}
