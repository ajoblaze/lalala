<?php
/*
	Created by Albert Ricia
	on 28 / 08 / 2014
	Reusable Export Function
*/
class Exporter
{
	var $delim = "\t";
	public function __construct()
	{
	}
	
	public function getDirectory($path)
	{
		$p = explode("/",$path);
		return $p[0];
	}
	
	// Added by Albert Ricia
	// on 04 / 09 / 2014
	public function exportToCSVFromJSON($json, $path)
	{
		if (file_exists($path)) {
			unlink($path);
		}
		
		$dir = $this->getDirectory($path);
		if (!file_exists($dir))
		{
			mkdir($dir,0777);
		}
		
		$filename = $path;
		$fp = fopen($filename, "w");
		$ln = 0;
		$obj = json_decode($json);
		foreach ($obj as $o) : 
			$separator = "";
			$comma = "";	
			$temp = "";
			foreach ($o as $key => $value) : 
				if ($ln == 0)
				{
					$separator .=  $comma.''.str_replace('','""', $key);
					$temp .=  $comma.''.str_replace('','""', $value);
					$comma = $this->delim;
				}
				else
				{
					$separator .= $comma.''.str_replace('','""', $value);
					$comma = $this->delim;
				}
			endforeach;
			$separator .="\n";
			if ($ln == 0) 
			{
				$separator.=$temp."\n";
			}
			fputs($fp, $separator);
			$ln++;
		endforeach;
		fputs($fp,"---------");
		fclose($fp);
	}
	
	// Added by Kelvin Tham
	// on 27 / 08 / 2014
	public function exportToCSVFromDB($result, $path)
	{
		$dir = $this->getDirectory($path);
		if (!file_exists($dir))
		{
			mkdir($dir,0777);
		}
		
		$filename = $path;
		$fp = fopen($filename, "w");
		$ln = 0;
		foreach ($result as $line) :
			$separator = "";
			$comma = "";	
			$temp = "";
			
			foreach($line as $name => $value){
				if ($ln == 0)
				{
					$separator .=  $comma.''.str_replace('','""', $name);
					$temp .=  $comma.''.str_replace('','""', $value);
					$comma = $this->delim;
				}
				else
				{
					$separator .= $comma.''.str_replace('','""', $value);
					$comma = $this->delim;
				}
			}
			$separator .="\n";
			if ($ln == 0) 
			{
				$separator.=$temp."\n";
			}

			fputs($fp, $separator);
			$ln++;
		endforeach;
		fputs($fp,"---------");
		fclose($fp);
	}

	public function __destruct()
	{
	}
}