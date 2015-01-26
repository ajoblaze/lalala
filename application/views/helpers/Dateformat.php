<?php
class Zend_View_Helper_Dateformat extends Zend_View_Helper_Abstract
{	
	var $param_full_date = "DF_FULL_DATE";
	public function dateformat($date, $param = "")
	{
		switch($param)
		{
			case $param_full_date : return date("d M Y", strtotime($date));
			default : return date('d M Y H:i:s',strtotime($date));
		}
	}
}