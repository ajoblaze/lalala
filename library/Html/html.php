<?php

class Html 
{
	static $BUTTON_BUTTON = "button";
	static $BUTTON_SUBMIT = "submit";
	static $BUTTON_RESET = "reset";
	static $TARGET_NEW_TAB  = "_blank";
	var $css;
	public function __construct()
	{
	}
	
	public function openHtmlTag($content)
	{
		return "<html><head></head><body>".$content."</body></html>";
	}
	
	public function createBtn($type, $class, $id, $text, $onclick = "")
	{
		return "<button type='".$type."' class='".$class."' id='".$id."' onclick='".$onclick."'>".$text."</button>";
	}
	
	public function createLink($html, $href, $target = "", $class="")
	{
		return "<a href='".$href."' class='".$class."' target='".$target."'>".$html."</a>";
	}
	
	public function inlineCSS($property)
	{
		return "<style type='text/css'>".$property."</style>";
	}
	
	public function compileHtmlParam($msg, $content, $useCSS = false) 
	{
		foreach ($content as $key => $value) : 
			$msg = str_replace("{{".$key."}}",$value,$msg);
		endforeach;
		
		return ($useCSS == true) ? $this->css." ".$msg : $msg;
	}
	
	public function createTbl($class, $rows)
	{	
		$table = "<table class='".$class."'>";
		foreach ($rows as $key => $value ) :
			$table .= "<tr>";
			$table .= "<td>".$key."</td>";
			$table .= "<td> : </td>";
			$table .= "<td>".$value."</td>";
			$table .= "</tr>";
		endforeach;
		$table .= "</table>";
		return $table;
	}
	
	public function openTemplate($file)
	{
		$f = fopen($file, "r");
		$str = "";
		while (!feof($f)) {
			$str.= fread($f, 1000000);
		}
		fclose($f);
		return $str;
	}
	
	public function useCSS($file)
	{
		$f = fopen($file, "r");
		$str = "";
		while (!feof($f)) {
			$str.= fread($f, 1000000);
		}
		fclose($f);
		$this->css = "<style type='text/css'>".$str."</style>";
	}
	
	public function compileWithCSS($html)
	{
		return $this->css." ".$html;
	}
	
	public function __destruct()
	{
	}
}