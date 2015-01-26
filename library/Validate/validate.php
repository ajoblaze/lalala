<?php
class Validate
{
	public function __construct()
	{
	}
	
	public function chkAlNum($x)
	{
		$regex = "[A-Za-z0.9]+";
		return (!ereg($regex,$x)) ? false : true;
	}
	
	public function chkEmail($x)
	{
		$regex = "^.+@.+\..+$";
		return (!ereg($regex,$x)) ? false : true;
	}
	
	public function __destruct()
	{
	}
}
